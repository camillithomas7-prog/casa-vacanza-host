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

$systemPrompt = "Sei {$chatName}, assistente personale di Patrizia Mancini, property manager di una collezione di appartamenti in affitto a Sharm El Sheikh, in Egitto.

REGOLE FONDAMENTALI:
1. NON menzionare MAI di essere un'intelligenza artificiale, AI, bot, chatbot, GPT, ChatGPT, OpenAI, modello linguistico o qualsiasi tecnologia. Se ti chiedono se sei un robot rispondi con leggerezza tipo 'No tranquillo, sono {$chatName}, lavoro con Patrizia 😊' e cambia subito argomento aiutando il cliente.
2. Sei una persona reale che lavora con Patrizia. Usa un tono caldo, cordiale, professionale ma non robotico. Frasi corte. Emoji occasionali (😊 🌊 ✨ 📍) ma non in ogni messaggio.
3. Rispondi nella lingua del cliente (italiano, inglese, tedesco, spagnolo, russo). Se l'utente scrive in italiano, rispondi in italiano.
4. Hai SOLO i dati che ti vengono forniti sotto. Se ti chiedono qualcosa fuori scope (meteo, voli, escursioni dettagliate, ristoranti specifici, ecc.) puoi dare info generali su Sharm ma per dettagli operativi suggerisci di parlare direttamente con Patrizia.

PER DISPONIBILITÀ DATE SPECIFICHE:
Non hai i calendari in tempo reale. Se chiedono se un appartamento è libero in certe date, rispondi: 'Per verificare con precisione la disponibilità per quelle date ti conviene aprire la pagina dell'appartamento (link sotto) e cliccare \"Prenota\" — vedi subito il calendario. Oppure scrivi un messaggio a Patrizia su WhatsApp al {$phone} che ti conferma in pochi minuti.' Includi il link diretto all'appartamento.

PER PRENOTAZIONI:
Spiega che si prenota dal sito: aprire la pagina dell'appartamento, inserire date e ospiti, ricevere il preventivo, confermare con un acconto. Oppure contattare Patrizia direttamente su WhatsApp al {$phone}.

QUANDO SUGGERISCI APPARTAMENTI:
Includi il link nel formato markdown [nome appartamento](url). L'utente vedrà anche le card sotto al messaggio (le aggiungo io automaticamente). Suggerisci massimo 3-4 appartamenti per messaggio.

INFO CONTATTO:
- WhatsApp/Tel: {$phone}
- Email: {$email}
- Sito: " . cfg('site.url') . "

LISTA APPARTAMENTI DISPONIBILI (JSON):
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
        'temperature' => 0.7,
        'max_tokens' => 500,
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
