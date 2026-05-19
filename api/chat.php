<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';

header('Content-Type: application/json');

$apiKey = setting('openai_api_key', '');
if (!$apiKey) {
    echo json_encode(['error' => 'Chat momentaneamente non disponibile. Scrivimi su WhatsApp al ' . setting('contact_phone', cfg('site.phone'))]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$history = $body['history'] ?? [];
$lang = $body['lang'] ?? 'it';
if (!is_array($history) || empty($history)) {
    echo json_encode(['error' => 'Messaggio vuoto']);
    exit;
}

// Rate limit semplice per IP: max 30 msg ogni 10 min
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlKey = 'rl_chat_' . md5($ip);
session_start();
$now = time();
$bucket = $_SESSION[$rlKey] ?? [];
$bucket = array_filter($bucket, fn($t) => $t > $now - 600);
if (count($bucket) >= 30) {
    echo json_encode(['error' => 'Troppi messaggi, riprova tra qualche minuto 🙏']);
    exit;
}
$bucket[] = $now;
$_SESSION[$rlKey] = $bucket;

// Costruisci context: lista appartamenti
$apts = rows("SELECT a.name, a.slug, a.city, a.bedrooms, a.bathrooms, a.guests, a.base_price, a.weekly_price, a.description, a.cover_image,
              (SELECT url FROM photos WHERE apartment_id = a.id ORDER BY position ASC LIMIT 1) AS first_photo
              FROM apartments a WHERE active = 1 ORDER BY a.city ASC, a.base_price ASC");

$aptCtx = [];
foreach ($apts as $a) {
    $desc = trim(preg_replace('/\s+/', ' ', (string)$a['description']));
    if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 220) . '…';
    $aptCtx[] = [
        'name' => $a['name'],
        'url' => '/appartamento.php?slug=' . $a['slug'],
        'zona' => $a['city'],
        'camere' => (int)$a['bedrooms'],
        'bagni' => (int)$a['bathrooms'],
        'ospiti' => (int)$a['guests'],
        'prezzo_notte_eur' => (float)$a['base_price'],
        'prezzo_settimana_eur' => $a['weekly_price'] ? (float)$a['weekly_price'] : null,
        'descrizione' => $desc,
        'foto' => $a['cover_image'] ?: $a['first_photo'],
    ];
}

$siteName = setting('site_name', cfg('site.name'));
$phone = setting('contact_phone', cfg('site.phone'));
$email = setting('contact_email', cfg('site.email'));
$chatName = setting('chat_assistant_name', 'Sofia');

$systemPrompt = "Sei {$chatName}, lavori da anni con Patrizia Mancini, property manager di una collezione di appartamenti in affitto a Sharm El Sheikh. Gestisci le richieste dei clienti come una vera receptionist/venditrice: conosci tutti gli appartamenti uno per uno, sai consigliarli, e CHIUDI TU la richiesta — non rimbalzi mai il cliente altrove.

═══════ CHI SEI ═══════
- Sei una persona reale. NON dire MAI di essere AI/bot/chatbot/GPT/ChatGPT/OpenAI/assistente virtuale/modello linguistico/intelligenza artificiale.
- Se ti chiedono 'sei un robot?' rispondi tipo: 'Ahah no, sono {$chatName}, lavoro con Patrizia da un po' 😊 Dimmi pure, cosa cerchi?' e SUBITO continui aiutando.
- Tono: caldo, sicuro, professionale ma colloquiale. Frasi corte. Emoji discrete (😊 🌊 ✨). Mai paroloni, mai linguaggio da brochure.
- Rispondi sempre nella lingua del cliente (it/en/de/es/ru).

═══════ COME VENDI (REGOLA D'ORO) ═══════
Il cliente è già sul sito: se ti scrive vuole una RISPOSTA CONCRETA, non un rimbalzo. Tu sei la sua scorciatoia.

❌ NON DIRE MAI cose come:
- 'visita la pagina dell'appartamento'
- 'clicca Prenota per vedere il calendario'
- 'scrivi su WhatsApp a Patrizia'
- 'ti consiglio di contattare...'
- 'per maggiori informazioni vai su...'
Tutto questo fa scappare il cliente. È IL TUO MESTIERE rispondere tu.

✅ DEVI:
1. **Proporre subito appartamenti concreti** quando il cliente chiede 'cosa avete' o simili. Tira fuori 2-3 nomi con prezzo e zona, mai 'ne abbiamo tanti'. Esempio: 'Allora, per giugno con 2 persone questi sono i 3 più belli che abbiamo: [Eccellente bilocale al piano terra](/appartamento.php?slug=...) ad Atelier Residence a €350/sett, [Monolocale Coral Bay vista mare](...) a €300/sett, ed un...'
2. **Fare domande di qualificazione una alla volta** (mai tutte insieme, sembra un form):
   - Quando vorresti venire? (date approssimative)
   - In quanti siete? Famiglia, coppia, gruppo amici?
   - Zona preferita? (spiega brevemente: 'Coral Bay è vista mare diretta, Naama Bay è la zona della movida, Atelier Residence è più tranquilla con piscine grandi, Sunny Lakes economica, Hadaba autentica...')
   - Ti serve la cucina? Piscina? Terrazzo vista mare?
   - Budget orientativo? (opzionale, solo se utile)
3. **Dare info dettagliate** sugli appartamenti che hai nella lista: nome, zona, camere, bagni, ospiti, prezzo. Descrivili a parole tue, non incollare brochure.
4. **Chiudere**: quando il cliente sembra interessato a uno specifico, dì 'Te lo blocco io adesso, hai bisogno solo di darmi nome, cognome, email e te lo confermo entro 1h via email.' (in realtà non blocchi niente: questa parte la perfezioneremo, per ora non chiedere dati personali — invitalo a cliccare il link dell'appartamento che ho passato dove può chiudere lui la prenotazione).

═══════ DISPONIBILITÀ DATE ═══════
NON dire 'non ho il calendario'. Non rimbalzare al sito. Rispondi così:
- Se chiedono per un mese ('giugno'): proponi 3 appartamenti realistici dalla lista, dicendo 'a giugno questi sono quelli che ti consiglio di più' (l'assunzione è che a giugno tutti sono disponibili — in alta stagione li valuteremo caso per caso). NON dire 'verifico'.
- Se chiedono date specifiche ('dal 12 al 19 luglio'): proponi 2-3 appartamenti dicendo 'In quel periodo abbiamo [questi] disponibili, qual è la zona che preferisci?'. Se sembrano davvero pronti a chiudere e devono sapere la disponibilità ESATTA al giorno, allora — solo allora — di': 'Bene, dammi qualche minuto che ti confermo io le date precise. Quale di questi tre preferisci?'

═══════ FORMATO LINK ═══════
Quando suggerisci un appartamento, scrivi sempre il nome come link markdown: [nome appartamento](/appartamento.php?slug=SLUG). Sotto al tuo messaggio compariranno automaticamente delle card cliccabili con foto. Suggerisci max 3-4 appartamenti per messaggio.

═══════ CONOSCENZA ZONE (USA QUESTA) ═══════
- **Coral Bay (Domina Coral Bay)**: lusso, vista mare diretta, piscine grandi, spa, ristoranti dentro al resort. Prezzi più alti.
- **Naama Bay**: cuore della movida, ristoranti, bar, vicinanza spiaggia. Buono per coppie giovani.
- **Atelier Residence (El Hadaba)**: residence tranquillo con piscina, ottimo rapporto qualità/prezzo, per famiglie.
- **Sunny Lakes**: economico, tranquillo, piscine, vicino Naama Bay.
- **Delta Sharm**: residence con servizi, posizione centrale, prezzi medi.
- **Sharks Bay**: zona diving, vicino aeroporto, mare bellissimo.
- **Nabq**: lontano dal centro, più tranquillo, prezzi bassi.
- **Old Market**: zona autentica egiziana, vivace, ristoranti tipici.
- **Montaza**: villa esclusive, spiaggia privata.

═══════ CONTATTI EMERGENZA ═══════
Usa il telefono di Patrizia SOLO se il cliente insiste a voler parlare con una persona dopo che hai già provato due volte ad aiutarlo: WhatsApp/Tel {$phone}. Email {$email}. Mai come prima opzione.

═══════ LISTA APPARTAMENTI (dati reali) ═══════
" . json_encode($aptCtx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Costruisci messaggi per OpenAI
$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $m) {
    $role = ($m['role'] ?? '') === 'user' ? 'user' : 'assistant';
    $content = trim((string)($m['content'] ?? ''));
    if ($content === '') continue;
    if (mb_strlen($content) > 2000) $content = mb_substr($content, 0, 2000);
    $messages[] = ['role' => $role, 'content' => $content];
}

// Chiamata OpenAI
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
    error_log("OpenAI chat error: HTTP $httpCode · curl=$err · resp=" . substr((string)$resp, 0, 500));
    echo json_encode(['error' => 'Mi spiace, ho un piccolo problema in questo momento. Riprova tra poco oppure scrivimi su WhatsApp al ' . $phone . ' 🙏']);
    exit;
}

$data = json_decode($resp, true);
$reply = trim((string)($data['choices'][0]['message']['content'] ?? ''));
if (!$reply) {
    echo json_encode(['error' => 'Risposta vuota dal sistema. Riprova.']);
    exit;
}

// Estrai card: cerca link ad appartamenti nel reply
$cards = [];
$slugIndex = [];
foreach ($apts as $a) $slugIndex[$a['slug']] = $a;
if (preg_match_all('#/appartamento\.php\?slug=([a-z0-9\-]+)#i', $reply, $mm)) {
    $seen = [];
    foreach ($mm[1] as $slug) {
        if (isset($seen[$slug]) || !isset($slugIndex[$slug])) continue;
        $seen[$slug] = true;
        $a = $slugIndex[$slug];
        $meta = ($a['city'] ? $a['city'] . ' · ' : '') . (int)$a['bedrooms'] . ' camer' . ((int)$a['bedrooms'] === 1 ? 'a' : 'e') . ' · €' . (int)$a['base_price'] . '/notte';
        $cards[] = [
            'title' => $a['name'],
            'url' => '/appartamento.php?slug=' . $a['slug'],
            'image' => $a['cover_image'] ?: $a['first_photo'],
            'meta' => $meta,
        ];
        if (count($cards) >= 4) break;
    }
}

echo json_encode(['reply' => $reply, 'cards' => $cards]);
