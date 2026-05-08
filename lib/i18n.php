<?php
$LANGUAGES = [
    'it' => ['code' => 'it', 'name' => 'Italiano', 'native' => 'Italiano', 'flag' => '🇮🇹'],
    'en' => ['code' => 'en', 'name' => 'English',  'native' => 'English',  'flag' => '🇬🇧'],
    'ru' => ['code' => 'ru', 'name' => 'Russian',  'native' => 'Русский',  'flag' => '🇷🇺'],
    'es' => ['code' => 'es', 'name' => 'Spanish',  'native' => 'Español',  'flag' => '🇪🇸'],
    'de' => ['code' => 'de', 'name' => 'German',   'native' => 'Deutsch',  'flag' => '🇩🇪'],
];

function currentLang(): string {
    static $lang = null;
    if ($lang !== null) return $lang;
    global $LANGUAGES;
    if (!empty($_GET['lang']) && isset($LANGUAGES[$_GET['lang']])) {
        $lang = $_GET['lang'];
        if (!headers_sent()) {
            setcookie('cv_lang', $lang, time() + 86400 * 365, '/', '', false, false);
        }
    } elseif (!empty($_COOKIE['cv_lang']) && isset($LANGUAGES[$_COOKIE['cv_lang']])) {
        $lang = $_COOKIE['cv_lang'];
    } else {
        $lang = 'it';
    }
    return $lang;
}

function langSwitch(string $lang): string {
    $url = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $params = $_GET;
    $params['lang'] = $lang;
    return $url . '?' . http_build_query($params);
}

$TRANSLATIONS = [

// ========== NAVIGATION ==========
'nav.home' => [
    'it' => 'Home', 'en' => 'Home', 'ru' => 'Главная', 'es' => 'Inicio', 'de' => 'Start',
],
'nav.apartments' => [
    'it' => 'Appartamenti', 'en' => 'Apartments', 'ru' => 'Апартаменты', 'es' => 'Apartamentos', 'de' => 'Apartments',
],
'nav.rentals' => [
    'it' => 'Noleggi', 'en' => 'Rentals', 'ru' => 'Аренда транспорта', 'es' => 'Alquileres', 'de' => 'Mietfahrzeuge',
],
'nav.excursions' => [
    'it' => 'Escursioni', 'en' => 'Tours', 'ru' => 'Экскурсии', 'es' => 'Excursiones', 'de' => 'Ausflüge',
],
'nav.transfer' => [
    'it' => 'Transfer', 'en' => 'Transfers', 'ru' => 'Трансфер', 'es' => 'Traslados', 'de' => 'Transfer',
],
'nav.contact' => [
    'it' => 'Contatti', 'en' => 'Contact', 'ru' => 'Контакты', 'es' => 'Contacto', 'de' => 'Kontakt',
],
'nav.admin' => [
    'it' => 'Area admin', 'en' => 'Admin', 'ru' => 'Админ', 'es' => 'Admin', 'de' => 'Admin',
],
'cta.book_now' => [
    'it' => 'Prenota ora', 'en' => 'Book now', 'ru' => 'Забронировать', 'es' => 'Reservar ahora', 'de' => 'Jetzt buchen',
],

// ========== COMMON ==========
'common.search' => [
    'it' => 'Cerca', 'en' => 'Search', 'ru' => 'Найти', 'es' => 'Buscar', 'de' => 'Suchen',
],
'common.see_all' => [
    'it' => 'Vedi tutto', 'en' => 'See all', 'ru' => 'Смотреть все', 'es' => 'Ver todo', 'de' => 'Alle ansehen',
],
'common.see_all_arrow' => [
    'it' => 'Vedi tutte →', 'en' => 'See all →', 'ru' => 'Смотреть все →', 'es' => 'Ver todas →', 'de' => 'Alle ansehen →',
],
'common.from' => [
    'it' => 'da', 'en' => 'from', 'ru' => 'от', 'es' => 'desde', 'de' => 'ab',
],
'common.per_night' => [
    'it' => '/notte', 'en' => '/night', 'ru' => '/ночь', 'es' => '/noche', 'de' => '/Nacht',
],
'common.per_day' => [
    'it' => '/giorno', 'en' => '/day', 'ru' => '/день', 'es' => '/día', 'de' => '/Tag',
],
'common.per_person' => [
    'it' => '/persona', 'en' => '/person', 'ru' => '/чел.', 'es' => '/persona', 'de' => '/Person',
],
'common.per_trip' => [
    'it' => 'a tratta', 'en' => 'per trip', 'ru' => 'за поездку', 'es' => 'por trayecto', 'de' => 'pro Strecke',
],
'common.guests' => [
    'it' => 'Ospiti', 'en' => 'Guests', 'ru' => 'Гостей', 'es' => 'Huéspedes', 'de' => 'Gäste',
],
'common.checkin' => [
    'it' => 'Check-in', 'en' => 'Check-in', 'ru' => 'Заезд', 'es' => 'Entrada', 'de' => 'Anreise',
],
'common.checkout' => [
    'it' => 'Check-out', 'en' => 'Check-out', 'ru' => 'Выезд', 'es' => 'Salida', 'de' => 'Abreise',
],
'common.where' => [
    'it' => 'Dove', 'en' => 'Where', 'ru' => 'Куда', 'es' => 'Dónde', 'de' => 'Wohin',
],
'common.all_areas' => [
    'it' => 'Tutte le zone', 'en' => 'All areas', 'ru' => 'Все районы', 'es' => 'Todas las zonas', 'de' => 'Alle Gebiete',
],
'common.loading' => [
    'it' => 'Caricamento…', 'en' => 'Loading…', 'ru' => 'Загрузка…', 'es' => 'Cargando…', 'de' => 'Wird geladen…',
],
'common.send' => [
    'it' => 'Invio…', 'en' => 'Sending…', 'ru' => 'Отправка…', 'es' => 'Enviando…', 'de' => 'Wird gesendet…',
],
'common.max' => [
    'it' => 'max', 'en' => 'max', 'ru' => 'до', 'es' => 'máx.', 'de' => 'max.',
],
'common.up_to' => [
    'it' => 'Fino a', 'en' => 'Up to', 'ru' => 'До', 'es' => 'Hasta', 'de' => 'Bis zu',
],
'common.optional' => [
    'it' => 'opzionale', 'en' => 'optional', 'ru' => 'необязательно', 'es' => 'opcional', 'de' => 'optional',
],
'common.reviews' => [
    'it' => 'recensioni', 'en' => 'reviews', 'ru' => 'отзывов', 'es' => 'opiniones', 'de' => 'Bewertungen',
],
'common.notes' => [
    'it' => 'Note', 'en' => 'Notes', 'ru' => 'Заметки', 'es' => 'Notas', 'de' => 'Notizen',
],
'common.error' => [
    'it' => 'Errore', 'en' => 'Error', 'ru' => 'Ошибка', 'es' => 'Error', 'de' => 'Fehler',
],
'common.reset' => [
    'it' => 'Reset', 'en' => 'Reset', 'ru' => 'Сбросить', 'es' => 'Restablecer', 'de' => 'Zurücksetzen',
],

// ========== HOME HERO ==========
'hero.badge' => [
    'it' => 'Selezionati a mano · 100% gestione diretta',
    'en' => 'Hand-picked · 100% direct management',
    'ru' => 'Авторский подбор · прямое управление 100%',
    'es' => 'Seleccionados a mano · 100% gestión directa',
    'de' => 'Handverlesen · 100% Direktverwaltung',
],
'hero.title.line1' => [
    'it' => 'La tua prossima', 'en' => 'Your next', 'ru' => 'Ваш следующий', 'es' => 'Tus próximas', 'de' => 'Dein nächster',
],
'hero.title.italic' => [
    'it' => 'vacanza', 'en' => 'getaway', 'ru' => 'отпуск', 'es' => 'vacaciones', 'de' => 'Urlaub',
],
'hero.title.line3' => [
    'it' => 'inizia qui.', 'en' => 'starts here.', 'ru' => 'начинается здесь.', 'es' => 'empieza aquí.', 'de' => 'beginnt hier.',
],
'hero.subtitle' => [
    'it' => 'Appartamenti selezionati personalmente a Sharm El Sheikh: Naama Bay, Hadaba, Nabq, Sharks Bay e Old Market. Check-in fluido, assistenza in italiano, ospitalità che si sente.',
    'en' => 'Apartments personally curated in Sharm El Sheikh: Naama Bay, Hadaba, Nabq, Sharks Bay and Old Market. Smooth check-in, multilingual support, hospitality you can feel.',
    'ru' => 'Лично подобранные апартаменты в Шарм-эль-Шейхе: Naama Bay, Hadaba, Nabq, Sharks Bay и Old Market. Простой заезд, поддержка на русском, гостеприимство, которое чувствуешь.',
    'es' => 'Apartamentos seleccionados personalmente en Sharm El Sheikh: Naama Bay, Hadaba, Nabq, Sharks Bay y Old Market. Check-in fluido, atención en español, hospitalidad que se nota.',
    'de' => 'Persönlich ausgewählte Apartments in Sharm El Sheikh: Naama Bay, Hadaba, Nabq, Sharks Bay und Old Market. Reibungsloses Einchecken, Betreuung auf Deutsch, spürbare Gastfreundschaft.',
],
'hero.cta.explore' => [
    'it' => 'Esplora appartamenti', 'en' => 'Browse apartments', 'ru' => 'Смотреть апартаменты', 'es' => 'Ver apartamentos', 'de' => 'Apartments entdecken',
],
'hero.cta.how' => [
    'it' => 'Come funziona', 'en' => 'How it works', 'ru' => 'Как это работает', 'es' => 'Cómo funciona', 'de' => 'So funktioniert\'s',
],
'hero.stats.apartments' => [
    'it' => 'Appartamenti', 'en' => 'Apartments', 'ru' => 'Апартаментов', 'es' => 'Apartamentos', 'de' => 'Apartments',
],
'hero.stats.reviews_new' => [
    'it' => 'Nuove recensioni', 'en' => 'Fresh reviews', 'ru' => 'Новых отзывов', 'es' => 'Reseñas nuevas', 'de' => 'Neue Bewertungen',
],
'hero.stats.guests' => [
    'it' => 'Ospiti accolti', 'en' => 'Guests welcomed', 'ru' => 'Принятых гостей', 'es' => 'Huéspedes recibidos', 'de' => 'Gäste begrüßt',
],

// ========== HOME SECTIONS ==========
'home.zones.badge' => [
    'it' => 'Zone & villaggi', 'en' => 'Areas & resorts', 'ru' => 'Районы и курорты', 'es' => 'Zonas y resorts', 'de' => 'Gebiete & Resorts',
],
'home.zones.title' => [
    'it' => 'Le nostre destinazioni.',
    'en' => 'Our destinations.',
    'ru' => 'Наши направления.',
    'es' => 'Nuestros destinos.',
    'de' => 'Unsere Reiseziele.',
],
'home.zones.sub' => [
    'it' => 'Zone di Sharm El Sheikh e villaggi/resort selezionati.',
    'en' => 'Hand-picked Sharm El Sheikh areas and resort villages.',
    'ru' => 'Отобранные районы Шарм-эль-Шейха и курортные комплексы.',
    'es' => 'Zonas de Sharm El Sheikh y villas/resorts seleccionados.',
    'de' => 'Ausgewählte Gebiete in Sharm El Sheikh sowie Resort-Dörfer.',
],
'home.zones.unit_one' => [
    'it' => 'appartamento', 'en' => 'apartment', 'ru' => 'апартамент', 'es' => 'apartamento', 'de' => 'Apartment',
],
'home.zones.unit_many' => [
    'it' => 'appartamenti', 'en' => 'apartments', 'ru' => 'апартаментов', 'es' => 'apartamentos', 'de' => 'Apartments',
],
'home.zones.kind_zone' => [
    'it' => 'Zona', 'en' => 'Area', 'ru' => 'Район', 'es' => 'Zona', 'de' => 'Gebiet',
],
'home.zones.kind_villaggio' => [
    'it' => 'Villaggio', 'en' => 'Resort village', 'ru' => 'Курорт', 'es' => 'Resort', 'de' => 'Resort',
],
'home.zones.kind_quartiere' => [
    'it' => 'Quartiere', 'en' => 'Neighborhood', 'ru' => 'Квартал', 'es' => 'Barrio', 'de' => 'Viertel',
],
'home.featured.badge' => [
    'it' => 'In evidenza', 'en' => 'Featured', 'ru' => 'Рекомендуем', 'es' => 'Destacados', 'de' => 'Im Fokus',
],
'home.featured.title' => [
    'it' => 'Le nostre case.', 'en' => 'Our homes.', 'ru' => 'Наши дома.', 'es' => 'Nuestras casas.', 'de' => 'Unsere Häuser.',
],
'home.featured.sub' => [
    'it' => 'Una selezione delle proprietà più richieste.',
    'en' => 'A selection of our most-requested stays.',
    'ru' => 'Подборка самых востребованных вариантов.',
    'es' => 'Una selección de las propiedades más solicitadas.',
    'de' => 'Eine Auswahl unserer gefragtesten Apartments.',
],
'home.banner.rentals.title' => [ 'it' => 'Noleggi', 'en' => 'Rentals', 'ru' => 'Аренда', 'es' => 'Alquileres', 'de' => 'Verleih' ],
'home.banner.rentals.sub' => [
    'it' => 'Auto, scooter, golf cart, monopattini',
    'en' => 'Cars, scooters, golf carts, e-scooters',
    'ru' => 'Авто, скутеры, гольф-кары, электросамокаты',
    'es' => 'Coches, scooters, golf carts, patinetes',
    'de' => 'Autos, Roller, Golf Carts, E-Scooter',
],
'home.banner.excursions.title' => [ 'it' => 'Escursioni', 'en' => 'Excursions', 'ru' => 'Экскурсии', 'es' => 'Excursiones', 'de' => 'Ausflüge' ],
'home.banner.excursions.sub' => [
    'it' => 'Ras Mohammed, deserto, Cairo, Sinai',
    'en' => 'Ras Mohammed, desert, Cairo, Sinai',
    'ru' => 'Рас-Мохаммед, пустыня, Каир, Синай',
    'es' => 'Ras Mohammed, desierto, El Cairo, Sinaí',
    'de' => 'Ras Mohammed, Wüste, Kairo, Sinai',
],
'home.banner.transfer.title' => [
    'it' => 'Transfer aeroporto', 'en' => 'Airport transfer', 'ru' => 'Трансфер из аэропорта', 'es' => 'Traslados aeropuerto', 'de' => 'Flughafentransfer',
],
'home.banner.transfer.sub' => [
    'it' => 'Auto e minibus da/per SSH',
    'en' => 'Cars and minibuses from/to SSH',
    'ru' => 'Авто и микроавтобусы из/в SSH',
    'es' => 'Coches y minibuses desde/hacia SSH',
    'de' => 'Autos und Minibusse von/nach SSH',
],
'home.banner.diving.title' => [
    'it' => 'Diving & snorkeling', 'en' => 'Diving & snorkeling', 'ru' => 'Дайвинг и снорклинг', 'es' => 'Buceo y snorkel', 'de' => 'Tauchen & Schnorcheln',
],
'home.banner.diving.sub' => [
    'it' => 'Reef e relitti del Mar Rosso',
    'en' => 'Red Sea reefs and wrecks',
    'ru' => 'Рифы и затонувшие корабли Красного моря',
    'es' => 'Arrecifes y pecios del Mar Rojo',
    'de' => 'Riffe und Wracks im Roten Meer',
],
'home.rentals.title' => [
    'it' => 'Muoviti come vuoi.', 'en' => 'Move how you want.', 'ru' => 'Передвигайтесь как удобно.', 'es' => 'Muévete a tu manera.', 'de' => 'Beweg dich, wie du willst.',
],
'home.rentals.sub' => [
    'it' => 'Auto, golf cart, scooter e monopattini elettrici.',
    'en' => 'Cars, golf carts, scooters and electric kick-scooters.',
    'ru' => 'Авто, гольф-кары, скутеры и электросамокаты.',
    'es' => 'Coches, golf carts, scooters y patinetes eléctricos.',
    'de' => 'Autos, Golf Carts, Roller und E-Scooter.',
],
'home.rentals.see_all' => [
    'it' => 'Tutti i noleggi', 'en' => 'All rentals', 'ru' => 'Вся аренда', 'es' => 'Todos los alquileres', 'de' => 'Alle Mietfahrzeuge',
],
'home.excursions.title' => [
    'it' => 'Vivi Sharm.', 'en' => 'Live Sharm.', 'ru' => 'Прочувствуйте Шарм.', 'es' => 'Vive Sharm.', 'de' => 'Erlebe Sharm.',
],
'home.excursions.sub' => [
    'it' => 'Snorkeling, deserto, diving, tour culturali — con guida italiana.',
    'en' => 'Snorkeling, desert, diving and cultural tours — with English-speaking guides.',
    'ru' => 'Снорклинг, пустыня, дайвинг, культурные туры — с русскоговорящим гидом.',
    'es' => 'Snorkel, desierto, buceo, tours culturales — con guía en español.',
    'de' => 'Schnorcheln, Wüste, Tauchen, Kulturtouren — mit deutschsprachiger Betreuung.',
],
'home.excursions.see_all' => [
    'it' => 'Tutte le escursioni', 'en' => 'All tours', 'ru' => 'Все экскурсии', 'es' => 'Todas las excursiones', 'de' => 'Alle Ausflüge',
],
'home.transfer.title' => [
    'it' => 'Ti aspettiamo all\'aeroporto.',
    'en' => 'We pick you up at the airport.',
    'ru' => 'Встретим вас в аэропорту.',
    'es' => 'Te recogemos en el aeropuerto.',
    'de' => 'Wir holen Sie am Flughafen ab.',
],
'home.transfer.sub' => [
    'it' => 'Transfer privato e diretto dall\'aeroporto SSH al tuo appartamento.',
    'en' => 'Private door-to-door transfer from SSH airport to your apartment.',
    'ru' => 'Частный трансфер «от двери до двери» из аэропорта SSH к вашим апартаментам.',
    'es' => 'Traslado privado puerta a puerta desde el aeropuerto SSH a tu apartamento.',
    'de' => 'Privater Tür-zu-Tür-Transfer vom Flughafen SSH zu Ihrem Apartment.',
],
'home.transfer.see_all' => [
    'it' => 'Tutte le tratte', 'en' => 'All routes', 'ru' => 'Все маршруты', 'es' => 'Todas las rutas', 'de' => 'Alle Strecken',
],
'home.howit.badge' => [
    'it' => 'Come funziona', 'en' => 'How it works', 'ru' => 'Как это работает', 'es' => 'Cómo funciona', 'de' => 'So funktioniert\'s',
],
'home.howit.title' => [
    'it' => 'Tre passi, zero stress.', 'en' => 'Three steps, zero stress.', 'ru' => 'Три шага — никакого стресса.', 'es' => 'Tres pasos, cero estrés.', 'de' => 'Drei Schritte, null Stress.',
],
'home.howit.s1.t' => [ 'it' => 'Trova', 'en' => 'Find', 'ru' => 'Найдите', 'es' => 'Encuentra', 'de' => 'Finden' ],
'home.howit.s1.d' => [
    'it' => 'Sfoglia la collezione, filtra per zona, date e ospiti. Vedi disponibilità live.',
    'en' => 'Browse the collection, filter by area, dates and guests. Live availability.',
    'ru' => 'Посмотрите подборку, отфильтруйте по району, датам и гостям. Доступность онлайн.',
    'es' => 'Explora la colección, filtra por zona, fechas y huéspedes. Disponibilidad en vivo.',
    'de' => 'Stöber durch die Kollektion, filter nach Gebiet, Daten und Gästen. Live-Verfügbarkeit.',
],
'home.howit.s2.t' => [ 'it' => 'Prenota', 'en' => 'Book', 'ru' => 'Забронируйте', 'es' => 'Reserva', 'de' => 'Buchen' ],
'home.howit.s2.d' => [
    'it' => 'Compila il modulo. Ricevi conferma immediata via WhatsApp/Email con tutto il necessario.',
    'en' => 'Fill in the form. Get instant confirmation via WhatsApp or email — everything you need.',
    'ru' => 'Заполните форму. Получите мгновенное подтверждение в WhatsApp или Email со всеми деталями.',
    'es' => 'Rellena el formulario. Recibes confirmación inmediata por WhatsApp o email con todo lo necesario.',
    'de' => 'Formular ausfüllen. Sofortige Bestätigung per WhatsApp oder E-Mail — mit allen Infos.',
],
'home.howit.s3.t' => [ 'it' => 'Soggiorna', 'en' => 'Stay', 'ru' => 'Отдыхайте', 'es' => 'Disfruta', 'de' => 'Genießen' ],
'home.howit.s3.d' => [
    'it' => 'Check-in semplice, istruzioni chiare. Siamo qui per qualsiasi cosa, 7/7.',
    'en' => 'Easy check-in, clear instructions. We\'re here for anything, every day.',
    'ru' => 'Простой заезд, чёткие инструкции. Мы на связи 7 дней в неделю.',
    'es' => 'Check-in fácil, instrucciones claras. Estamos aquí para lo que necesites, los 7 días.',
    'de' => 'Einfacher Check-in, klare Anweisungen. Wir sind 7 Tage die Woche für dich da.',
],
'home.benefit.b1.t' => [ 'it' => 'Pagamenti sicuri', 'en' => 'Secure payments', 'ru' => 'Безопасные платежи', 'es' => 'Pagos seguros', 'de' => 'Sichere Zahlungen' ],
'home.benefit.b1.d' => [
    'it' => 'Acconto e saldo trasparenti, ricevuta sempre.',
    'en' => 'Transparent deposit and balance, always with receipt.',
    'ru' => 'Прозрачный задаток и оплата, всегда с квитанцией.',
    'es' => 'Anticipo y saldo transparentes, siempre con recibo.',
    'de' => 'Transparente Anzahlung und Restzahlung, immer mit Beleg.',
],
'home.benefit.b2.t' => [ 'it' => 'Check-in semplice', 'en' => 'Easy check-in', 'ru' => 'Простой заезд', 'es' => 'Check-in sencillo', 'de' => 'Einfacher Check-in' ],
'home.benefit.b2.d' => [
    'it' => 'Istruzioni chiare, supporto WhatsApp 7/7.',
    'en' => 'Clear instructions, WhatsApp support 7 days a week.',
    'ru' => 'Понятные инструкции, поддержка в WhatsApp 7 дней в неделю.',
    'es' => 'Instrucciones claras, soporte por WhatsApp los 7 días.',
    'de' => 'Klare Anweisungen, WhatsApp-Support 7 Tage die Woche.',
],
'home.benefit.b3.t' => [ 'it' => 'Recensioni reali', 'en' => 'Real reviews', 'ru' => 'Настоящие отзывы', 'es' => 'Opiniones reales', 'de' => 'Echte Bewertungen' ],
'home.benefit.b3.d' => [
    'it' => 'Solo clienti verificati, niente sorprese.',
    'en' => 'Only from verified guests — no surprises.',
    'ru' => 'Только от проверенных гостей — никаких сюрпризов.',
    'es' => 'Solo de clientes verificados — sin sorpresas.',
    'de' => 'Nur von verifizierten Gästen — keine Überraschungen.',
],
'home.benefit.b4.t' => [ 'it' => 'Selezione curata', 'en' => 'Curated selection', 'ru' => 'Авторская подборка', 'es' => 'Selección cuidada', 'de' => 'Kuratierte Auswahl' ],
'home.benefit.b4.d' => [
    'it' => 'Ogni casa visitata personalmente.',
    'en' => 'Every home personally visited.',
    'ru' => 'Каждый дом проверен лично.',
    'es' => 'Cada casa visitada personalmente.',
    'de' => 'Jedes Haus persönlich besichtigt.',
],
'home.testi.badge' => [
    'it' => 'Cosa dicono di noi', 'en' => 'What they say', 'ru' => 'Что о нас говорят', 'es' => 'Lo que dicen de nosotros', 'de' => 'Was unsere Gäste sagen',
],
'home.testi.title' => [
    'it' => 'Recensioni vere, da clienti veri.',
    'en' => 'Real reviews, from real guests.',
    'ru' => 'Настоящие отзывы — от настоящих гостей.',
    'es' => 'Opiniones de verdad, de clientes de verdad.',
    'de' => 'Echte Bewertungen — von echten Gästen.',
],
'home.cta.title' => [
    'it' => 'Pronti a partire?',
    'en' => 'Ready to go?',
    'ru' => 'Готовы в путь?',
    'es' => '¿Listos para irte?',
    'de' => 'Bereit für die Reise?',
],
'home.cta.sub' => [
    'it' => 'Trova la casa giusta in pochi click. Conferma immediata, prezzi senza sorprese.',
    'en' => 'Find your home in a few clicks. Instant confirmation, no hidden fees.',
    'ru' => 'Найдите свой дом за пару кликов. Мгновенное подтверждение, никаких сюрпризов в цене.',
    'es' => 'Encuentra tu casa en pocos clics. Confirmación inmediata, precios sin sorpresas.',
    'de' => 'Finde dein Zuhause in wenigen Klicks. Sofortige Bestätigung, keine versteckten Kosten.',
],

// ========== ABOUT PATRIZIA ==========
'home.about.badge' => [
    'it' => 'La fondatrice', 'en' => 'The founder', 'ru' => 'Основательница', 'es' => 'La fundadora', 'de' => 'Die Gründerin',
],
'home.about.available' => [
    'it' => 'Disponibile su WhatsApp',
    'en' => 'Available on WhatsApp',
    'ru' => 'Доступна в WhatsApp',
    'es' => 'Disponible en WhatsApp',
    'de' => 'Verfügbar auf WhatsApp',
],
'home.about.title_pre' => [
    'it' => '', 'en' => '', 'ru' => '', 'es' => '', 'de' => '',
],
'home.about.title_post' => [
    'it' => ', il vostro riferimento a Sharm.',
    'en' => ', your trusted contact in Sharm.',
    'ru' => ' — ваш надежный контакт в Шарм-эш-Шейхе.',
    'es' => ', vuestra referencia en Sharm.',
    'de' => ', Ihre Ansprechpartnerin in Sharm.',
],
'home.about.p1' => [
    'it' => 'Mi occupo <b>personalmente</b> di ogni ospite, dal primo messaggio fino al rientro a casa. Gli appartamenti che vedete sul sito li ho <b>selezionati uno per uno</b>, in zone che conosco a fondo: posizione, qualità, sicurezza e rapporto qualità-prezzo sono verificati di persona, non delegati a una piattaforma.',
    'en' => 'I take care of every guest <b>personally</b>, from the first message until you return home. Each apartment on this website has been <b>hand-picked</b>, in areas I know inside out: location, quality, safety and value for money are verified in person, never delegated to a platform.',
    'ru' => 'Я <b>лично</b> сопровождаю каждого гостя — от первого сообщения до возвращения домой. Все апартаменты на сайте я <b>отобрала по одному</b>, в районах, которые знаю досконально: расположение, качество, безопасность и соотношение цены и качества проверены лично, а не доверены платформе.',
    'es' => 'Me ocupo <b>personalmente</b> de cada huésped, desde el primer mensaje hasta el regreso a casa. Los apartamentos que ven en el sitio los he <b>seleccionado uno a uno</b>, en zonas que conozco a fondo: ubicación, calidad, seguridad y relación calidad-precio se verifican en persona, no se delegan a una plataforma.',
    'de' => 'Ich kümmere mich <b>persönlich</b> um jeden Gast — von der ersten Nachricht bis zur Heimreise. Die Apartments auf dieser Seite habe ich <b>einzeln ausgewählt</b>, in Vierteln, die ich genau kenne: Lage, Qualität, Sicherheit und Preis-Leistung prüfe ich selbst, nicht über eine Plattform.',
],
'home.about.p2' => [
    'it' => 'Vi seguo con <b>assistenza in italiano sempre disponibile</b>, anche fuori orario: per un dubbio sul check-in, un transfer da organizzare, un\'escursione da prenotare o semplicemente il consiglio giusto su dove mangiare bene. Trovate una <b>professionista presente, discreta e rapida</b>, non un call center.',
    'en' => 'I support you with <b>round-the-clock assistance in Italian and English</b>, even after hours: a check-in question, a transfer to arrange, a tour to book, or simply the right tip on where to dine well. You\'ll find a <b>discreet, responsive professional</b>, not a call center.',
    'ru' => 'Я всегда на связи — поддержка в любое время дня и ночи: вопрос по заселению, организация трансфера, бронирование экскурсии или просто совет, где вкусно поужинать. Со мной вы получаете <b>деликатного и быстрого специалиста</b>, а не колл-центр.',
    'es' => 'Os acompaño con <b>asistencia siempre disponible</b>, incluso fuera de horario: una duda del check-in, un transfer que organizar, una excursión que reservar o simplemente el consejo justo sobre dónde cenar bien. Encontrarán una <b>profesional presente, discreta y rápida</b>, no un call center.',
    'de' => 'Ich begleite Sie mit <b>jederzeit erreichbarer Betreuung</b>, auch außerhalb der Geschäftszeiten: bei Fragen zum Check-in, einem Transfer, einer Tour oder einem einfachen Tipp, wo man gut essen kann. Sie finden eine <b>diskrete, schnelle Ansprechpartnerin</b> — kein Callcenter.',
],
'home.about.feature1.t' => [
    'it' => 'Selezione personale', 'en' => 'Personal selection', 'ru' => 'Личный отбор', 'es' => 'Selección personal', 'de' => 'Persönliche Auswahl',
],
'home.about.feature1.d' => [
    'it' => 'ogni appartamento verificato',
    'en' => 'every apartment vetted',
    'ru' => 'каждые апартаменты проверены',
    'es' => 'cada apartamento verificado',
    'de' => 'jedes Apartment geprüft',
],
'home.about.feature2.t' => [
    'it' => 'Assistenza H24', 'en' => '24/7 support', 'ru' => 'Поддержка 24/7', 'es' => 'Asistencia 24/7', 'de' => '24/7-Betreuung',
],
'home.about.feature2.d' => [
    'it' => 'in italiano, anche di notte',
    'en' => 'in Italian and English, day or night',
    'ru' => 'днем и ночью',
    'es' => 'también de noche',
    'de' => 'auch nachts',
],
'home.about.feature3.t' => [
    'it' => 'Conoscenza locale', 'en' => 'Local know-how', 'ru' => 'Знание места', 'es' => 'Conocimiento local', 'de' => 'Ortskenntnis',
],
'home.about.feature3.d' => [
    'it' => 'consigli su misura',
    'en' => 'tailored advice',
    'ru' => 'персональные советы',
    'es' => 'consejos a medida',
    'de' => 'maßgeschneiderter Rat',
],
'home.about.cta_wa' => [
    'it' => 'Scrivimi su WhatsApp',
    'en' => 'Message me on WhatsApp',
    'ru' => 'Написать в WhatsApp',
    'es' => 'Escríbeme por WhatsApp',
    'de' => 'Schreib mir auf WhatsApp',
],
'home.about.cta_apt' => [
    'it' => 'Vedi gli appartamenti',
    'en' => 'Browse apartments',
    'ru' => 'Смотреть апартаменты',
    'es' => 'Ver los apartamentos',
    'de' => 'Apartments ansehen',
],
'home.about.quote' => [
    'it' => '«Il mio obiettivo è semplice: che ogni ospite torni a casa serenamente, con la sensazione di essere stato seguito da una persona di fiducia.»',
    'en' => '"My goal is simple: every guest returns home relaxed, feeling they were looked after by a trusted person."',
    'ru' => '«Моя цель проста: чтобы каждый гость вернулся домой со спокойной душой, чувствуя, что о нем заботился человек, которому можно доверять.»',
    'es' => '«Mi objetivo es simple: que cada huésped vuelva a casa tranquilo, con la sensación de haber sido atendido por una persona de confianza.»',
    'de' => '„Mein Ziel ist einfach: Jeder Gast kehrt entspannt nach Hause zurück, mit dem Gefühl, von einer vertrauenswürdigen Person betreut worden zu sein."',
],
'home.about.wa_message' => [
    'it' => 'Ciao Patrizia, vorrei informazioni sui vostri appartamenti a Sharm El Sheikh.',
    'en' => 'Hi Patrizia, I would like information about your apartments in Sharm El Sheikh.',
    'ru' => 'Здравствуйте, Патриция! Хотела бы получить информацию о ваших апартаментах в Шарм-эш-Шейхе.',
    'es' => 'Hola Patrizia, me gustaría información sobre vuestros apartamentos en Sharm El Sheikh.',
    'de' => 'Hallo Patrizia, ich hätte gern Informationen zu Ihren Apartments in Sharm El Sheikh.',
],

// ========== APARTMENTS LIST ==========
'apt_list.badge' => [
    'it' => 'La collezione', 'en' => 'The collection', 'ru' => 'Коллекция', 'es' => 'La colección', 'de' => 'Die Auswahl',
],
'apt_list.title' => [
    'it' => 'Trova la casa giusta.', 'en' => 'Find the right home.', 'ru' => 'Найдите свой дом.', 'es' => 'Encuentra el lugar ideal.', 'de' => 'Find dein Zuhause auf Zeit.',
],
'apt_list.sub' => [
    'it' => 'Filtra per zona di Sharm, date, ospiti e prezzo. Ogni casa è ispezionata personalmente.',
    'en' => 'Filter by Sharm area, dates, guests and price. Every home is personally inspected.',
    'ru' => 'Фильтруйте по району, датам, гостям и цене. Каждый дом проверен лично.',
    'es' => 'Filtra por zona de Sharm, fechas, huéspedes y precio. Cada casa la inspeccionamos personalmente.',
    'de' => 'Filter nach Gebiet, Daten, Gästen und Preis. Jedes Haus persönlich begutachtet.',
],
'apt_list.quickfilters' => [
    'it' => 'Filtri rapidi:', 'en' => 'Quick filters:', 'ru' => 'Быстрые фильтры:', 'es' => 'Filtros rápidos:', 'de' => 'Schnellfilter:',
],
'apt_list.filter.2g' => [ 'it' => '2 ospiti', 'en' => '2 guests', 'ru' => '2 гостя', 'es' => '2 huéspedes', 'de' => '2 Gäste' ],
'apt_list.filter.4g' => [ 'it' => '4+ ospiti', 'en' => '4+ guests', 'ru' => '4+ гостей', 'es' => '4+ huéspedes', 'de' => '4+ Gäste' ],
'apt_list.filter.under' => [ 'it' => 'sotto i €120', 'en' => 'under €120', 'ru' => 'до €120', 'es' => 'menos de €120', 'de' => 'unter €120' ],
'apt_list.results' => [
    'it' => '{n} case disponibili', 'en' => '{n} homes available', 'ru' => 'доступно: {n}', 'es' => '{n} casas disponibles', 'de' => '{n} verfügbare Häuser',
],
'apt_list.results_dates' => [
    'it' => 'Disponibilità per {n} notti — {c} risultati',
    'en' => 'Availability for {n} nights — {c} results',
    'ru' => 'Доступность на {n} ночей — найдено: {c}',
    'es' => 'Disponibilidad para {n} noches — {c} resultados',
    'de' => 'Verfügbarkeit für {n} Nächte — {c} Treffer',
],

// ========== APARTMENT DETAIL ==========
'apt.back' => [
    'it' => 'Tutti gli appartamenti', 'en' => 'All apartments', 'ru' => 'Все апартаменты', 'es' => 'Todos los apartamentos', 'de' => 'Alle Apartments',
],
'apt.show_photos' => [
    'it' => 'Mostra tutte le {n} foto', 'en' => 'Show all {n} photos', 'ru' => 'Все фото ({n})', 'es' => 'Ver las {n} fotos', 'de' => 'Alle {n} Fotos zeigen',
],
'apt.info.guests' => [ 'it' => 'Ospiti', 'en' => 'Guests', 'ru' => 'Гостей', 'es' => 'Huéspedes', 'de' => 'Gäste' ],
'apt.info.bedrooms' => [ 'it' => 'Camere', 'en' => 'Bedrooms', 'ru' => 'Спален', 'es' => 'Dormitorios', 'de' => 'Schlafzimmer' ],
'apt.info.beds' => [ 'it' => 'Letti', 'en' => 'Beds', 'ru' => 'Кроватей', 'es' => 'Camas', 'de' => 'Betten' ],
'apt.info.bathrooms' => [ 'it' => 'Bagni', 'en' => 'Bathrooms', 'ru' => 'Ванных', 'es' => 'Baños', 'de' => 'Bäder' ],
'apt.about' => [
    'it' => 'L\'appartamento', 'en' => 'About this place', 'ru' => 'Об апартаментах', 'es' => 'El apartamento', 'de' => 'Über das Apartment',
],
'apt.amenities' => [
    'it' => 'Servizi inclusi', 'en' => 'Amenities', 'ru' => 'Удобства', 'es' => 'Servicios incluidos', 'de' => 'Ausstattung',
],
'apt.availability' => [
    'it' => 'Disponibilità', 'en' => 'Availability', 'ru' => 'Доступность', 'es' => 'Disponibilidad', 'de' => 'Verfügbarkeit',
],
'apt.availability.tap' => [
    'it' => 'Tocca le date verdi per selezionare check-in e check-out.',
    'en' => 'Tap the green dates to pick check-in and check-out.',
    'ru' => 'Нажмите на зелёные даты, чтобы выбрать заезд и выезд.',
    'es' => 'Toca las fechas verdes para elegir entrada y salida.',
    'de' => 'Tipp auf die grünen Daten, um Anreise und Abreise zu wählen.',
],
'apt.rules' => [
    'it' => 'Regole della casa', 'en' => 'House rules', 'ru' => 'Правила проживания', 'es' => 'Normas de la casa', 'de' => 'Hausregeln',
],
'apt.cleaning' => [
    'it' => 'Pulizie', 'en' => 'Cleaning', 'ru' => 'Уборка', 'es' => 'Limpieza', 'de' => 'Endreinigung',
],
'apt.city_tax' => [
    'it' => 'Tassa soggiorno', 'en' => 'City tax', 'ru' => 'Туристический сбор', 'es' => 'Tasa turística', 'de' => 'Kurtaxe',
],
'apt.coupon' => [
    'it' => 'Codice sconto', 'en' => 'Discount code', 'ru' => 'Промокод', 'es' => 'Código de descuento', 'de' => 'Rabattcode',
],
'apt.fullname' => [
    'it' => 'Nome e cognome', 'en' => 'Full name', 'ru' => 'Имя и фамилия', 'es' => 'Nombre y apellido', 'de' => 'Vor- und Nachname',
],
'apt.email' => [
    'it' => 'Email', 'en' => 'Email', 'ru' => 'Email', 'es' => 'Email', 'de' => 'E-Mail',
],
'apt.phone' => [
    'it' => 'Telefono', 'en' => 'Phone', 'ru' => 'Телефон', 'es' => 'Teléfono', 'de' => 'Telefon',
],
'apt.country_select' => [
    'it' => 'Seleziona il tuo paese',
    'en' => 'Select your country',
    'ru' => 'Выберите вашу страну',
    'es' => 'Selecciona tu país',
    'de' => 'Land auswählen',
],
'apt.country_search' => [
    'it' => 'Cerca paese…',
    'en' => 'Search country…',
    'ru' => 'Поиск страны…',
    'es' => 'Buscar país…',
    'de' => 'Land suchen…',
],
'apt.country_empty' => [
    'it' => 'Nessun paese trovato',
    'en' => 'No country found',
    'ru' => 'Страна не найдена',
    'es' => 'Ningún país encontrado',
    'de' => 'Kein Land gefunden',
],
'apt.request_book' => [
    'it' => 'Richiedi prenotazione', 'en' => 'Request booking', 'ru' => 'Запросить бронирование', 'es' => 'Solicitar reserva', 'de' => 'Buchung anfragen',
],
'apt.no_charge' => [
    'it' => 'Non ti verrà addebitato nulla ora. Confermeremo via WhatsApp o email.',
    'en' => 'You won\'t be charged now. We\'ll confirm via WhatsApp or email.',
    'ru' => 'Сейчас оплата не списывается. Подтверждение придёт в WhatsApp или Email.',
    'es' => 'No se cobra nada ahora. Confirmamos por WhatsApp o email.',
    'de' => 'Es wird jetzt nichts abgebucht. Bestätigung per WhatsApp oder E-Mail.',
],
'apt.req_sent' => [
    'it' => 'Richiesta inviata!', 'en' => 'Request sent!', 'ru' => 'Заявка отправлена!', 'es' => '¡Solicitud enviada!', 'de' => 'Anfrage gesendet!',
],
'apt.booking_code' => [
    'it' => 'Codice prenotazione', 'en' => 'Booking code', 'ru' => 'Номер брони', 'es' => 'Código de reserva', 'de' => 'Buchungscode',
],
'apt.contact_soon' => [
    'it' => 'Ti contatteremo a breve per la conferma.',
    'en' => 'We\'ll be in touch shortly to confirm.',
    'ru' => 'Скоро свяжемся с вами для подтверждения.',
    'es' => 'Nos pondremos en contacto en breve para confirmar.',
    'de' => 'Wir melden uns in Kürze zur Bestätigung.',
],
'apt.help.title' => [
    'it' => 'Hai domande?', 'en' => 'Got questions?', 'ru' => 'Есть вопросы?', 'es' => '¿Tienes dudas?', 'de' => 'Noch Fragen?',
],
'apt.help.sub' => [
    'it' => 'Scrivici, rispondiamo velocemente.',
    'en' => 'Write us — we reply fast.',
    'ru' => 'Напишите нам — отвечаем быстро.',
    'es' => 'Escríbenos, respondemos rápido.',
    'de' => 'Schreib uns — wir antworten schnell.',
],
'apt.help.cta' => [
    'it' => 'Contatta lo staff →', 'en' => 'Contact us →', 'ru' => 'Связаться с нами →', 'es' => 'Contactar con el staff →', 'de' => 'Team kontaktieren →',
],
'apt.summary.nights' => [
    'it' => 'notti × pernottamento', 'en' => 'nights × stay', 'ru' => 'ночей × проживание', 'es' => 'noches × estancia', 'de' => 'Nächte × Aufenthalt',
],
'apt.summary.total' => [
    'it' => 'Totale', 'en' => 'Total', 'ru' => 'Итого', 'es' => 'Total', 'de' => 'Gesamt',
],
'apt.cal.legend.free' => [ 'it' => 'Disponibile', 'en' => 'Available', 'ru' => 'Свободно', 'es' => 'Disponible', 'de' => 'Verfügbar' ],
'apt.cal.legend.busy' => [ 'it' => 'Occupato', 'en' => 'Booked', 'ru' => 'Занято', 'es' => 'Ocupado', 'de' => 'Belegt' ],
'apt.cal.legend.checkinout' => [ 'it' => 'Check-in/out', 'en' => 'Check-in/out', 'ru' => 'Заезд/выезд', 'es' => 'Entrada/salida', 'de' => 'An-/Abreise' ],
'apt.cal.legend.selected' => [ 'it' => 'Selezionato', 'en' => 'Selected', 'ru' => 'Выбрано', 'es' => 'Seleccionado', 'de' => 'Ausgewählt' ],
'apt.cal.choose_out' => [
    'it' => 'Scegli check-out', 'en' => 'Pick check-out', 'ru' => 'Выберите выезд', 'es' => 'Elige la salida', 'de' => 'Abreise wählen',
],
'apt.cal.nights_count' => [ 'it' => 'notti', 'en' => 'nights', 'ru' => 'ночей', 'es' => 'noches', 'de' => 'Nächte' ],

// ========== RENTALS LIST ==========
'rent.title' => [
    'it' => 'Muoviti come vuoi.', 'en' => 'Move how you want.', 'ru' => 'Передвигайтесь как удобно.', 'es' => 'Muévete a tu manera.', 'de' => 'Beweg dich, wie du willst.',
],
'rent.sub' => [
    'it' => 'Auto, golf cart per il resort, scooter e monopattini elettrici. Tariffe giornaliere, settimanali e mensili.',
    'en' => 'Cars, golf carts for the resort, scooters and electric kick-scooters. Daily, weekly and monthly rates.',
    'ru' => 'Авто, гольф-кары для курорта, скутеры и электросамокаты. Тарифы — день, неделя, месяц.',
    'es' => 'Coches, golf carts para el complejo, scooters y patinetes eléctricos. Tarifas diarias, semanales y mensuales.',
    'de' => 'Autos, Golf Carts fürs Resort, Roller und E-Scooter. Tages-, Wochen- und Monatstarife.',
],
'rent.filter.all' => [ 'it' => 'Tutti', 'en' => 'All', 'ru' => 'Все', 'es' => 'Todos', 'de' => 'Alle' ],
'rent.no_items' => [
    'it' => 'Nessun veicolo disponibile', 'en' => 'No vehicles available', 'ru' => 'Нет доступных транспортных средств', 'es' => 'No hay vehículos disponibles', 'de' => 'Keine Fahrzeuge verfügbar',
],
'rent.at_resort' => [
    'it' => 'presso resort', 'en' => 'at resort', 'ru' => 'на курорте', 'es' => 'en el resort', 'de' => 'im Resort',
],
'rent.from_weekly' => [
    'it' => 'da {p}/settimana', 'en' => 'from {p}/week', 'ru' => 'от {p}/неделя', 'es' => 'desde {p}/semana', 'de' => 'ab {p}/Woche',
],
'rent.detail.back' => [
    'it' => 'Tutti i noleggi', 'en' => 'All rentals', 'ru' => 'Вся аренда', 'es' => 'Todos los alquileres', 'de' => 'Alle Mietfahrzeuge',
],
'rent.detail.description' => [
    'it' => 'Descrizione', 'en' => 'Description', 'ru' => 'Описание', 'es' => 'Descripción', 'de' => 'Beschreibung',
],
'rent.detail.features' => [
    'it' => 'Caratteristiche', 'en' => 'Features', 'ru' => 'Характеристики', 'es' => 'Características', 'de' => 'Merkmale',
],
'rent.detail.rates' => [
    'it' => 'Tariffe', 'en' => 'Rates', 'ru' => 'Тарифы', 'es' => 'Tarifas', 'de' => 'Tarife',
],
'rent.detail.daily' => [ 'it' => 'Giornaliera', 'en' => 'Daily', 'ru' => 'За день', 'es' => 'Diaria', 'de' => 'Tagestarif' ],
'rent.detail.weekly' => [ 'it' => 'Settimanale', 'en' => 'Weekly', 'ru' => 'За неделю', 'es' => 'Semanal', 'de' => 'Wochentarif' ],
'rent.detail.biweekly' => [ 'it' => '2 settimane', 'en' => '2 weeks', 'ru' => '2 недели', 'es' => '2 semanas', 'de' => '2 Wochen' ],
'rent.detail.triweekly' => [ 'it' => '3 settimane', 'en' => '3 weeks', 'ru' => '3 недели', 'es' => '3 semanas', 'de' => '3 Wochen' ],
'rent.detail.monthly' => [ 'it' => 'Mensile', 'en' => 'Monthly', 'ru' => 'За месяц', 'es' => 'Mensual', 'de' => 'Monatstarif' ],
'rent.detail.deposit' => [
    'it' => 'Cauzione richiesta: {p} (rimborsata alla riconsegna).',
    'en' => 'Security deposit: {p} (refunded on return).',
    'ru' => 'Залог: {p} (возвращается при сдаче).',
    'es' => 'Fianza: {p} (devuelta a la entrega).',
    'de' => 'Kaution: {p} (Rückerstattung bei Rückgabe).',
],
'rent.detail.license' => [
    'it' => 'Patente di guida obbligatoria.',
    'en' => 'Driver\'s license required.',
    'ru' => 'Требуется водительское удостоверение.',
    'es' => 'Carné de conducir obligatorio.',
    'de' => 'Führerschein erforderlich.',
],
'rent.detail.minage' => [
    'it' => 'Età minima: {n} anni.', 'en' => 'Minimum age: {n}.', 'ru' => 'Минимальный возраст: {n} лет.', 'es' => 'Edad mínima: {n} años.', 'de' => 'Mindestalter: {n} Jahre.',
],
'rent.detail.pickup' => [ 'it' => 'Ritiro', 'en' => 'Pick-up', 'ru' => 'Получение', 'es' => 'Recogida', 'de' => 'Abholung' ],
'rent.detail.return' => [ 'it' => 'Riconsegna', 'en' => 'Return', 'ru' => 'Возврат', 'es' => 'Devolución', 'de' => 'Rückgabe' ],
'rent.detail.days' => [ 'it' => 'giorni', 'en' => 'days', 'ru' => 'дней', 'es' => 'días', 'de' => 'Tage' ],
'rent.detail.book' => [
    'it' => 'Noleggia', 'en' => 'Rent', 'ru' => 'Арендовать', 'es' => 'Alquilar', 'de' => 'Mieten',
],
'rent.detail.available_at' => [
    'it' => 'Disponibile presso', 'en' => 'Available at', 'ru' => 'Доступно в', 'es' => 'Disponible en', 'de' => 'Verfügbar bei',
],

// ========== EXCURSIONS LIST ==========
'exc.title' => [
    'it' => 'Vivi Sharm.', 'en' => 'Live Sharm.', 'ru' => 'Прочувствуйте Шарм.', 'es' => 'Vive Sharm.', 'de' => 'Erlebe Sharm.',
],
'exc.sub' => [
    'it' => 'Snorkeling, deserto, diving e tour culturali. Esperienze curate, prezzi onesti.',
    'en' => 'Snorkeling, desert, diving and cultural tours. Curated experiences, fair prices.',
    'ru' => 'Снорклинг, пустыня, дайвинг и культурные туры. Продуманные программы, честные цены.',
    'es' => 'Snorkel, desierto, buceo y tours culturales. Experiencias cuidadas, precios honestos.',
    'de' => 'Schnorcheln, Wüste, Tauchen und Kulturtouren. Sorgfältig zusammengestellt, faire Preise.',
],
'exc.no_items' => [
    'it' => 'Nessuna escursione disponibile', 'en' => 'No tours available', 'ru' => 'Экскурсий нет', 'es' => 'No hay excursiones disponibles', 'de' => 'Keine Ausflüge verfügbar',
],
'exc.detail.back' => [
    'it' => 'Tutte le escursioni', 'en' => 'All tours', 'ru' => 'Все экскурсии', 'es' => 'Todas las excursiones', 'de' => 'Alle Ausflüge',
],
'exc.detail.experience' => [
    'it' => 'L\'esperienza', 'en' => 'The experience', 'ru' => 'Программа', 'es' => 'La experiencia', 'de' => 'Das Erlebnis',
],
'exc.detail.included' => [ 'it' => 'Incluso', 'en' => 'Included', 'ru' => 'Включено', 'es' => 'Incluido', 'de' => 'Inklusive' ],
'exc.detail.not_included' => [ 'it' => 'Non incluso', 'en' => 'Not included', 'ru' => 'Не включено', 'es' => 'No incluido', 'de' => 'Nicht inbegriffen' ],
'exc.detail.days' => [
    'it' => 'Giorni disponibili', 'en' => 'Available days', 'ru' => 'Дни проведения', 'es' => 'Días disponibles', 'de' => 'Verfügbare Tage',
],
'exc.detail.hours' => [ 'it' => 'ore', 'en' => 'hours', 'ru' => 'часа', 'es' => 'horas', 'de' => 'Stunden' ],
'exc.detail.max_people' => [
    'it' => 'Max {n} persone', 'en' => 'Max {n} people', 'ru' => 'До {n} человек', 'es' => 'Máx. {n} personas', 'de' => 'Max. {n} Personen',
],
'exc.detail.date' => [ 'it' => 'Data', 'en' => 'Date', 'ru' => 'Дата', 'es' => 'Fecha', 'de' => 'Datum' ],
'exc.detail.participants' => [ 'it' => 'Partecipanti', 'en' => 'Participants', 'ru' => 'Участников', 'es' => 'Participantes', 'de' => 'Teilnehmer' ],
'exc.detail.pickup' => [
    'it' => 'Hotel / Appartamento', 'en' => 'Hotel / Apartment', 'ru' => 'Отель / Апартаменты', 'es' => 'Hotel / Apartamento', 'de' => 'Hotel / Apartment',
],
'exc.detail.pickup_ph' => [
    'it' => 'es. Naama Bay Sea View', 'en' => 'e.g. Naama Bay Sea View', 'ru' => 'напр. Naama Bay Sea View', 'es' => 'ej. Naama Bay Sea View', 'de' => 'z. B. Naama Bay Sea View',
],
'exc.detail.book' => [
    'it' => 'Prenota', 'en' => 'Book', 'ru' => 'Забронировать', 'es' => 'Reservar', 'de' => 'Buchen',
],

// ========== TRANSFER ==========
'tr.title' => [
    'it' => 'Ti aspettiamo all\'aeroporto.',
    'en' => 'We pick you up at the airport.',
    'ru' => 'Встретим вас в аэропорту.',
    'es' => 'Te recogemos en el aeropuerto.',
    'de' => 'Wir holen Sie am Flughafen ab.',
],
'tr.sub' => [
    'it' => 'Transfer privato dall\'aeroporto di Sharm El Sheikh a qualsiasi villaggio o appartamento. Auto fino a 4 persone o minibus 7 posti.',
    'en' => 'Private transfer from Sharm El Sheikh airport to any resort or apartment. Cars up to 4 people or 7-seater minibuses.',
    'ru' => 'Частный трансфер из аэропорта Шарм-эль-Шейха в любой отель или апартаменты. Авто до 4 человек или микроавтобус на 7 мест.',
    'es' => 'Traslado privado desde el aeropuerto de Sharm El Sheikh a cualquier complejo o apartamento. Coches hasta 4 personas o minibús de 7 plazas.',
    'de' => 'Privater Transfer vom Flughafen Sharm El Sheikh zu jedem Resort oder Apartment. Autos bis 4 Personen oder 7-Sitzer-Minibus.',
],
'tr.routes' => [
    'it' => 'Tratte disponibili', 'en' => 'Available routes', 'ru' => 'Доступные маршруты', 'es' => 'Rutas disponibles', 'de' => 'Verfügbare Strecken',
],
'tr.up_to' => [
    'it' => 'fino a {n} persone', 'en' => 'up to {n} people', 'ru' => 'до {n} человек', 'es' => 'hasta {n} personas', 'de' => 'bis zu {n} Personen',
],
'tr.flight_date' => [ 'it' => 'Data arrivo', 'en' => 'Arrival date', 'ru' => 'Дата прилёта', 'es' => 'Fecha de llegada', 'de' => 'Ankunftsdatum' ],
'tr.flight_time' => [ 'it' => 'Ora volo', 'en' => 'Flight time', 'ru' => 'Время рейса', 'es' => 'Hora del vuelo', 'de' => 'Flugzeit' ],
'tr.flight_no' => [ 'it' => 'Numero volo', 'en' => 'Flight number', 'ru' => 'Номер рейса', 'es' => 'Número de vuelo', 'de' => 'Flugnummer' ],
'tr.dest' => [ 'it' => 'Indirizzo destinazione', 'en' => 'Destination address', 'ru' => 'Адрес назначения', 'es' => 'Dirección de destino', 'de' => 'Zieladresse' ],
'tr.dest_ph' => [
    'it' => 'hotel o appartamento', 'en' => 'hotel or apartment', 'ru' => 'отель или апартаменты', 'es' => 'hotel o apartamento', 'de' => 'Hotel oder Apartment',
],
'tr.passengers' => [ 'it' => 'Passeggeri', 'en' => 'Passengers', 'ru' => 'Пассажиров', 'es' => 'Pasajeros', 'de' => 'Passagiere' ],
'tr.book_btn' => [
    'it' => 'Prenota transfer', 'en' => 'Book transfer', 'ru' => 'Заказать трансфер', 'es' => 'Reservar traslado', 'de' => 'Transfer buchen',
],
'tr.pay_note' => [
    'it' => 'Pagamento direttamente al conducente o online.',
    'en' => 'Pay directly to the driver or online.',
    'ru' => 'Оплата напрямую водителю или онлайн.',
    'es' => 'Pago directamente al conductor o en línea.',
    'de' => 'Bezahlung direkt beim Fahrer oder online.',
],
'tr.success' => [
    'it' => 'Transfer prenotato!', 'en' => 'Transfer booked!', 'ru' => 'Трансфер забронирован!', 'es' => '¡Traslado reservado!', 'de' => 'Transfer gebucht!',
],

// ========== CONTACT ==========
'ct.badge' => [
    'it' => 'Parliamo', 'en' => 'Get in touch', 'ru' => 'Свяжитесь с нами', 'es' => 'Hablemos', 'de' => 'Schreib uns',
],
'ct.title' => [
    'it' => 'Una mano, sempre.', 'en' => 'A hand, always.', 'ru' => 'Помощь рядом, всегда.', 'es' => 'Una mano, siempre.', 'de' => 'Immer für dich da.',
],
'ct.sub' => [
    'it' => 'Per prenotazioni, modifiche o consigli sul tuo soggiorno a Sharm El Sheikh: rispondiamo entro poche ore, sette giorni su sette.',
    'en' => 'Bookings, changes or advice for your Sharm stay — we reply within hours, seven days a week.',
    'ru' => 'Бронирование, изменения или советы по отдыху в Шарме — отвечаем в течение нескольких часов, семь дней в неделю.',
    'es' => 'Reservas, cambios o consejos para tu estancia en Sharm — respondemos en pocas horas, los 7 días.',
    'de' => 'Buchungen, Änderungen oder Tipps für deinen Sharm-Aufenthalt — Antwort in wenigen Stunden, sieben Tage die Woche.',
],
'ct.card.email' => [ 'it' => 'Email', 'en' => 'Email', 'ru' => 'Email', 'es' => 'Email', 'de' => 'E-Mail' ],
'ct.card.email.note' => [
    'it' => 'Risposta entro poche ore', 'en' => 'Reply within hours', 'ru' => 'Ответ в течение нескольких часов', 'es' => 'Respuesta en pocas horas', 'de' => 'Antwort innerhalb weniger Stunden',
],
'ct.card.phone' => [
    'it' => 'Telefono / WhatsApp', 'en' => 'Phone / WhatsApp', 'ru' => 'Телефон / WhatsApp', 'es' => 'Teléfono / WhatsApp', 'de' => 'Telefon / WhatsApp',
],
'ct.card.phone.note' => [
    'it' => 'Lun-Dom 9-22', 'en' => 'Mon-Sun 9-22', 'ru' => 'Пн-Вс 9-22', 'es' => 'Lun-Dom 9-22', 'de' => 'Mo-So 9-22 Uhr',
],
'ct.card.where' => [
    'it' => 'Dove siamo', 'en' => 'Where we are', 'ru' => 'Где мы', 'es' => 'Dónde estamos', 'de' => 'Wo wir sind',
],
'ct.card.where.value' => [
    'it' => 'Sharm El Sheikh, Egitto', 'en' => 'Sharm El Sheikh, Egypt', 'ru' => 'Шарм-эль-Шейх, Египет', 'es' => 'Sharm El Sheikh, Egipto', 'de' => 'Sharm El Sheikh, Ägypten',
],
'ct.card.where.note' => [
    'it' => 'Tutte le proprietà', 'en' => 'All our properties', 'ru' => 'Все наши объекты', 'es' => 'Todas las propiedades', 'de' => 'Alle Objekte',
],
'ct.tailored.title' => [
    'it' => "Cerchi un soggiorno\nsu misura a Sharm?",
    'en' => "Looking for a tailored\nSharm getaway?",
    'ru' => "Ищете персональный\nотдых в Шарме?",
    'es' => "¿Buscas una estancia\na medida en Sharm?",
    'de' => "Du suchst einen Sharm-\nUrlaub nach Maß?",
],
'ct.tailored.sub' => [
    'it' => 'Anniversari, lune di miele, vacanze lunghe, gruppi diving: scrivici e ti aiutiamo a trovare l\'appartamento e l\'esperienza giusta.',
    'en' => 'Anniversaries, honeymoons, long stays, diving groups — message us and we\'ll find the right apartment and experience for you.',
    'ru' => 'Годовщины, медовый месяц, долгие отпуска, дайв-группы — напишите, и мы подберём вам подходящие апартаменты и программу.',
    'es' => 'Aniversarios, lunas de miel, estancias largas, grupos de buceo — escríbenos y te ayudamos a encontrar el apartamento y la experiencia ideal.',
    'de' => 'Jubiläen, Flitterwochen, Langzeitaufenthalte, Tauchgruppen — schreib uns, wir finden das passende Apartment und Erlebnis für dich.',
],
'ct.email_us' => [
    'it' => 'Scrivici una email', 'en' => 'Send us an email', 'ru' => 'Написать письмо', 'es' => 'Envíanos un email', 'de' => 'Schreib uns eine E-Mail',
],

// ========== FOOTER ==========
'foot.tagline' => [
    'it' => 'Appartamenti selezionati a Sharm El Sheikh: Naama Bay, Hadaba, Sharks Bay, Old Market e Nabq. Gestione diretta in italiano, soggiorni curati, prezzi trasparenti.',
    'en' => 'Hand-picked apartments in Sharm El Sheikh: Naama Bay, Hadaba, Sharks Bay, Old Market and Nabq. Direct management, curated stays, transparent pricing.',
    'ru' => 'Тщательно подобранные апартаменты в Шарм-эль-Шейхе: Naama Bay, Hadaba, Sharks Bay, Old Market и Nabq. Прямое управление, продуманные программы пребывания, прозрачные цены.',
    'es' => 'Apartamentos seleccionados en Sharm El Sheikh: Naama Bay, Hadaba, Sharks Bay, Old Market y Nabq. Gestión directa, estancias cuidadas, precios transparentes.',
    'de' => 'Handverlesene Apartments in Sharm El Sheikh: Naama Bay, Hadaba, Sharks Bay, Old Market und Nabq. Direktverwaltung, durchdachte Aufenthalte, transparente Preise.',
],
'foot.explore' => [ 'it' => 'Esplora', 'en' => 'Explore', 'ru' => 'Разделы', 'es' => 'Explora', 'de' => 'Entdecken' ],
'foot.support' => [ 'it' => 'Supporto', 'en' => 'Support', 'ru' => 'Поддержка', 'es' => 'Soporte', 'de' => 'Support' ],
'foot.faq' => [ 'it' => 'FAQ', 'en' => 'FAQ', 'ru' => 'Вопросы', 'es' => 'Preguntas frecuentes', 'de' => 'FAQ' ],
'foot.terms' => [ 'it' => 'Termini', 'en' => 'Terms', 'ru' => 'Условия', 'es' => 'Términos', 'de' => 'AGB' ],
'foot.privacy' => [ 'it' => 'Privacy', 'en' => 'Privacy', 'ru' => 'Конфиденциальность', 'es' => 'Privacidad', 'de' => 'Datenschutz' ],
'foot.contact_us' => [ 'it' => 'Contattaci', 'en' => 'Contact us', 'ru' => 'Связаться', 'es' => 'Contáctanos', 'de' => 'Kontakt' ],
'foot.response_in' => [ 'it' => 'Risposta entro', 'en' => 'Reply within', 'ru' => 'Ответ в течение', 'es' => 'Respuesta en', 'de' => 'Antwort innerhalb' ],
'foot.response_time' => [
    'it' => 'poche ore, 7/7', 'en' => 'a few hours, 7/7', 'ru' => 'нескольких часов, 7/7', 'es' => 'pocas horas, 7/7', 'de' => 'weniger Stunden, 7/7',
],
'foot.copyright' => [
    'it' => 'Tutti i diritti riservati.', 'en' => 'All rights reserved.', 'ru' => 'Все права защищены.', 'es' => 'Todos los derechos reservados.', 'de' => 'Alle Rechte vorbehalten.',
],
'foot.crafted' => [
    'it' => 'Progettato con cura', 'en' => 'Crafted with care', 'ru' => 'Сделано с заботой', 'es' => 'Diseñado con cuidado', 'de' => 'Mit Sorgfalt gemacht',
],

// ========== META ==========
'meta.home_title' => [
    'it' => 'Casa Vacanza · Affitti brevi premium',
    'en' => 'Casa Vacanza · Premium short-stay rentals',
    'ru' => 'Casa Vacanza · Премиум-аренда на короткий срок',
    'es' => 'Casa Vacanza · Alquileres premium de corta estancia',
    'de' => 'Casa Vacanza · Premium-Kurzzeitvermietungen',
],
'meta.default_desc' => [
    'it' => 'Appartamenti a Sharm El Sheikh selezionati e gestiti direttamente in italiano. Naama Bay, Hadaba, Sharks Bay, Old Market, Nabq.',
    'en' => 'Apartments in Sharm El Sheikh, hand-picked and directly managed. Naama Bay, Hadaba, Sharks Bay, Old Market, Nabq.',
    'ru' => 'Апартаменты в Шарм-эль-Шейхе — авторский подбор и прямое управление. Naama Bay, Hadaba, Sharks Bay, Old Market, Nabq.',
    'es' => 'Apartamentos en Sharm El Sheikh seleccionados y gestionados directamente. Naama Bay, Hadaba, Sharks Bay, Old Market, Nabq.',
    'de' => 'Apartments in Sharm El Sheikh — handverlesen und direkt verwaltet. Naama Bay, Hadaba, Sharks Bay, Old Market, Nabq.',
],
'meta.lang_label' => [
    'it' => 'Lingua', 'en' => 'Language', 'ru' => 'Язык', 'es' => 'Idioma', 'de' => 'Sprache',
],

];

function t(string $key, array $vars = []): string {
    global $TRANSLATIONS;
    $lang = currentLang();
    $val = $TRANSLATIONS[$key][$lang] ?? $TRANSLATIONS[$key]['it'] ?? $key;
    foreach ($vars as $k => $v) {
        $val = str_replace('{' . $k . '}', (string)$v, $val);
    }
    return $val;
}
