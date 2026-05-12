<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/messages.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
$reset = isset($_GET['reset']) && $_GET['reset'] === 'YES';

$schema = <<<SQL
CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(32) PRIMARY KEY,
  email VARCHAR(190) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(120),
  role VARCHAR(30) NOT NULL DEFAULT 'admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apartments (
  id VARCHAR(32) PRIMARY KEY,
  slug VARCHAR(190) UNIQUE NOT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT,
  address VARCHAR(255) DEFAULT '',
  city VARCHAR(120) DEFAULT '',
  country VARCHAR(120) DEFAULT 'Egitto',
  guests INT NOT NULL DEFAULT 2,
  bedrooms INT NOT NULL DEFAULT 1,
  bathrooms INT NOT NULL DEFAULT 1,
  beds INT NOT NULL DEFAULT 1,
  size_sqm INT,
  amenities TEXT,
  rules TEXT,
  check_in_time VARCHAR(8) DEFAULT '15:00',
  check_out_time VARCHAR(8) DEFAULT '11:00',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 80,
  weekly_price DECIMAL(10,2),
  biweekly_price DECIMAL(10,2),
  triweekly_price DECIMAL(10,2),
  monthly_price DECIMAL(10,2),
  weekend_price DECIMAL(10,2),
  cleaning_fee DECIMAL(10,2) NOT NULL DEFAULT 35,
  security_deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
  city_tax DECIMAL(10,2) NOT NULL DEFAULT 2,
  city_tax_max_nights INT NOT NULL DEFAULT 5,
  long_stay_discount_7 DECIMAL(5,2) NOT NULL DEFAULT 5,
  long_stay_discount_14 DECIMAL(5,2) NOT NULL DEFAULT 10,
  long_stay_discount_30 DECIMAL(5,2) NOT NULL DEFAULT 20,
  manager_commission_pct DECIMAL(5,2) NOT NULL DEFAULT 20,
  owner_name VARCHAR(190) DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  under_maintenance TINYINT(1) NOT NULL DEFAULT 0,
  cover_image VARCHAR(500),
  lat DECIMAL(10,6),
  lng DECIMAL(10,6),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS photos (
  id VARCHAR(32) PRIMARY KEY,
  apartment_id VARCHAR(32) NOT NULL,
  url VARCHAR(500) NOT NULL,
  alt VARCHAR(255) DEFAULT '',
  position INT NOT NULL DEFAULT 0,
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
  INDEX idx_apt (apartment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id VARCHAR(32) PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190),
  phone VARCHAR(40),
  document VARCHAR(80),
  document_type VARCHAR(40),
  country VARCHAR(80),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
  id VARCHAR(32) PRIMARY KEY,
  code VARCHAR(40) UNIQUE NOT NULL,
  apartment_id VARCHAR(32) NOT NULL,
  customer_id VARCHAR(32) NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  guests INT NOT NULL DEFAULT 1,
  nights INT NOT NULL DEFAULT 1,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  source VARCHAR(30) NOT NULL DEFAULT 'direct',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  cleaning_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  city_tax DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
  notes TEXT,
  contract_url VARCHAR(500),
  qr_code VARCHAR(500),
  coupon_code VARCHAR(40),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (apartment_id) REFERENCES apartments(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  INDEX idx_apt_dates (apartment_id, check_in, check_out),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id VARCHAR(32) PRIMARY KEY,
  booking_id VARCHAR(32) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(30) NOT NULL DEFAULT 'cash',
  type VARCHAR(30) NOT NULL DEFAULT 'deposit',
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notes TEXT,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  INDEX idx_bk (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
  id VARCHAR(32) PRIMARY KEY,
  booking_id VARCHAR(32) NOT NULL,
  url VARCHAR(500) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'id',
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  id VARCHAR(32) PRIMARY KEY,
  apartment_id VARCHAR(32),
  category VARCHAR(60) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  date DATE NOT NULL DEFAULT (CURRENT_DATE),
  description TEXT,
  attachment VARCHAR(500),
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE SET NULL,
  INDEX idx_date (date),
  INDEX idx_apt (apartment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS price_rules (
  id VARCHAR(32) PRIMARY KEY,
  apartment_id VARCHAR(32) NOT NULL,
  name VARCHAR(120) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  price_per_night DECIMAL(10,2) NOT NULL,
  min_nights INT NOT NULL DEFAULT 1,
  priority INT NOT NULL DEFAULT 0,
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
  INDEX idx_apt_dates (apartment_id, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS date_blocks (
  id VARCHAR(32) PRIMARY KEY,
  apartment_id VARCHAR(32) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason VARCHAR(255),
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
  INDEX idx_apt (apartment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_templates (
  id VARCHAR(32) PRIMARY KEY,
  template_key VARCHAR(60) UNIQUE NOT NULL,
  name VARCHAR(120) NOT NULL,
  channel VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
  subject VARCHAR(255),
  body TEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id VARCHAR(32) PRIMARY KEY,
  apartment_id VARCHAR(32) NOT NULL,
  booking_id VARCHAR(32),
  author_name VARCHAR(120) NOT NULL,
  rating INT NOT NULL DEFAULT 5,
  title VARCHAR(255),
  body TEXT,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
  INDEX idx_apt (apartment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coupons (
  id VARCHAR(32) PRIMARY KEY,
  code VARCHAR(60) UNIQUE NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'percent',
  value DECIMAL(10,2) NOT NULL,
  valid_from DATE,
  valid_until DATE,
  max_uses INT,
  uses INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id VARCHAR(32) PRIMARY KEY,
  type VARCHAR(40) NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT,
  link VARCHAR(500),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_read (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
  id VARCHAR(32) PRIMARY KEY,
  user_id VARCHAR(32),
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(60),
  entity_id VARCHAR(32),
  details TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id VARCHAR(32) PRIMARY KEY,
  slug VARCHAR(190) UNIQUE NOT NULL,
  type VARCHAR(40) NOT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT,
  cover_image VARCHAR(500),
  gallery TEXT,
  daily_price DECIMAL(10,2),
  weekend_price DECIMAL(10,2),
  weekly_price DECIMAL(10,2),
  biweekly_price DECIMAL(10,2),
  triweekly_price DECIMAL(10,2),
  monthly_price DECIMAL(10,2),
  long_stay_discount_7 DECIMAL(5,2) DEFAULT 0,
  long_stay_discount_14 DECIMAL(5,2) DEFAULT 0,
  long_stay_discount_30 DECIMAL(5,2) DEFAULT 0,
  cleaning_fee DECIMAL(10,2) DEFAULT 0,
  security_deposit DECIMAL(10,2) DEFAULT 0,
  price_per_person DECIMAL(10,2),
  price_per_group DECIMAL(10,2),
  duration_hours DECIMAL(5,1),
  group_size_min INT DEFAULT 1,
  group_size_max INT DEFAULT 10,
  resort_name VARCHAR(190),
  resort_address VARCHAR(255),
  features TEXT,
  includes TEXT,
  excludes TEXT,
  meeting_point VARCHAR(255),
  schedule_days VARCHAR(255),
  from_location VARCHAR(190),
  to_location VARCHAR(190),
  vehicle_capacity INT,
  insurance_included TINYINT(1) DEFAULT 0,
  fuel_included TINYINT(1) DEFAULT 0,
  helmet_included TINYINT(1) DEFAULT 0,
  min_age INT,
  license_required TINYINT(1) DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  position INT DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type (type),
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS zones (
  id VARCHAR(32) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'zone',
  description TEXT,
  image VARCHAR(500),
  position INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_subscriptions (
  id VARCHAR(32) PRIMARY KEY,
  endpoint TEXT NOT NULL,
  p256dh VARCHAR(255) NOT NULL,
  auth VARCHAR(255) NOT NULL,
  user_agent VARCHAR(500),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_endpoint (endpoint(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_bookings (
  id VARCHAR(32) PRIMARY KEY,
  code VARCHAR(40) UNIQUE NOT NULL,
  service_id VARCHAR(32) NOT NULL,
  customer_id VARCHAR(32) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE,
  pickup_time VARCHAR(8),
  participants INT DEFAULT 1,
  pickup_location VARCHAR(255),
  dropoff_location VARCHAR(255),
  flight_number VARCHAR(40),
  details TEXT,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  source VARCHAR(30) NOT NULL DEFAULT 'direct',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  extras DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
  notes TEXT,
  coupon_code VARCHAR(40),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (service_id) REFERENCES services(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  INDEX idx_svc (service_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cleaning_tasks (
  id VARCHAR(32) PRIMARY KEY,
  label VARCHAR(255) NOT NULL,
  description VARCHAR(500),
  position INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cleaning_sessions (
  id VARCHAR(32) PRIMARY KEY,
  booking_id VARCHAR(32),
  apartment_id VARCHAR(32) NOT NULL,
  scheduled_date DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  started_at DATETIME NULL DEFAULT NULL,
  completed_at DATETIME NULL DEFAULT NULL,
  cleaner_notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id)   REFERENCES bookings(id)   ON DELETE SET NULL,
  INDEX idx_date (scheduled_date),
  INDEX idx_status (status),
  INDEX idx_apt (apartment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cleaning_session_items (
  id VARCHAR(32) PRIMARY KEY,
  session_id VARCHAR(32) NOT NULL,
  task_id VARCHAR(32),
  label_snapshot VARCHAR(255) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  checked TINYINT(1) NOT NULL DEFAULT 0,
  checked_at DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (session_id) REFERENCES cleaning_sessions(id) ON DELETE CASCADE,
  INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

foreach (preg_split('/;\s*\n/', $schema) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt) $pdo->exec($stmt);
}
echo "✓ Schema creato/verificato\n";

// === Migrazioni idempotenti per DB esistenti ===========================
// Aggiunge nuove colonne se mancano. MySQL non supporta IF NOT EXISTS su
// ADD COLUMN in tutte le versioni, quindi controlliamo prima con SHOW COLUMNS.
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): bool {
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'")->fetchColumn();
    if ($exists) return false;
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
    return true;
}
$migrations = [
    ['apartments', 'manager_commission_pct', 'DECIMAL(5,2) NOT NULL DEFAULT 20'],
    ['apartments', 'owner_name', "VARCHAR(190) DEFAULT ''"],
    ['push_subscriptions', 'role', "VARCHAR(20) NOT NULL DEFAULT 'admin'"],
    ['cleaning_sessions', 'reminder_sent_at', "DATETIME NULL DEFAULT NULL"],
    ['apartments', 'block_number', "VARCHAR(20) DEFAULT ''"],
    ['apartments', 'map_x', "DECIMAL(6,3) NULL DEFAULT NULL"],
    ['apartments', 'map_y', "DECIMAL(6,3) NULL DEFAULT NULL"],
    ['apartments', 'cleaner_directions', "TEXT"],
];
foreach ($migrations as [$tbl, $col, $def]) {
    if (ensureColumn($pdo, $tbl, $col, $def)) echo "✓ Aggiunta colonna $tbl.$col\n";
}

if ($reset) {
    echo "⚠ RESET RICHIESTO — pulizia dati...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['payments','documents','bookings','service_bookings','customers','date_blocks','price_rules','photos','reviews','expenses','notifications','activity_logs','coupons','apartments','services','message_templates','settings'] as $t) {
        $pdo->exec("TRUNCATE TABLE $t");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "✓ Dati cancellati\n";
}

ensureAdminUser();
echo "✓ Utente admin: " . cfg('admin_default.email') . " / " . cfg('admin_default.password') . "\n";

foreach (defaultTemplates() as $t) {
    $existing = row('SELECT id FROM message_templates WHERE template_key = ?', [$t['key']]);
    if ($existing) continue;
    q('INSERT INTO message_templates (id, template_key, name, channel, subject, body, active) VALUES (?, ?, ?, ?, ?, ?, 1)',
        [newId(), $t['key'], $t['name'], $t['channel'], $t['subject'], $t['body']]);
}
echo "✓ Template messaggi caricati\n";

// Seed zone/villaggi default (idempotente)
$defaultZones = [
    ['name' => 'Naama Bay',   'slug' => 'naama-bay',   'kind' => 'zone',     'image' => '/assets/sharm/zone_naama_bay.jpg',  'position' => 1],
    ['name' => 'Hadaba',      'slug' => 'hadaba',      'kind' => 'zone',     'image' => '/assets/sharm/zone_hadaba.jpg',     'position' => 2],
    ['name' => 'Sharks Bay',  'slug' => 'sharks-bay',  'kind' => 'zone',     'image' => '/assets/sharm/zone_sharks_bay.jpg', 'position' => 3],
    ['name' => 'Nabq Bay',    'slug' => 'nabq-bay',    'kind' => 'villaggio','image' => '/assets/sharm/zone_nabq_bay.jpg',   'position' => 4],
    ['name' => 'Old Market',  'slug' => 'old-market',  'kind' => 'zone',     'image' => '/assets/sharm/zone_old_market.jpg', 'position' => 5],
];
foreach ($defaultZones as $z) {
    $exists = row('SELECT id FROM zones WHERE slug = ?', [$z['slug']]);
    if ($exists) continue;
    q('INSERT INTO zones (id, name, slug, kind, image, position) VALUES (?, ?, ?, ?, ?, ?)',
        [newId(), $z['name'], $z['slug'], $z['kind'], $z['image'], $z['position']]);
}
echo "✓ Zone/villaggi caricati\n";

// Seed checklist pulizia default + token cleaner (idempotente)
$defaultCleaningTasks = [
    ['Cambio lenzuola e federe in tutte le camere', null],
    ['Cambio asciugamani (bagno, viso, bidet)', null],
    ['Bagno: WC, doccia, lavandino, specchio puliti', 'Controlla anche il bidet e cambia il tappetino se sporco'],
    ['Cucina: piano cottura, forno, microonde, lavello', null],
    ['Frigorifero: vuoto, pulito, scongelato se serve', 'Butta cibo dimenticato dagli ospiti'],
    ['Pavimenti aspirati e lavati', null],
    ['Polvere su mobili, mensole, comodini, TV', null],
    ['Aria condizionata: filtri puliti, telecomando funzionante', null],
    ['Spazzatura svuotata (umido + secco) e sacchetti nuovi', null],
    ['Kit benvenuto: acqua, caffè, zucchero, sale', null],
    ['Wifi: foglietto con nome rete e password visibile', null],
    ['Tende, finestre, balcone in ordine', null],
    ['Controllo lampadine, prese, rubinetti, scarichi', 'Segnala in note se qualcosa non funziona'],
    ['Oggetti dimenticati dai clienti precedenti', 'Mettili in un sacchetto e avvisami'],
    ['Foto finali dell\'appartamento pronte', 'Mandami 2-3 foto per conferma'],
];
$existsTasks = (int)val('SELECT COUNT(*) FROM cleaning_tasks');
if (!$existsTasks) {
    $pos = 1;
    foreach ($defaultCleaningTasks as [$label, $desc]) {
        q('INSERT INTO cleaning_tasks (id, label, description, position, active) VALUES (?, ?, ?, ?, 1)',
            [newId(), $label, $desc, $pos++]);
    }
    echo "✓ Checklist pulizie default caricata (" . count($defaultCleaningTasks) . " voci)\n";
}

// Token segreto per il link condivisibile alle pulizie (idempotente)
$existingToken = val('SELECT setting_value FROM settings WHERE setting_key = ?', ['cleaner_link_token']);
if (!$existingToken) {
    $newToken = bin2hex(random_bytes(16));
    q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', ['cleaner_link_token', $newToken]);
    echo "✓ Token link pulizie generato\n";
}

$count = (int)val('SELECT COUNT(*) FROM apartments');
if ($count > 0 && !$reset) {
    echo "✓ Dati demo già presenti ($count appartamenti) — usa /setup.php?reset=YES per ricaricare\n";
} else {
    $demos = [
        ['slug' => 'naama-bay-sea-view', 'name' => 'Naama Bay Sea View',
         'description' => "Splendido appartamento sul lungomare di Naama Bay con balcone vista mare e tramonto sul Mar Rosso. A 2 minuti a piedi dalla spiaggia e dai migliori ristoranti della Promenade.\n\nIdeale per coppie e piccole famiglie che vogliono il cuore di Sharm a portata di mano.",
         'address' => 'Naama Bay Promenade, Sharm El Sheikh', 'city' => 'Naama Bay', 'country' => 'Egitto',
         'guests' => 4, 'bedrooms' => 2, 'bathrooms' => 1, 'beds' => 3, 'size_sqm' => 75,
         'amenities' => json_encode(['WiFi','Aria condizionata','Vista mare','Cucina','TV','Smart TV','Balcone','Lavatrice','Asciugamani','Phon']),
         'rules' => "Vietato fumare\nNo feste rumorose\nCheck-in dalle 15:00\nCheck-out entro le 11:00\nDocumento d'identità obbligatorio",
         'base_price' => 95, 'weekend_price' => 110, 'weekly_price' => 600, 'biweekly_price' => 1100, 'monthly_price' => 2200,
         'cleaning_fee' => 35, 'city_tax' => 0, 'city_tax_max_nights' => 0, 'security_deposit' => 100,
         'cover_image' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&q=80',
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80',
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&q=80',
            'https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1200&q=80',
            'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80',
         ]],
        ['slug' => 'hadaba-pool-residence', 'name' => 'Hadaba Pool Residence',
         'description' => "Bilocale moderno in residence con piscina ad Hadaba, zona elegante e tranquilla in collina con vista sulla baia. Accesso libero alla piscina e alla spiaggia privata del residence (navetta gratuita).\n\nPerfetto per coppie che cercano relax e privacy.",
         'address' => 'Hadaba, Ras Um Sid, Sharm El Sheikh', 'city' => 'Hadaba', 'country' => 'Egitto',
         'guests' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'beds' => 1, 'size_sqm' => 50,
         'amenities' => json_encode(['WiFi','Aria condizionata','Piscina','Spiaggia privata','Cucina','TV','Parcheggio','Balcone']),
         'rules' => "No fumo\nUso piscina 8-22\nNavetta spiaggia su prenotazione",
         'base_price' => 70, 'weekend_price' => 85, 'weekly_price' => 440, 'monthly_price' => 1600,
         'cleaning_fee' => 30, 'city_tax' => 0, 'city_tax_max_nights' => 0, 'security_deposit' => 100,
         'cover_image' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1200&q=80',
            'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=1200&q=80',
            'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=1200&q=80',
            'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1200&q=80',
         ]],
        ['slug' => 'sharks-bay-suite', 'name' => 'Sharks Bay Diving Suite',
         'description' => "Suite a Sharks Bay, paradiso del diving. Piscina infinity, accesso diretto al reef, perfetta per chi ama snorkeling e immersioni. Centro diving convenzionato a 100m.\n\nAria condizionata in ogni stanza, terrazza panoramica.",
         'address' => 'Sharks Bay, Sharm El Sheikh', 'city' => 'Sharks Bay', 'country' => 'Egitto',
         'guests' => 3, 'bedrooms' => 1, 'bathrooms' => 1, 'beds' => 2, 'size_sqm' => 60,
         'amenities' => json_encode(['WiFi','Aria condizionata','Piscina','Vista mare','Cucina','TV','Diving center']),
         'base_price' => 110, 'weekend_price' => 130, 'weekly_price' => 700, 'monthly_price' => 2500,
         'cleaning_fee' => 40, 'city_tax' => 0, 'city_tax_max_nights' => 0, 'security_deposit' => 150,
         'cover_image' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80',
            'https://images.unsplash.com/photo-1559599189-fe84dea4eb79?w=1200&q=80',
            'https://images.unsplash.com/photo-1582610116397-edb318620f90?w=1200&q=80',
            'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&q=80',
         ]],
        ['slug' => 'old-market-boho', 'name' => 'Old Market Boho · Sharm Old Town',
         'description' => "Caratteristico appartamento nel cuore di Old Market, tra spezie, bazar e atmosfera autentica egiziana. Tetti curati, archi tipici, arredamento boho con tessuti locali.\n\nA pochi passi dai migliori ristoranti tradizionali e dal mercato.",
         'address' => 'Old Market (Sok El Kadeem), Sharm El Sheikh', 'city' => 'Old Market', 'country' => 'Egitto',
         'guests' => 4, 'bedrooms' => 2, 'bathrooms' => 1, 'beds' => 2, 'size_sqm' => 65,
         'amenities' => json_encode(['WiFi','Aria condizionata','Cucina','TV','Lavatrice']),
         'base_price' => 55, 'weekend_price' => 65, 'weekly_price' => 350, 'monthly_price' => 1300,
         'cleaning_fee' => 25, 'city_tax' => 0, 'city_tax_max_nights' => 0, 'security_deposit' => 80,
         'cover_image' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&q=80',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&q=80',
            'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=1200&q=80',
            'https://images.unsplash.com/photo-1567016376408-0226e4d0c1ea?w=1200&q=80',
         ]],
        ['slug' => 'nabq-bay-family', 'name' => 'Nabq Bay Family',
         'description' => "Spazioso appartamento per famiglie a Nabq Bay, zona tranquilla con spiagge bianche e mare cristallino. Tre camere, due bagni, salotto enorme. A 5 minuti dalle migliori escursioni nel deserto.\n\nIdeale per gruppi e famiglie con bambini.",
         'address' => 'Nabq Bay Resort Area, Sharm El Sheikh', 'city' => 'Nabq Bay', 'country' => 'Egitto',
         'guests' => 6, 'bedrooms' => 3, 'bathrooms' => 2, 'beds' => 5, 'size_sqm' => 110,
         'amenities' => json_encode(['WiFi','Aria condizionata','Piscina','Vista mare','Cucina','TV','Smart TV','Lavatrice','Parcheggio','Lettino bimbi','Asciugamani']),
         'rules' => "Bambini benvenuti\nAnimali su richiesta\nNo feste",
         'base_price' => 130, 'weekend_price' => 150, 'weekly_price' => 820, 'biweekly_price' => 1500, 'monthly_price' => 2800,
         'cleaning_fee' => 50, 'city_tax' => 0, 'city_tax_max_nights' => 0, 'security_deposit' => 200,
         'cover_image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80',
            'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=1200&q=80',
            'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1200&q=80',
            'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1200&q=80',
         ]],
    ];

    $aptIds = [];
    foreach ($demos as $d) {
        $photos = $d['photos']; unset($d['photos']);
        $id = newId(); $aptIds[$d['slug']] = $id;
        $cols = implode(',', array_keys($d));
        $placeholders = implode(',', array_fill(0, count($d), '?'));
        q("INSERT INTO apartments (id, $cols) VALUES (?, $placeholders)", array_merge([$id], array_values($d)));
        foreach ($photos as $i => $url) {
            q('INSERT INTO photos (id, apartment_id, url, position) VALUES (?, ?, ?, ?)', [newId(), $id, $url, $i]);
        }
    }
    echo "✓ " . count($demos) . " appartamenti Sharm El Sheikh creati\n";

    $c1 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c1, 'Luca Bianchi', 'luca@example.com', '+393331112233', 'Italia']);
    $c2 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c2, 'Sarah Müller', 'sarah@example.com', '+491701234567', 'Germania']);
    $c3 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c3, 'Marco Rossi', 'marco@example.com', '+393344455667', 'Italia']);
    $c4 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c4, 'Famiglia Russo', 'russo@example.com', '+393355566778', 'Italia']);

    $today = strtotime('today');
    $bk = function($n) use ($today) { return date('Y-m-d', $today + $n * 86400); };

    $b1 = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$b1, 'PM-2026-0001', $aptIds['naama-bay-sea-view'], $c1, $bk(7), $bk(14), 7, 4, 600, 35, 635, 200, 'confirmed', 'direct']);
    q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)', [newId(), $b1, 200, 'deposit', 'bank']);

    $b2 = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, discount, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$b2, 'PM-2026-0002', $aptIds['hadaba-pool-residence'], $c2, $bk(-12), $bk(-5), 7, 2, 490, 30, 50, 470, 470, 'completed', 'airbnb']);
    q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)', [newId(), $b2, 470, 'balance', 'stripe']);

    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [newId(), 'PM-2026-0003', $aptIds['sharks-bay-suite'], $c3, $bk(20), $bk(27), 7, 2, 770, 40, 810, 0, 'pending', 'direct']);

    $b4 = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$b4, 'PM-2026-0004', $aptIds['nabq-bay-family'], $c4, $bk(35), $bk(45), 10, 5, 1300, 50, 1350, 400, 'confirmed', 'booking']);
    q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)', [newId(), $b4, 400, 'deposit', 'bank']);

    foreach ([
        [$aptIds['naama-bay-sea-view'], 'pulizie', 35, $bk(-5), 'Pulizia post check-out'],
        [$aptIds['naama-bay-sea-view'], 'manutenzione', 180, $bk(-30), 'Riparazione condizionatore'],
        [$aptIds['hadaba-pool-residence'], 'bollette', 65, $bk(-15), 'Energia + acqua'],
        [$aptIds['hadaba-pool-residence'], 'commissioni', 47, $bk(-5), 'Commissioni Airbnb 10%'],
        [$aptIds['old-market-boho'], 'internet', 25, $bk(-20), 'Internet mensile'],
        [$aptIds['nabq-bay-family'], 'pulizie', 50, $bk(-10), null],
        [$aptIds['sharks-bay-suite'], 'tasse', 120, $bk(-60), 'Imposte locali Q1'],
    ] as $e) {
        q('INSERT INTO expenses (id, apartment_id, category, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)',
            array_merge([newId()], $e));
    }

    foreach ([
        [$aptIds['naama-bay-sea-view'], 'Marco D.', 5, 'Tramonti indimenticabili', 'Vista pazzesca, posizione perfetta sulla Promenade. Patrizia super disponibile, ci ha consigliato i ristoranti migliori. Torneremo sicuro!'],
        [$aptIds['naama-bay-sea-view'], 'Laura B.', 5, 'Esattamente come nelle foto', 'Tutto curato nei dettagli, balcone vista mare da sogno. Aria condizionata perfetta anche ad agosto.'],
        [$aptIds['hadaba-pool-residence'], 'Andrea P.', 5, 'Relax assoluto', 'Piscina pulitissima, residence super tranquillo. La navetta per la spiaggia è una comodità incredibile.'],
        [$aptIds['sharks-bay-suite'], 'Sofia R.', 5, null, 'Per fare diving non c\'è posto migliore. Centro diving a 2 passi, reef incredibile direttamente dalla spiaggia.'],
        [$aptIds['old-market-boho'], 'Giulia & Marco', 4, 'Sharm autentica', 'Un\'esperienza diversa dai soliti resort, immersi nella vera Sharm. Il bazar a 50m è una favola.'],
        [$aptIds['nabq-bay-family'], 'Famiglia Conti', 5, 'Perfetto per famiglie', 'Spazioso, con tutto il necessario per i bambini. Nabq è ideale per chi cerca tranquillità ma vuole essere vicino alle escursioni.'],
    ] as $r) {
        q('INSERT INTO reviews (id, apartment_id, author_name, rating, title, body, approved) VALUES (?, ?, ?, ?, ?, ?, 1)',
            array_merge([newId()], $r));
    }
    echo "✓ Recensioni demo caricate\n";

    q('INSERT INTO coupons (id, code, type, value, max_uses) VALUES (?, ?, ?, ?, ?)', [newId(), 'SHARM10', 'percent', 10, 100]);
    q('INSERT INTO coupons (id, code, type, value, max_uses) VALUES (?, ?, ?, ?, ?)', [newId(), 'WELCOME50', 'fixed', 50, 50]);

    q('INSERT INTO notifications (id, type, title, body, link) VALUES (?, ?, ?, ?, ?)',
        [newId(), 'new_booking', 'Nuova prenotazione', 'Marco Rossi ha richiesto Sharks Bay Diving Suite', '/admin/prenotazioni.php']);

    echo "✓ Coupon e notifiche pronti\n";

    // ============== SERVIZI: VEICOLI / ESCURSIONI / TRANSFER ==============
    $services = [
      // ============ AUTO ============
      ['slug'=>'jeep-wrangler-open', 'type'=>'car', 'name'=>'Jeep Wrangler open-top',
       'description'=>"Iconica Jeep Wrangler open-top per esplorare il deserto del Sinai e le strade panoramiche. Aria condizionata, cambio manuale, perfetta per avventure off-road.\n\nIdeale per gite a Dahab, Ras Mohammed e zone desertiche.",
       'cover_image'=>'/assets/services/car_jeep.jpg',
       'gallery'=>json_encode([
         '/assets/services/car_jeep.jpg',
         '/assets/services/car_suv.jpg',
       ]),
       'daily_price'=>65, 'weekly_price'=>390, 'biweekly_price'=>720, 'triweekly_price'=>1020, 'monthly_price'=>1400,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>20,
       'cleaning_fee'=>15, 'security_deposit'=>250,
       'features'=>json_encode(['Aria condizionata','Cambio manuale','4x4','Tetto removibile','4 posti','Bluetooth']),
       'insurance_included'=>1, 'min_age'=>23, 'license_required'=>1],

      ['slug'=>'toyota-land-cruiser', 'type'=>'car', 'name'=>'Toyota Land Cruiser SUV (4x4)',
       'description'=>"SUV 4x4 spazioso 7 posti, perfetto per famiglie e gruppi. Cambio automatico, GPS integrato, comfort premium per lunghi viaggi.\n\nIdeale per escursioni nel deserto e tour culturali.",
       'cover_image'=>'/assets/services/car_suv.jpg',
       'gallery'=>json_encode([
         '/assets/services/car_suv.jpg',
         '/assets/services/car_jeep.jpg',
       ]),
       'daily_price'=>85, 'weekly_price'=>510, 'biweekly_price'=>950, 'triweekly_price'=>1370, 'monthly_price'=>1900,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>20,
       'cleaning_fee'=>20, 'security_deposit'=>350,
       'features'=>json_encode(['Aria condizionata','Cambio automatico','4x4','7 posti','GPS','Bluetooth','Cruise control']),
       'insurance_included'=>1, 'min_age'=>25, 'license_required'=>1],

      ['slug'=>'mercedes-e-class', 'type'=>'car', 'name'=>'Mercedes-Benz Classe E · premium sedan',
       'description'=>"Berlina premium per chi cerca il top del comfort. Cambio automatico, interni in pelle, sistema audio premium, perfetta per viaggi di lavoro o piacere.\n\nDisponibile con autista su richiesta.",
       'cover_image'=>'/assets/services/car_premium.jpg',
       'gallery'=>json_encode([
         '/assets/services/car_premium.jpg',
       ]),
       'daily_price'=>95, 'weekly_price'=>570, 'biweekly_price'=>1050, 'monthly_price'=>2200,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>20,
       'cleaning_fee'=>20, 'security_deposit'=>400,
       'features'=>json_encode(['Aria condizionata','Cambio automatico','Pelle','GPS','Bluetooth','Audio premium','Cruise control']),
       'insurance_included'=>1, 'min_age'=>25, 'license_required'=>1],

      ['slug'=>'hyundai-i10-city', 'type'=>'car', 'name'=>'Hyundai i10 · city car economica',
       'description'=>"Compatta agile e economica per spostarsi in città. Aria condizionata, consumi bassi, ideale per coppie.\n\nLa scelta più conveniente per soggiorni lunghi a Sharm.",
       'cover_image'=>'/assets/services/car_city.jpg',
       'gallery'=>json_encode([
         '/assets/services/car_city.jpg',
       ]),
       'daily_price'=>35, 'weekly_price'=>210, 'biweekly_price'=>380, 'triweekly_price'=>540, 'monthly_price'=>800,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>15,
       'cleaning_fee'=>10, 'security_deposit'=>150,
       'features'=>json_encode(['Aria condizionata','5 porte','Bluetooth','Bassi consumi']),
       'insurance_included'=>1, 'min_age'=>21, 'license_required'=>1],

      // ============ GOLF CART ============
      ['slug'=>'golf-cart-naama-bay-4posti', 'type'=>'golf_cart', 'name'=>'Golf cart 4 posti · Naama Bay',
       'description'=>"Golf cart elettrico 4 posti per spostarti nel resort e sulla Promenade di Naama Bay senza problemi di parcheggio.\n\nPerfetto per spostamenti brevi tra spiaggia, ristoranti e appartamento.",
       'cover_image'=>'/assets/services/golf_cart_4_naama.jpg',
       'gallery'=>json_encode(['/assets/services/golf_cart_4_naama.jpg']),
       'daily_price'=>25, 'weekly_price'=>150, 'monthly_price'=>500,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>50,
       'resort_name'=>'Naama Bay Resort', 'resort_address'=>'Naama Bay Promenade, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','4 posti','Tetto','Vano bagagli','Silenzioso','Eco']),
       'min_age'=>18, 'license_required'=>0],

      ['slug'=>'golf-cart-hadaba-6posti', 'type'=>'golf_cart', 'name'=>'Golf cart 6 posti · Hadaba',
       'description'=>"Modello familiare 6 posti per gruppi. Disponibile presso il resort di Hadaba con accesso libero alla zona privata e alla spiaggia.",
       'cover_image'=>'/assets/services/golf_cart_6_hadaba.jpg',
       'gallery'=>json_encode(['/assets/services/golf_cart_6_hadaba.jpg', '/assets/services/golf_cart_4_naama.jpg']),
       'daily_price'=>35, 'weekly_price'=>210, 'monthly_price'=>700,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>80,
       'resort_name'=>'Hadaba Resort', 'resort_address'=>'Hadaba, Ras Um Sid, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','6 posti','Tetto','Vano bagagli grande']),
       'min_age'=>18, 'license_required'=>0],

      // ============ SCOOTER ============
      ['slug'=>'scooter-50cc', 'type'=>'scooter', 'name'=>'Scooter 50cc',
       'description'=>"Scooter agile per spostarti tra Naama Bay, Old Market e Hadaba. Casco e bauletto inclusi. Disponibile presso il punto noleggio di Naama Bay.",
       'cover_image'=>'/assets/services/scooter_vespa.jpg',
       'gallery'=>json_encode(['/assets/services/scooter_vespa.jpg']),
       'daily_price'=>20, 'weekly_price'=>120, 'monthly_price'=>400,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>100,
       'resort_name'=>'Naama Bay · Punto noleggio',
       'resort_address'=>'Naama Bay Promenade angolo Sultan Hotel, Sharm El Sheikh',
       'features'=>json_encode(['50cc','2 posti','Casco incluso','Bauletto','Antifurto']),
       'helmet_included'=>1, 'min_age'=>18, 'license_required'=>1],

      // ============ MONOPATTINO ELETTRICO ============
      ['slug'=>'monopattino-elettrico', 'type'=>'escooter', 'name'=>'Monopattino elettrico',
       'description'=>"Monopattino elettrico per spostamenti rapidi sulla Promenade. Autonomia 30km, velocità max 25 km/h, pieghevole.\n\nDisponibile presso il resort di Nabq Bay con caschetto.",
       'cover_image'=>'/assets/services/escooter_modern.jpg',
       'gallery'=>json_encode(['/assets/services/escooter_modern.jpg']),
       'daily_price'=>15, 'weekly_price'=>80, 'monthly_price'=>250,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>50,
       'resort_name'=>'Nabq Bay Resort', 'resort_address'=>'Nabq Bay Resort Area, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','Autonomia 30km','Pieghevole','25 km/h max','Caschetto incluso','Display LED']),
       'helmet_included'=>1, 'min_age'=>16],

      // ============ ESCURSIONI IN BARCA ============
      ['slug'=>'ras-mohammed-snorkeling', 'type'=>'boat_excursion', 'name'=>'Ras Mohammed: snorkeling nel parco marino',
       'description'=>"Giornata in barca al Parco Nazionale di Ras Mohammed, l'area marina protetta più famosa del Mar Rosso. 3 stop di snorkeling tra coralli intatti, mangrovie e il celebre Lago Magico.\n\nPranzo a bordo incluso, accompagnamento di guida italiana esperta. Partenza dal porto turistico di Sharm El Sheikh.",
       'cover_image'=>'/assets/services/exc_ras_mohammed.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_ras_mohammed.jpg',
         '/assets/services/exc_dolphins.jpg',
         '/assets/services/exc_white_island.jpg',
       ]),
       'price_per_person'=>45, 'duration_hours'=>8, 'group_size_min'=>1, 'group_size_max'=>40,
       'meeting_point'=>'Porto turistico Sharm El Sheikh · pickup hotel ore 8:00',
       'includes'=>json_encode(['Transfer da/per hotel','Pranzo completo a bordo','Bevande analcoliche','Maschera, pinne e snorkel','Guida marina italiana','Assicurazione']),
       'excludes'=>json_encode(['Tasse parco (5 USD a persona, da pagare in loco)','Mance','Foto subacquee professionali']),
       'schedule_days'=>'Lun,Mar,Mer,Gio,Ven,Sab,Dom'],

      ['slug'=>'dolphin-house-cruise', 'type'=>'boat_excursion', 'name'=>'Crociera Dolphin House con pranzo a bordo',
       'description'=>"Crociera giornaliera al reef Dolphin House, dove un branco residente di delfini incontra spesso le barche. Snorkeling, sosta su isolotto sabbioso e pranzo gourmet a bordo.\n\nGiornata premium con barca limitata a 30 persone per garantire il massimo comfort.",
       'cover_image'=>'/assets/services/exc_dolphins.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_dolphins.jpg',
         '/assets/services/exc_ras_mohammed.jpg',
         '/assets/services/exc_white_island.jpg',
       ]),
       'price_per_person'=>75, 'duration_hours'=>9, 'group_size_min'=>2, 'group_size_max'=>30,
       'meeting_point'=>'Porto turistico Sharm El Sheikh · pickup hotel ore 7:30',
       'includes'=>json_encode(['Transfer A/R hotel','Pranzo gourmet a bordo','Bevande illimitate','Attrezzatura snorkeling','Guida italiana','Frutta fresca','Tassa parco inclusa']),
       'excludes'=>json_encode(['Mance','Bevande alcoliche extra']),
       'schedule_days'=>'Lun,Mer,Ven,Sab,Dom'],

      ['slug'=>'private-yacht-half-day', 'type'=>'boat_excursion', 'name'=>'Yacht privato: mezza giornata su misura',
       'description'=>"La tua giornata, il tuo programma: yacht 12 metri privato per gruppo fino a 8 persone. Itinerario flessibile (Tiran, White Island, Ras Mohammed), aperitivo a bordo, snorkeling dove preferisci.\n\nTariffa per yacht intero, perfetto per famiglie e gruppi che cercano privacy.",
       'cover_image'=>'/assets/services/yacht_private.jpg',
       'gallery'=>json_encode([
         '/assets/services/yacht_private.jpg',
         '/assets/services/exc_dolphins.jpg',
         '/assets/services/exc_white_island.jpg',
       ]),
       'price_per_person'=>0, 'price_per_group'=>890, 'duration_hours'=>5, 'group_size_min'=>1, 'group_size_max'=>8,
       'meeting_point'=>'Marina privata · pickup hotel ore 9:00',
       'includes'=>json_encode(['Yacht privato 12m','Skipper professionista','Aperitivo a bordo','Snack e frutta','Attrezzatura snorkeling','Itinerario flessibile','Asciugamani']),
       'excludes'=>json_encode(['Pranzo (su richiesta +30€/p)','Mance']),
       'schedule_days'=>'Lun,Mar,Mer,Gio,Ven,Sab,Dom'],

      ['slug'=>'white-island-snorkel', 'type'=>'boat_excursion', 'name'=>'White Island: l\'isola che scompare',
       'description'=>"Banco di sabbia bianca che emerge dal mare con la bassa marea: una delle esperienze più scenografiche di Sharm. Sosta alle barriere di Tiran per snorkeling spettacolare.\n\nPranzo a bordo, atmosfera caraibica nel cuore del Mar Rosso.",
       'cover_image'=>'/assets/services/exc_white_island.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_white_island.jpg',
         '/assets/services/exc_ras_mohammed.jpg',
         '/assets/services/exc_dolphins.jpg',
       ]),
       'price_per_person'=>55, 'duration_hours'=>7, 'group_size_min'=>2, 'group_size_max'=>30,
       'meeting_point'=>'Porto turistico · pickup hotel ore 8:00',
       'includes'=>json_encode(['Transfer A/R','Pranzo a bordo','Bevande','Maschera e pinne','Guida italiana']),
       'excludes'=>json_encode(['Tasse parco','Mance']),
       'schedule_days'=>'Mar,Gio,Sab'],

      // ============ ESCURSIONI NEL DESERTO ============
      ['slug'=>'quad-bedouin-sunset', 'type'=>'desert_excursion', 'name'=>'Quad nel deserto al tramonto + cena beduina',
       'description'=>"L'esperienza più amata: quad nel deserto del Sinai al tramonto, sosta in un villaggio beduino autentico con cena tipica sotto le stelle, narghilè e musica dal vivo.\n\nPossibilità di guidare singolo o doppio, accompagnamento guida esperta.",
       'cover_image'=>'/assets/services/exc_quad.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_quad.jpg',
         'https://images.unsplash.com/photo-1571679654681-ba01b9e1e117?w=1200&q=80',
         'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=1200&q=80',
       ]),
       'price_per_person'=>35, 'duration_hours'=>5, 'group_size_min'=>1, 'group_size_max'=>20,
       'meeting_point'=>'Pickup hotel ore 14:30',
       'includes'=>json_encode(['Pickup A/R hotel','Quad singolo (o doppio +5€)','Briefing sicurezza','Casco e occhiali','Tè beduino','Cena tipica beduina','Spettacolo musicale','Narghilè']),
       'excludes'=>json_encode(['Bevande extra','Mance']),
       'schedule_days'=>'Lun,Mar,Mer,Gio,Ven,Sab,Dom'],

      ['slug'=>'beduino-cammelli-cena', 'type'=>'desert_excursion', 'name'=>'Beduini, cammelli e cena nel deserto',
       'description'=>"Esperienza tradizionale al 100%: passeggiata sul dorso del cammello al tramonto nel deserto del Sinai, cena tipica beduina sotto le stelle con musica dal vivo, danza del ventre e narghilè.\n\nIdeale per famiglie e chi cerca un'avventura tranquilla.",
       'cover_image'=>'/assets/services/bedouin_camels.jpg',
       'gallery'=>json_encode([
         '/assets/services/bedouin_camels.jpg',
         '/assets/services/exc_quad.jpg',
       ]),
       'price_per_person'=>30, 'duration_hours'=>5, 'group_size_min'=>1, 'group_size_max'=>30,
       'meeting_point'=>'Pickup hotel ore 16:00',
       'includes'=>json_encode(['Pickup A/R hotel','Cammello con guida','Tè beduino','Cena tipica','Spettacolo','Narghilè','Osservazione stelle']),
       'excludes'=>json_encode(['Bevande extra','Mance']),
       'schedule_days'=>'Lun,Mer,Gio,Sab,Dom'],

      // ============ DIVING ============
      ['slug'=>'thistlegorm-diving', 'type'=>'diving', 'name'=>'Diving al relitto SS Thistlegorm',
       'description'=>"Day trip al leggendario relitto della SS Thistlegorm, mercantile britannico affondato nel 1941, uno dei wreck dive più famosi al mondo. 2 immersioni guidate, pranzo a bordo.\n\nSolo per diver certificati Open Water (con almeno 30 immersioni consigliate).",
       'cover_image'=>'/assets/services/thistlegorm_diving.jpg',
       'gallery'=>json_encode([
         '/assets/services/thistlegorm_diving.jpg',
         '/assets/services/exc_ras_mohammed.jpg',
       ]),
       'price_per_person'=>110, 'duration_hours'=>10, 'group_size_min'=>1, 'group_size_max'=>16,
       'meeting_point'=>'Porto turistico Sharm El Sheikh · partenza 5:30',
       'includes'=>json_encode(['Transfer A/R hotel','2 immersioni guidate','Bombole 12L e pesi','Pranzo a bordo','Bevande','Guida diving certificata','Assicurazione DAN']),
       'excludes'=>json_encode(['Attrezzatura completa (noleggio 25€)','Tasse parco','Computer subacqueo','Mance']),
       'schedule_days'=>'Mar,Gio,Sab'],

      // ============ TOUR CULTURALI ============
      ['slug'=>'cairo-pyramids-day-trip', 'type'=>'tour', 'name'=>'Il Cairo: piramidi, Sfinge e Museo Egizio in un giorno',
       'description'=>"Volo a/r in giornata al Cairo per vivere le 3 meraviglie egiziane in un solo viaggio: Piramidi di Giza, Sfinge, Museo Egizio, e tempo libero al mercato di Khan El Khalili.\n\nGuida italiana dedicata, voli, pranzo, ingressi e trasferimenti tutto incluso.",
       'cover_image'=>'/assets/services/exc_pyramids.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_pyramids.jpg',
         'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=1200&q=80',
         'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=1200&q=80',
       ]),
       'price_per_person'=>295, 'duration_hours'=>15, 'group_size_min'=>2, 'group_size_max'=>15,
       'meeting_point'=>'Aeroporto Sharm · pickup hotel ore 3:30',
       'includes'=>json_encode(['Voli A/R Sharm-Cairo','Trasferimenti privati','Pranzo in ristorante','Guida italiana esperta','Ingresso Piramidi e Sfinge','Ingresso Museo Egizio','Tempo libero a Khan El Khalili']),
       'excludes'=>json_encode(['Mance','Bevande extra','Spese personali']),
       'schedule_days'=>'Lun,Mer,Sab'],

      ['slug'=>'sinai-sunrise-monastery', 'type'=>'tour', 'name'=>'Monte Sinai all\'alba e Monastero di Santa Caterina',
       'description'=>"L'esperienza spirituale per eccellenza: salita notturna al Monte Sinai (2.285m) per assistere all'alba dalla cima dove Mosè ricevette le Tavole. Visita al Monastero di Santa Caterina, patrimonio UNESCO.\n\nPickup serale dall'hotel, rientro mattino successivo.",
       'cover_image'=>'/assets/services/exc_sinai.jpg',
       'gallery'=>json_encode([
         '/assets/services/exc_sinai.jpg',
         'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=1200&q=80',
         'https://images.unsplash.com/photo-1517816743773-6e0fd518b4a6?w=1200&q=80',
       ]),
       'price_per_person'=>85, 'duration_hours'=>18, 'group_size_min'=>2, 'group_size_max'=>20,
       'meeting_point'=>'Pickup hotel ore 22:00',
       'includes'=>json_encode(['Pickup A/R hotel','Guida italiana','Beduino accompagnatore in cammino','Ingresso al monastero','Tè caldo durante la salita','Colazione al ritorno']),
       'excludes'=>json_encode(['Bastone da trekking (5 EGP, opzionale)','Cammello (35 EGP, opzionale)','Mance','Acqua extra']),
       'schedule_days'=>'Mar,Gio,Sab'],

      // ============ TRANSFER ============
      ['slug'=>'transfer-aeroporto-naama-bay', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Naama Bay',
       'description'=>"Transfer privato dall'aeroporto di Sharm El Sheikh agli hotel/appartamenti di Naama Bay. Auto privata fino a 4 persone, conducente parlante italiano/inglese.",
       'cover_image'=>'/assets/services/transfer_sedan.jpg',
       'price_per_group'=>25, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Naama Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','Bagagli inclusi','24/7','Acqua omaggio'])],

      ['slug'=>'transfer-aeroporto-hadaba', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Hadaba',
       'description'=>"Transfer privato aeroporto-Hadaba/Ras Um Sid. Auto fino a 4 persone con conducente professionista.",
       'cover_image'=>'/assets/services/transfer_sedan.jpg',
       'price_per_group'=>30, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Hadaba / Ras Um Sid',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','24/7'])],

      ['slug'=>'transfer-aeroporto-nabq', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Nabq Bay',
       'description'=>"Transfer privato aeroporto-Nabq Bay. Tragitto circa 30 minuti, conducente attende anche con voli in ritardo.",
       'cover_image'=>'/assets/services/transfer_sedan.jpg',
       'price_per_group'=>35, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Nabq Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','24/7','Attesa volo inclusa'])],

      ['slug'=>'transfer-aeroporto-sharks-bay', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Sharks Bay',
       'description'=>"Transfer privato aeroporto-Sharks Bay (zona diving). Auto privata con conducente.",
       'cover_image'=>'/assets/services/transfer_sedan.jpg',
       'price_per_group'=>30, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Sharks Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata'])],

      ['slug'=>'transfer-minibus-7posti', 'type'=>'transfer', 'name'=>'Transfer minibus 7 posti',
       'description'=>"Minibus privato 7 posti per gruppi familiari. Da/per qualsiasi villaggio o appartamento di Sharm. Bagagli inclusi.",
       'cover_image'=>'/assets/services/transfer_minibus.jpg',
       'price_per_group'=>50, 'vehicle_capacity'=>7,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Qualsiasi zona di Sharm',
       'features'=>json_encode(['Minibus 7 posti','Bagagli ampi','Aria condizionata','Conducente professionista','Seggiolino bimbi su richiesta'])],
    ];

    $svcIds = [];
    foreach ($services as $i => $s) {
        $s['position'] = $i;
        $id = newId(); $svcIds[$s['slug']] = $id;
        $cols = array_keys($s);
        $colsStr = implode(',', $cols);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        q("INSERT INTO services (id, $colsStr) VALUES (?, $placeholders)", array_merge([$id], array_values($s)));
    }
    echo "✓ " . count($services) . " servizi extra creati (auto, golf cart, scooter, monopattino, escursioni, transfer)\n";

    // demo service bookings
    $sb1 = newId();
    q('INSERT INTO service_bookings (id, code, service_id, customer_id, start_date, end_date, participants, base_price, total, paid, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$sb1, 'SV-2026-0001', $svcIds['suzuki-jimny-4x4'], $c1, $bk(7), $bk(14), 4, 300, 315, 100, 'confirmed']);
    q('INSERT INTO service_bookings (id, code, service_id, customer_id, start_date, participants, base_price, total, paid, status, pickup_time, pickup_location, dropoff_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [newId(), 'SV-2026-0002', $svcIds['transfer-aeroporto-naama-bay'], $c2, $bk(-12), 2, 25, 25, 25, 'completed', '14:30', 'Aeroporto SSH', 'Naama Bay Sea View']);
    q('INSERT INTO service_bookings (id, code, service_id, customer_id, start_date, participants, base_price, total, paid, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [newId(), 'SV-2026-0003', $svcIds['snorkeling-ras-mohammed'], $c3, $bk(22), 2, 90, 90, 0, 'pending']);

    echo "✓ Prenotazioni servizi demo create\n";
}

echo "\n✅ Setup completato!\n";
echo "   Login admin: " . cfg('admin_default.email') . " / " . cfg('admin_default.password') . "\n";
echo "   Sito pubblico: /\n   Area admin: /admin/\n";
if ($reset) echo "\n⚠ Ricordati di rimuovere il parametro ?reset=YES dall'URL.\n";
