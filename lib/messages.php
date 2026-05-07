<?php
function renderTemplate(string $body, array $vars): string {
    return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function($m) use ($vars) {
        return $vars[$m[1]] ?? '';
    }, $body);
}

function whatsappLink(string $phone, string $message): string {
    $p = preg_replace('/\D/', '', $phone);
    return 'https://wa.me/' . $p . '?text=' . rawurlencode($message);
}

function mailtoLink(string $email, string $subject, string $body): string {
    return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
}

function defaultTemplates(): array {
    return [
        ['key' => 'confirmation', 'name' => 'Conferma prenotazione', 'channel' => 'both',
         'subject' => 'Conferma prenotazione {{appartamento}}',
         'body' => "Ciao {{nome}}! 🏡\n\nLa tua prenotazione presso {{appartamento}} è confermata.\n\n📅 Check-in: {{checkin}} dalle {{ora_checkin}}\n📅 Check-out: {{checkout}} entro le {{ora_checkout}}\n👥 Ospiti: {{ospiti}}\n💶 Totale: {{totale}}\n💳 Acconto ricevuto: {{acconto}}\n💰 Saldo da versare: {{saldo}}\n\nCodice prenotazione: {{codice}}\n\nPer qualsiasi cosa rispondi pure a questo messaggio.\nA presto!"],

        ['key' => 'docs_request', 'name' => 'Richiesta documenti', 'channel' => 'whatsapp',
         'subject' => 'Documenti per il check-in',
         'body' => "Ciao {{nome}}, per completare la registrazione abbiamo bisogno di una foto del documento di tutti gli ospiti.\n\nPuoi inviarli direttamente qui in chat. Grazie! 🙏"],

        ['key' => 'checkin_instructions', 'name' => 'Istruzioni check-in', 'channel' => 'both',
         'subject' => 'Istruzioni check-in {{appartamento}}',
         'body' => "Ciao {{nome}}, ecco le istruzioni per il tuo arrivo:\n\n📍 Indirizzo: {{indirizzo}}\n🔑 Check-in self-service dalle {{ora_checkin}}\n📲 Codice cassetta chiavi: te lo invieremo il giorno dell'arrivo\n\nA presto!"],

        ['key' => 'balance_reminder', 'name' => 'Reminder saldo', 'channel' => 'both',
         'subject' => 'Promemoria saldo {{appartamento}}',
         'body' => "Ciao {{nome}}, ti ricordiamo il saldo di {{saldo}} per la tua prenotazione {{codice}} dal {{checkin}} al {{checkout}}.\nGrazie!"],

        ['key' => 'checkout', 'name' => 'Check-out', 'channel' => 'both',
         'subject' => 'Grazie {{nome}}!',
         'body' => "Ciao {{nome}}, grazie per aver scelto {{appartamento}}! 💛\nSperiamo che il soggiorno sia stato fantastico. Ci farebbe piacere ricevere una tua recensione.\nA presto!"],
    ];
}
