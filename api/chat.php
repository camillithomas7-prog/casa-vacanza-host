<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');

// ──────── Schema idempotente per chat ────────
try {
    db()->exec("CREATE TABLE IF NOT EXISTS chat_conversations (
      id VARCHAR(32) PRIMARY KEY,
      session_id VARCHAR(64) NOT NULL UNIQUE,
      customer_name VARCHAR(120) DEFAULT '',
      customer_phone VARCHAR(60) DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      escalated_at DATETIME NULL,
      escalation_reason TEXT,
      handled_by_admin TINYINT(1) NOT NULL DEFAULT 0,
      message_count INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_status (status),
      INDEX idx_updated (updated_at DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS chat_messages (
      id VARCHAR(32) PRIMARY KEY,
      conversation_id VARCHAR(32) NOT NULL,
      role VARCHAR(20) NOT NULL,
      content TEXT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
      INDEX idx_conv (conversation_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) { error_log('chat schema: ' . $e->getMessage()); }

$apiKey = setting('openai_api_key', '');
if (!$apiKey) {
    echo json_encode(['error' => 'Chat non configurata. Riprova più tardi.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$history = $body['history'] ?? [];
$sessionId = trim((string)($body['session_id'] ?? ''));
$lang = $body['lang'] ?? 'it';
if (!is_array($history) || empty($history) || !$sessionId || !preg_match('/^[a-zA-Z0-9\-_]{8,64}$/', $sessionId)) {
    echo json_encode(['error' => 'Richiesta non valida']);
    exit;
}

// Rate limit per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (session_status() === PHP_SESSION_NONE) session_start();
$rlKey = 'rl_chat_' . md5($ip);
$now = time();
$bucket = $_SESSION[$rlKey] ?? [];
$bucket = array_filter($bucket, fn($t) => $t > $now - 600);
if (count($bucket) >= 30) {
    echo json_encode(['error' => 'Troppi messaggi, riprova tra qualche minuto 🙏']);
    exit;
}
$bucket[] = $now;
$_SESSION[$rlKey] = $bucket;

// Trova/crea conversation
$conv = row('SELECT * FROM chat_conversations WHERE session_id = ?', [$sessionId]);
if (!$conv) {
    $convId = newId();
    q('INSERT INTO chat_conversations (id, session_id, status) VALUES (?, ?, ?)', [$convId, $sessionId, 'active']);
    $conv = ['id' => $convId, 'status' => 'active', 'customer_name' => '', 'customer_phone' => ''];
}

// Salva messaggio user più recente
$lastUserMsg = '';
foreach (array_reverse($history) as $m) {
    if (($m['role'] ?? '') === 'user') { $lastUserMsg = trim((string)($m['content'] ?? '')); break; }
}
if ($lastUserMsg !== '') {
    q('INSERT INTO chat_messages (id, conversation_id, role, content) VALUES (?, ?, ?, ?)',
        [newId(), $conv['id'], 'user', mb_substr($lastUserMsg, 0, 4000)]);
    q('UPDATE chat_conversations SET message_count = message_count + 1, updated_at = NOW() WHERE id = ?', [$conv['id']]);
}

// Context appartamenti
$apts = rows("SELECT a.name, a.slug, a.city, a.bedrooms, a.bathrooms, a.guests, a.base_price, a.weekly_price, a.description, a.cover_image,
              (SELECT url FROM photos WHERE apartment_id = a.id ORDER BY position ASC LIMIT 1) AS first_photo
              FROM apartments a WHERE active = 1 ORDER BY a.city ASC, a.base_price ASC");
$aptCtx = [];
foreach ($apts as $a) {
    $desc = trim(preg_replace('/\s+/', ' ', (string)$a['description']));
    if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 220) . '…';
    // Pulisci riferimenti al brand Domina anche nel context
    $cityClean = preg_replace('/\s*\(?\s*domina[^)]*\)?\s*/i', '', (string)$a['city']);
    $cityClean = trim($cityClean) ?: $a['city'];
    $descClean = preg_replace('/\bdomina\b/i', 'resort', $desc);
    $nameClean = preg_replace('/\bdomina\b/i', '', $a['name']);
    $nameClean = preg_replace('/\s+/', ' ', $nameClean);
    $aptCtx[] = [
        'name' => trim($nameClean),
        'url' => '/appartamento.php?slug=' . $a['slug'],
        'zona' => $cityClean,
        'camere' => (int)$a['bedrooms'],
        'bagni' => (int)$a['bathrooms'],
        'ospiti' => (int)$a['guests'],
        'prezzo_notte_eur' => (float)$a['base_price'],
        'prezzo_settimana_eur' => $a['weekly_price'] ? (float)$a['weekly_price'] : null,
        'descrizione' => $descClean,
        'foto' => $a['cover_image'] ?: $a['first_photo'],
    ];
}

$siteName = setting('site_name', cfg('site.name'));
$phone = setting('contact_phone', cfg('site.phone'));
$email = setting('contact_email', cfg('site.email'));
$chatName = setting('chat_assistant_name', 'Sofia');
$hasContact = !empty($conv['customer_phone']);

$systemPrompt = "Sei {$chatName}, lavori da anni con Patrizia Mancini, property manager di una collezione di appartamenti in affitto a Sharm El Sheikh. Sei una vera receptionist/venditrice: conosci ogni appartamento, sai consigliare, e CHIUDI TU le richieste — non rimbalzi mai il cliente altrove.

═══════ CHI SEI ═══════
- Sei una persona reale. NON dire MAI di essere AI/bot/chatbot/GPT/ChatGPT/OpenAI/assistente virtuale/modello linguistico/intelligenza artificiale.
- Se ti chiedono 'sei un robot?' rispondi tipo: 'Ahah no, sono {$chatName}, lavoro con Patrizia da un po' 😊 Dimmi, cosa cerchi?' e CONTINUI subito ad aiutare.
- Tono: caldo, sicuro, professionale ma colloquiale. Frasi corte. Emoji discrete (😊 🌊 ✨). Mai paroloni.
- Rispondi sempre nella lingua del cliente (it/en/de/es/ru).

═══════ REGOLA SUI NOMI BRAND ═══════ (IMPORTANTISSIMA)
- **NON scrivere MAI la parola 'Domina'** nelle tue risposte. È un marchio che non possiamo usare nei materiali pubblici.
- Se il CLIENTE dice 'Domina', tu CAPISCI che si riferisce alla zona di **Coral Bay** (il resort Domina Coral Bay è dentro Coral Bay). Tu nella risposta scrivi solo 'Coral Bay'.
- Esempio:
  - Cliente: 'Avete qualcosa al Domina?'
  - Tu: 'Sì, a Coral Bay abbiamo diversi appartamenti. Te ne consiglio 2-3 bellissimi: [...]'
- Se nei dati di un appartamento c'è scritto 'Domina', tu nel testo riscrivilo come 'Coral Bay' o ometti il dettaglio brand.

═══════ COME VENDI ═══════
Il cliente è già sul sito: se ti scrive vuole UNA RISPOSTA SUBITO. Tu sei la sua scorciatoia.

❌ NON DIRE MAI:
- 'visita la pagina dell'appartamento'
- 'clicca Prenota per vedere il calendario'
- 'scrivi su WhatsApp a Patrizia'
- 'ti consiglio di contattare...'
- 'per maggiori informazioni vai su...'

✅ DEVI:
1. **Proporre subito appartamenti concreti** con nome (link markdown), zona, camere, prezzo. Mai 'ne abbiamo tanti, dipende'.
2. **Fare domande di qualificazione UNA PER VOLTA** (mai tutte insieme): date → quanti siete → zona preferita → cucina/piscina → budget.
3. **Conoscere le zone**: Coral Bay (lusso vista mare, piscine resort), Naama Bay (movida, bar, ristoranti), Atelier Residence/Hadaba (tranquillo, piscina, family), Sunny Lakes (economico, piscine), Delta Sharm (medio centrale), Sharks Bay (diving, vicino aeroporto), Nabq (tranquillo, lontano), Old Market (autentico), Montaza (villa esclusive).
4. **Disponibilità date**: NON dire 'non ho il calendario'. Proponi gli appartamenti dicendo 'in quel periodo questi sono i 3 che ti consiglio'. Solo se il cliente è già pronto a chiudere su uno specifico, dì che gli confermi la data esatta in pochi minuti.

═══════ ESCALATION A UMANO ═══════
Se ti viene fatta una domanda CHE NON SAI rispondere coi dati che hai (es. richieste molto specifiche su un servizio extra, su un dettaglio operativo non documentato, condizioni complesse di pagamento, domanda fuori dal tuo scope, conferma di disponibilità in alta stagione su date strette), NON inventare. Usa questa procedura:

1. Rispondi in modo professionale tipo: 'Su questo specifico punto ti dico la verità: preferisco verificare con il mio collega che segue questa parte per non darti un'informazione approssimativa. Ti rispondo io in pochi minuti — per non perderti, mi lasci il tuo nome e numero di telefono? Ti scrivo io su WhatsApp con la risposta precisa.'
2. ALLA FINE della tua risposta aggiungi su una nuova riga ESATTAMENTE questo marker (l'utente non lo vedrà, lo userà il sistema): `[ESCALATE: motivo breve della domanda]`. Esempio: `[ESCALATE: chiede se ammettiamo cani nell'appartamento Atelier 2]`

═══════ RACCOLTA CONTATTO ═══════
" . ($hasContact ? "Il contatto del cliente è già stato salvato (nome: {$conv['customer_name']}, tel: {$conv['customer_phone']}). NON richiederlo di nuovo." : "Quando il cliente ti fornisce un numero di telefono SEGUI QUESTA PROCEDURA SCRUPOLOSAMENTE:

A) **VERIFICA IL PREFISSO INTERNAZIONALE**
Per richiamarlo su WhatsApp serve il prefisso internazionale (es. +39 per Italia, +49 Germania, +44 UK, +34 Spagna, +33 Francia, +7 Russia, +1 USA/Canada, +20 Egitto, ecc).
- Se il numero CONTIENE già il prefisso (inizia con + o con 00 o ha 11+ cifre tipo 39333...): OK, procedi al punto B.
- Se il numero è SOLO il numero locale (es. '3889365986' o '348 1234567', tipico italiano di 9-10 cifre senza +39): NON SALVARLO ancora. Chiedi gentilmente: 'Grazie! Solo un dettaglio per il WhatsApp: da quale Paese mi scrivi? Così aggiungo il prefisso corretto.' oppure 'Mi confermi il prefisso internazionale? (es. +39 se sei dall'Italia) Così te lo memorizzo giusto.'
- Se non hai NEMMENO il nome, chiedi anche il nome insieme al prefisso.

B) **SALVATAGGIO** (solo quando hai NOME + PREFISSO + NUMERO):
1. Conferma con calore: 'Perfetto {nome}, ti ho segnato! Ti scrivo io su WhatsApp entro pochi minuti con la risposta precisa 😊'
2. ALLA FINE della tua risposta, su una nuova riga, scrivi ESATTAMENTE questo marker (non lo vede l'utente):
`[CONTATTO: nome=Mario Rossi | tel=+39 333 1234567 | motivo=motivo breve]`
Il telefono nel marker DEVE iniziare con '+' seguito dal prefisso paese.

NOTA: se il cliente è italiano e te lo conferma esplicitamente ('sono da Roma', 'scrivo dall'Italia', ecc.) puoi assumere +39 senza richiedere ulteriore conferma.") . "

═══════ FORMATO LINK ═══════
Quando suggerisci un appartamento, scrivi il nome come link markdown: [nome](/appartamento.php?slug=SLUG). Sotto al tuo messaggio appariranno automaticamente card con foto. Max 4 appartamenti per messaggio.

═══════ CONTATTI EMERGENZA ═══════
SOLO se il cliente insiste a voler parlare con una persona DOPO che hai provato due volte: WhatsApp {$phone}.

═══════ LISTA APPARTAMENTI ═══════
" . json_encode($aptCtx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $m) {
    $role = ($m['role'] ?? '') === 'user' ? 'user' : 'assistant';
    $content = trim((string)($m['content'] ?? ''));
    if ($content === '') continue;
    if (mb_strlen($content) > 2000) $content = mb_substr($content, 0, 2000);
    $messages[] = ['role' => $role, 'content' => $content];
}

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => setting('openai_model', 'gpt-4o-mini'),
        'messages' => $messages,
        'temperature' => 0.85,
        'max_tokens' => 700,
    ]),
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err || $httpCode !== 200) {
    error_log("OpenAI error: HTTP $httpCode · $err · " . substr((string)$resp, 0, 300));
    echo json_encode(['error' => 'Mi spiace, ho un piccolo problema in questo momento. Riprova tra poco. 🙏']);
    exit;
}

$data = json_decode($resp, true);
$reply = trim((string)($data['choices'][0]['message']['content'] ?? ''));
if (!$reply) { echo json_encode(['error' => 'Risposta vuota.']); exit; }

// Parsing marker
$escalated = false;
$escalationReason = '';
$contactName = '';
$contactPhone = '';
$contactReason = '';

if (preg_match('/\[ESCALATE:\s*(.+?)\]/s', $reply, $m)) {
    $escalated = true;
    $escalationReason = trim($m[1]);
    $reply = trim(str_replace($m[0], '', $reply));
}
if (preg_match('/\[CONTATTO:\s*nome=([^|]+?)\s*\|\s*tel=([^|]+?)\s*\|\s*motivo=(.+?)\]/s', $reply, $m)) {
    $contactName = trim($m[1]);
    $contactPhone = trim($m[2]);
    $contactReason = trim($m[3]);
    $reply = trim(str_replace($m[0], '', $reply));
    // Validazione: il telefono deve avere prefisso internazionale (+ seguito da 1-3 cifre)
    if (!preg_match('/^\+\d{1,4}[\s\-]?\d{6,}/', $contactPhone)) {
        // Numero senza prefisso: non lo salviamo come "completo", non escaliamo per WhatsApp
        $contactPhone = '';
    }
}

// Aggiorna conversation se contatto raccolto
if ($contactName || $contactPhone) {
    q('UPDATE chat_conversations SET customer_name = ?, customer_phone = ?, escalation_reason = COALESCE(NULLIF(escalation_reason,""), ?), updated_at = NOW() WHERE id = ?',
        [$contactName, $contactPhone, $contactReason, $conv['id']]);
}

// Escalation
if ($escalated || ($contactName && $contactPhone)) {
    $reason = $escalationReason ?: $contactReason;
    q('UPDATE chat_conversations SET status = "escalated", escalated_at = COALESCE(escalated_at, NOW()), escalation_reason = COALESCE(NULLIF(escalation_reason,""), ?) WHERE id = ?',
        [$reason, $conv['id']]);

    // Notifica admin + push (web/iOS PWA)
    try {
        $titleN = $contactPhone
            ? '📞 Nuovo contatto chat: ' . ($contactName ?: 'cliente')
            : '⚠ Chat: serve risposta umana';
        $bodyN = ($contactPhone ? 'Tel: ' . $contactPhone . ' · ' : '') . ($reason ?: 'Richiesta che Sofia non può gestire');
        notify('chat_escalation', $titleN, mb_substr($bodyN, 0, 240), '/admin/chat.php?id=' . $conv['id']);
    } catch (Throwable $e) { error_log('chat notify push: ' . $e->getMessage()); }
}

// Salva risposta assistant (pulita)
q('INSERT INTO chat_messages (id, conversation_id, role, content) VALUES (?, ?, ?, ?)',
    [newId(), $conv['id'], 'assistant', mb_substr($reply, 0, 4000)]);

// Estrai card dai link
$cards = [];
$slugIndex = [];
foreach ($apts as $a) $slugIndex[$a['slug']] = $a;
if (preg_match_all('#/appartamento\.php\?slug=([a-z0-9\-]+)#i', $reply, $mm)) {
    $seen = [];
    foreach ($mm[1] as $slug) {
        if (isset($seen[$slug]) || !isset($slugIndex[$slug])) continue;
        $seen[$slug] = true;
        $a = $slugIndex[$slug];
        $cityClean = preg_replace('/\s*\(?\s*domina[^)]*\)?\s*/i', '', (string)$a['city']);
        $cityClean = trim($cityClean) ?: $a['city'];
        $meta = ($cityClean ? $cityClean . ' · ' : '') . (int)$a['bedrooms'] . ' camer' . ((int)$a['bedrooms'] === 1 ? 'a' : 'e') . ' · €' . (int)$a['base_price'] . '/notte';
        $cards[] = [
            'title' => preg_replace('/\s+/', ' ', preg_replace('/\bdomina\b/i', '', $a['name'])),
            'url' => '/appartamento.php?slug=' . $a['slug'],
            'image' => $a['cover_image'] ?: $a['first_photo'],
            'meta' => $meta,
        ];
        if (count($cards) >= 4) break;
    }
}

echo json_encode(['reply' => $reply, 'cards' => $cards, 'escalated' => $escalated]);
