<?php
/**
 * Web Push Protocol (RFC 8030) - Pure PHP implementation
 * Supports VAPID (RFC 8292) + aes128gcm payload encryption (RFC 8291)
 * Requires: PHP 8.1+, openssl extension with EC support, curl extension.
 */

class WebPush {
    private string $vapidPublic;   // raw 65-byte uncompressed P-256 public key
    private string $vapidPrivate;  // raw 32-byte private scalar
    private string $vapidPublicB64; // base64url
    private string $subject;       // mailto: or https: URL

    public function __construct(string $vapidPublicB64, string $vapidPrivateB64, string $subject) {
        $this->vapidPublic = self::b64UrlDecode($vapidPublicB64);
        $this->vapidPrivate = self::b64UrlDecode($vapidPrivateB64);
        $this->vapidPublicB64 = $vapidPublicB64;
        $this->subject = $subject;
        if (strlen($this->vapidPublic) !== 65 || $this->vapidPublic[0] !== "\x04") {
            throw new RuntimeException('VAPID public key must be 65-byte uncompressed P-256');
        }
        if (strlen($this->vapidPrivate) !== 32) {
            throw new RuntimeException('VAPID private key must be 32 bytes');
        }
    }

    /**
     * Generate a fresh VAPID keypair.
     * @return array{public: string, private: string}  base64url-encoded
     */
    public static function generateVapidKeys(): array {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if (!$key) throw new RuntimeException('openssl_pkey_new failed: ' . openssl_error_string());
        $details = openssl_pkey_get_details($key);
        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $public = "\x04" . $x . $y;
        $private = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);
        return [
            'public' => self::b64UrlEncode($public),
            'private' => self::b64UrlEncode($private),
        ];
    }

    /**
     * Send a push to a subscription.
     * @param array $sub ['endpoint'=>..., 'p256dh'=>..., 'auth'=>...]
     * @param string|null $payload  Optional payload (will be encrypted)
     * @param int $ttl  Time to live in seconds
     * @return array{ok: bool, status: int, body: string}
     */
    public function send(array $sub, ?string $payload = null, int $ttl = 86400, string $urgency = 'high'): array {
        $endpoint = $sub['endpoint'];
        $audience = preg_replace('#^(https?://[^/]+).*$#', '$1', $endpoint);

        // Urgency: high → bypassa Doze mode di Android, consegna immediata
        // anche con schermo spento. Senza questo header FCM ritarda la consegna.
        $headers = [
            'TTL: ' . $ttl,
            'Urgency: ' . $urgency,
            'Authorization: ' . $this->vapidAuthHeader($audience),
        ];

        $body = '';
        if ($payload !== null && $payload !== '') {
            [$encryptedBody, $encHeaders] = $this->encryptPayload($payload, $sub['p256dh'], $sub['auth']);
            $body = $encryptedBody;
            foreach ($encHeaders as $k => $v) $headers[] = "$k: $v";
        } else {
            $headers[] = 'Content-Length: 0';
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $resp = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $resp === false ? "curl_error: $err" : (string)$resp,
        ];
    }

    /** Build "vapid t=<jwt>, k=<public>" header */
    private function vapidAuthHeader(string $audience): string {
        $header = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => $this->subject,
        ];
        $headerB64 = self::b64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadB64 = self::b64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signingInput = "$headerB64.$payloadB64";

        $pem = $this->privateKeyToPem();
        $pkey = openssl_pkey_get_private($pem);
        if (!$pkey) throw new RuntimeException('Failed to load VAPID private key: ' . openssl_error_string());
        $derSig = '';
        if (!openssl_sign($signingInput, $derSig, $pkey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('VAPID sign failed: ' . openssl_error_string());
        }
        $rawSig = self::derToRaw($derSig);
        $jwt = $signingInput . '.' . self::b64UrlEncode($rawSig);
        return "vapid t=$jwt, k=" . $this->vapidPublicB64;
    }

    /** Build PEM from raw 32-byte private scalar (P-256) */
    private function privateKeyToPem(): string {
        // Build SEC1 EC PRIVATE KEY ASN.1
        // SEC1: ECPrivateKey ::= SEQUENCE { version 1, privateKey OCTET STRING(32),
        //   parameters [0] ECParameters OPTIONAL, publicKey [1] BIT STRING OPTIONAL }
        $oidP256 = "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07"; // 1.2.840.10045.3.1.7
        $params = "\xA0" . self::asn1Len(strlen($oidP256)) . $oidP256;
        $pubBitString = "\x00" . $this->vapidPublic; // 1 unused-bits byte + key
        $pub = "\x03" . self::asn1Len(strlen($pubBitString)) . $pubBitString;
        $pubTagged = "\xA1" . self::asn1Len(strlen($pub)) . $pub;
        $privKeyOctet = "\x04" . self::asn1Len(strlen($this->vapidPrivate)) . $this->vapidPrivate;
        $version = "\x02\x01\x01";
        $seq = $version . $privKeyOctet . $params . $pubTagged;
        $der = "\x30" . self::asn1Len(strlen($seq)) . $seq;
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64) . "-----END EC PRIVATE KEY-----\n";
    }

    private static function asn1Len(int $len): string {
        if ($len < 128) return chr($len);
        $hex = dechex($len);
        if (strlen($hex) % 2) $hex = '0' . $hex;
        $b = hex2bin($hex);
        return chr(0x80 | strlen($b)) . $b;
    }

    /** Convert DER ECDSA signature to raw R||S (64 bytes for P-256) */
    private static function derToRaw(string $der): string {
        // SEQUENCE { INTEGER r, INTEGER s }
        if (ord($der[0]) !== 0x30) throw new RuntimeException('Bad DER sig');
        $offset = 2;
        if (ord($der[1]) & 0x80) $offset += ord($der[1]) & 0x7F;
        $rLen = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLen);
        $sOffset = $offset + 2 + $rLen;
        $sLen = ord($der[$sOffset + 1]);
        $s = substr($der, $sOffset + 2, $sLen);
        // strip leading 0x00 if added for sign
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        return str_pad($r, 32, "\x00", STR_PAD_LEFT) . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    /** Encrypt payload per RFC 8291 (aes128gcm) */
    private function encryptPayload(string $payload, string $userPublicB64, string $userAuthB64): array {
        $userPublic = self::b64UrlDecode($userPublicB64);
        $userAuth = self::b64UrlDecode($userAuthB64);
        if (strlen($userPublic) !== 65 || $userPublic[0] !== "\x04") {
            throw new RuntimeException('Subscription p256dh must be 65-byte uncompressed');
        }

        // Generate ephemeral P-256 key pair (the "as_" application server keys for this push)
        $asKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if (!$asKey) throw new RuntimeException('Ephemeral key gen failed');
        $asDetails = openssl_pkey_get_details($asKey);
        $asX = str_pad($asDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $asY = str_pad($asDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $asPublic = "\x04" . $asX . $asY;

        // ECDH: derive shared secret with user's public key
        $userPubPem = self::rawPubKeyToPem($userPublic);
        $userPubKey = openssl_pkey_get_public($userPubPem);
        if (!$userPubKey) throw new RuntimeException('User public key parse failed');
        $ecdhSecret = openssl_pkey_derive($userPubKey, $asKey);
        if ($ecdhSecret === false) throw new RuntimeException('ECDH derive failed: ' . openssl_error_string());
        $ecdhSecret = substr($ecdhSecret, 0, 32);

        // HKDF as per RFC 8291
        $prkKey = self::hkdfExtract($userAuth, $ecdhSecret);
        $keyInfo = "WebPush: info\x00" . $userPublic . $asPublic;
        $ikm = self::hkdfExpand($prkKey, $keyInfo, 32);

        $salt = random_bytes(16);
        $prk = self::hkdfExtract($salt, $ikm);
        $cek = self::hkdfExpand($prk, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = self::hkdfExpand($prk, "Content-Encoding: nonce\x00", 12);

        // Plaintext + 0x02 padding delimiter (single-record)
        $plaintext = $payload . "\x02";

        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ciphertext === false) throw new RuntimeException('AES-GCM encrypt failed');

        $encryptedRecord = $ciphertext . $tag;

        // Header per RFC 8188: salt(16) | rs(4 BE) | idlen(1) | keyid
        $rs = strlen($encryptedRecord);
        if ($rs > 4096) $rs = 4096;
        $idlen = 65;
        $body = $salt . pack('N', $rs) . chr($idlen) . $asPublic . $encryptedRecord;

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Encoding' => 'aes128gcm',
            'Content-Length' => strlen($body),
        ];
        return [$body, $headers];
    }

    private static function hkdfExtract(string $salt, string $ikm): string {
        return hash_hmac('sha256', $ikm, $salt, true);
    }

    private static function hkdfExpand(string $prk, string $info, int $length): string {
        $output = '';
        $t = '';
        $i = 1;
        while (strlen($output) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
            $output .= $t;
            $i++;
        }
        return substr($output, 0, $length);
    }

    /** Wrap raw 65-byte uncompressed P-256 pub key in PEM */
    private static function rawPubKeyToPem(string $raw): string {
        $oidEc = "\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01"; // 1.2.840.10045.2.1
        $oidP256 = "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07";
        $algSeq = $oidEc . $oidP256;
        $algSeqWrapped = "\x30" . self::asn1Len(strlen($algSeq)) . $algSeq;
        $bitString = "\x00" . $raw;
        $bsWrapped = "\x03" . self::asn1Len(strlen($bitString)) . $bitString;
        $seq = $algSeqWrapped . $bsWrapped;
        $der = "\x30" . self::asn1Len(strlen($seq)) . $seq;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64) . "-----END PUBLIC KEY-----\n";
    }

    public static function b64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function b64UrlDecode(string $data): string {
        $pad = strlen($data) % 4;
        if ($pad) $data .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
