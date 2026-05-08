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

/**
 * Restituisce traduzioni di soggetto/corpo per un template_key in tutte le lingue supportate.
 * Le variabili {{nome}} {{appartamento}} ecc. restano invariate, vengono sostituite a runtime.
 */
function templateTranslations(): array {
    return [
        'confirmation' => [
            'it' => ['subject' => 'Conferma prenotazione {{appartamento}}',
                     'body' => "Ciao {{nome}}! 🏡\n\nLa tua prenotazione presso {{appartamento}} è confermata.\n\n📅 Check-in: {{checkin}} dalle {{ora_checkin}}\n📅 Check-out: {{checkout}} entro le {{ora_checkout}}\n👥 Ospiti: {{ospiti}}\n💶 Totale: {{totale}}\n💳 Acconto ricevuto: {{acconto}}\n💰 Saldo da versare: {{saldo}}\n\nCodice prenotazione: {{codice}}\n\nPer qualsiasi cosa rispondi pure a questo messaggio.\nA presto!"],
            'en' => ['subject' => 'Booking confirmation · {{appartamento}}',
                     'body' => "Hi {{nome}}! 🏡\n\nYour booking at {{appartamento}} is confirmed.\n\n📅 Check-in: {{checkin}} from {{ora_checkin}}\n📅 Check-out: {{checkout}} by {{ora_checkout}}\n👥 Guests: {{ospiti}}\n💶 Total: {{totale}}\n💳 Deposit received: {{acconto}}\n💰 Balance due: {{saldo}}\n\nBooking code: {{codice}}\n\nFeel free to reply here for anything.\nSee you soon!"],
            'ru' => ['subject' => 'Подтверждение бронирования · {{appartamento}}',
                     'body' => "Здравствуйте, {{nome}}! 🏡\n\nВаше бронирование в {{appartamento}} подтверждено.\n\n📅 Заезд: {{checkin}} с {{ora_checkin}}\n📅 Выезд: {{checkout}} до {{ora_checkout}}\n👥 Гости: {{ospiti}}\n💶 Итого: {{totale}}\n💳 Полученный задаток: {{acconto}}\n💰 К доплате: {{saldo}}\n\nКод брони: {{codice}}\n\nПо любым вопросам пишите сюда.\nДо скорой встречи!"],
            'es' => ['subject' => 'Confirmación de reserva · {{appartamento}}',
                     'body' => "¡Hola {{nome}}! 🏡\n\nTu reserva en {{appartamento}} está confirmada.\n\n📅 Check-in: {{checkin}} a partir de las {{ora_checkin}}\n📅 Check-out: {{checkout}} antes de las {{ora_checkout}}\n👥 Huéspedes: {{ospiti}}\n💶 Total: {{totale}}\n💳 Anticipo recibido: {{acconto}}\n💰 Saldo a pagar: {{saldo}}\n\nCódigo de reserva: {{codice}}\n\nPara cualquier cosa, responde a este mensaje.\n¡Hasta pronto!"],
            'de' => ['subject' => 'Buchungsbestätigung · {{appartamento}}',
                     'body' => "Hallo {{nome}}! 🏡\n\nIhre Buchung im {{appartamento}} ist bestätigt.\n\n📅 Check-in: {{checkin}} ab {{ora_checkin}}\n📅 Check-out: {{checkout}} bis {{ora_checkout}}\n👥 Gäste: {{ospiti}}\n💶 Gesamt: {{totale}}\n💳 Anzahlung erhalten: {{acconto}}\n💰 Restbetrag: {{saldo}}\n\nBuchungscode: {{codice}}\n\nBei Fragen einfach antworten.\nBis bald!"],
        ],
        'docs_request' => [
            'it' => ['subject' => 'Documenti per il check-in',
                     'body' => "Ciao {{nome}}, per completare la registrazione abbiamo bisogno di una foto del documento di tutti gli ospiti.\n\nPuoi inviarli direttamente qui in chat. Grazie! 🙏"],
            'en' => ['subject' => 'Documents for check-in',
                     'body' => "Hi {{nome}}, to complete registration we need a photo of the ID of every guest.\n\nYou can send them right here in this chat. Thank you! 🙏"],
            'ru' => ['subject' => 'Документы для заселения',
                     'body' => "Здравствуйте, {{nome}}! Для завершения регистрации нужны фото документов всех гостей.\n\nМожете прислать их прямо в этот чат. Спасибо! 🙏"],
            'es' => ['subject' => 'Documentos para el check-in',
                     'body' => "Hola {{nome}}, para completar el registro necesitamos una foto del documento de cada huésped.\n\nPuedes enviarlos directamente aquí en el chat. ¡Gracias! 🙏"],
            'de' => ['subject' => 'Dokumente für den Check-in',
                     'body' => "Hallo {{nome}}, um die Anmeldung abzuschließen, brauchen wir ein Foto des Ausweises jedes Gastes.\n\nSie können sie direkt hier im Chat schicken. Danke! 🙏"],
        ],
        'checkin_instructions' => [
            'it' => ['subject' => 'Istruzioni check-in {{appartamento}}',
                     'body' => "Ciao {{nome}}, ecco le istruzioni per il tuo arrivo:\n\n📍 Indirizzo: {{indirizzo}}\n🔑 Check-in self-service dalle {{ora_checkin}}\n📲 Codice cassetta chiavi: te lo invieremo il giorno dell'arrivo\n\nA presto!"],
            'en' => ['subject' => 'Check-in instructions · {{appartamento}}',
                     'body' => "Hi {{nome}}, here are the instructions for your arrival:\n\n📍 Address: {{indirizzo}}\n🔑 Self check-in from {{ora_checkin}}\n📲 Key box code: we'll send it to you on the day of arrival\n\nSee you soon!"],
            'ru' => ['subject' => 'Инструкция по заселению · {{appartamento}}',
                     'body' => "Здравствуйте, {{nome}}! Инструкция по заезду:\n\n📍 Адрес: {{indirizzo}}\n🔑 Самостоятельное заселение с {{ora_checkin}}\n📲 Код от сейфа с ключами пришлём в день приезда\n\nДо скорой встречи!"],
            'es' => ['subject' => 'Instrucciones de check-in · {{appartamento}}',
                     'body' => "Hola {{nome}}, aquí tienes las instrucciones para tu llegada:\n\n📍 Dirección: {{indirizzo}}\n🔑 Check-in self-service a partir de las {{ora_checkin}}\n📲 Código de la caja de llaves: te lo enviaremos el día de la llegada\n\n¡Hasta pronto!"],
            'de' => ['subject' => 'Check-in-Anweisungen · {{appartamento}}',
                     'body' => "Hallo {{nome}}, hier die Anweisungen für Ihre Ankunft:\n\n📍 Adresse: {{indirizzo}}\n🔑 Self Check-in ab {{ora_checkin}}\n📲 Den Code der Schlüsselbox senden wir am Anreisetag\n\nBis bald!"],
        ],
        'balance_reminder' => [
            'it' => ['subject' => 'Promemoria saldo {{appartamento}}',
                     'body' => "Ciao {{nome}}, ti ricordiamo il saldo di {{saldo}} per la tua prenotazione {{codice}} dal {{checkin}} al {{checkout}}.\nGrazie!"],
            'en' => ['subject' => 'Balance reminder · {{appartamento}}',
                     'body' => "Hi {{nome}}, just a reminder of the balance of {{saldo}} for your booking {{codice}} from {{checkin}} to {{checkout}}.\nThank you!"],
            'ru' => ['subject' => 'Напоминание о доплате · {{appartamento}}',
                     'body' => "Здравствуйте, {{nome}}! Напоминаем об оставшейся сумме {{saldo}} по бронированию {{codice}} с {{checkin}} по {{checkout}}.\nСпасибо!"],
            'es' => ['subject' => 'Recordatorio de saldo · {{appartamento}}',
                     'body' => "Hola {{nome}}, te recordamos el saldo de {{saldo}} para tu reserva {{codice}} del {{checkin}} al {{checkout}}.\n¡Gracias!"],
            'de' => ['subject' => 'Erinnerung an die Restzahlung · {{appartamento}}',
                     'body' => "Hallo {{nome}}, kurze Erinnerung an den Restbetrag von {{saldo}} für Ihre Buchung {{codice}} vom {{checkin}} bis {{checkout}}.\nVielen Dank!"],
        ],
        'checkout' => [
            'it' => ['subject' => 'Grazie {{nome}}!',
                     'body' => "Ciao {{nome}}, grazie per aver scelto {{appartamento}}! 💛\nSperiamo che il soggiorno sia stato fantastico. Ci farebbe piacere ricevere una tua recensione.\nA presto!"],
            'en' => ['subject' => 'Thank you {{nome}}!',
                     'body' => "Hi {{nome}}, thank you for choosing {{appartamento}}! 💛\nWe hope you had a wonderful stay. A short review would mean a lot to us.\nSee you next time!"],
            'ru' => ['subject' => 'Спасибо, {{nome}}!',
                     'body' => "Здравствуйте, {{nome}}! Спасибо, что выбрали {{appartamento}}! 💛\nНадеемся, проживание было замечательным. Будем благодарны за короткий отзыв.\nДо новых встреч!"],
            'es' => ['subject' => '¡Gracias {{nome}}!',
                     'body' => "Hola {{nome}}, ¡gracias por elegir {{appartamento}}! 💛\nEsperamos que la estancia haya sido fantástica. Una breve reseña nos haría mucha ilusión.\n¡Hasta pronto!"],
            'de' => ['subject' => 'Danke {{nome}}!',
                     'body' => "Hallo {{nome}}, danke, dass Sie sich für {{appartamento}} entschieden haben! 💛\nWir hoffen, der Aufenthalt war traumhaft. Eine kurze Bewertung würde uns sehr freuen.\nBis zum nächsten Mal!"],
        ],
    ];
}

function defaultTemplates(): array {
    $tr = templateTranslations();
    return [
        ['key' => 'confirmation', 'name' => 'Conferma prenotazione', 'channel' => 'both',
         'subject' => $tr['confirmation']['it']['subject'],
         'body' => $tr['confirmation']['it']['body']],
        ['key' => 'docs_request', 'name' => 'Richiesta documenti', 'channel' => 'whatsapp',
         'subject' => $tr['docs_request']['it']['subject'],
         'body' => $tr['docs_request']['it']['body']],
        ['key' => 'checkin_instructions', 'name' => 'Istruzioni check-in', 'channel' => 'both',
         'subject' => $tr['checkin_instructions']['it']['subject'],
         'body' => $tr['checkin_instructions']['it']['body']],
        ['key' => 'balance_reminder', 'name' => 'Reminder saldo', 'channel' => 'both',
         'subject' => $tr['balance_reminder']['it']['subject'],
         'body' => $tr['balance_reminder']['it']['body']],
        ['key' => 'checkout', 'name' => 'Check-out', 'channel' => 'both',
         'subject' => $tr['checkout']['it']['subject'],
         'body' => $tr['checkout']['it']['body']],
    ];
}
