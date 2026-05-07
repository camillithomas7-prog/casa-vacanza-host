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
SQL;

foreach (preg_split('/;\s*\n/', $schema) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt) $pdo->exec($stmt);
}
echo "✓ Schema creato/verificato\n";

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
      ['slug'=>'suzuki-jimny-4x4', 'type'=>'car', 'name'=>'Suzuki Jimny 4x4',
       'description'=>"SUV compatto 4x4 ideale per esplorare il deserto e le strade di Sharm. Aria condizionata, cambio manuale, 4 posti.\n\nPerfetto per gite a Dahab, Ras Mohammed e zone interne.",
       'cover_image'=>'https://images.unsplash.com/photo-1629897048514-3dd7414efc7d?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1629897048514-3dd7414efc7d?w=1200&q=80',
         'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=1200&q=80',
       ]),
       'daily_price'=>50, 'weekly_price'=>300, 'biweekly_price'=>550, 'triweekly_price'=>800, 'monthly_price'=>1100,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>20,
       'cleaning_fee'=>15, 'security_deposit'=>200,
       'features'=>json_encode(['Aria condizionata','Cambio manuale','4x4','4 posti','Bluetooth','Radio']),
       'insurance_included'=>1, 'fuel_included'=>0, 'min_age'=>21, 'license_required'=>1],

      ['slug'=>'renault-clio', 'type'=>'car', 'name'=>'Renault Clio · Auto economica',
       'description'=>"Compatta affidabile per spostarsi in città. Aria condizionata, cambio manuale, perfetta per coppie e piccoli gruppi.",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
         'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=1200&q=80',
       ]),
       'daily_price'=>35, 'weekly_price'=>210, 'biweekly_price'=>380, 'monthly_price'=>800,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>15,
       'cleaning_fee'=>10, 'security_deposit'=>150,
       'features'=>json_encode(['Aria condizionata','5 porte','Bluetooth','GPS']),
       'insurance_included'=>1, 'min_age'=>21, 'license_required'=>1],

      ['slug'=>'hyundai-i10-automatic', 'type'=>'car', 'name'=>'Hyundai i10 Automatica',
       'description'=>"Cambio automatico, ideale per chi non guida bene il manuale. Climatizzata, agile in città.",
       'cover_image'=>'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200&q=80',
       ]),
       'daily_price'=>40, 'weekly_price'=>240, 'biweekly_price'=>440, 'monthly_price'=>900,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10, 'long_stay_discount_30'=>15,
       'cleaning_fee'=>10, 'security_deposit'=>150,
       'features'=>json_encode(['Aria condizionata','Cambio automatico','5 porte','Bluetooth']),
       'insurance_included'=>1, 'min_age'=>21, 'license_required'=>1],

      // ============ GOLF CART ============
      ['slug'=>'golf-cart-naama-bay-4posti', 'type'=>'golf_cart', 'name'=>'Golf cart 4 posti · Naama Bay',
       'description'=>"Golf cart elettrico 4 posti per spostarti nel resort e sulla Promenade di Naama Bay senza problemi di parcheggio.",
       'cover_image'=>'https://images.unsplash.com/photo-1592890288564-76628a30a657?w=1200&q=80',
       'gallery'=>json_encode(['https://images.unsplash.com/photo-1592890288564-76628a30a657?w=1200&q=80']),
       'daily_price'=>25, 'weekly_price'=>150, 'monthly_price'=>500,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>50,
       'resort_name'=>'Naama Bay Resort', 'resort_address'=>'Naama Bay Promenade, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','4 posti','Tetto','Vano bagagli']),
       'min_age'=>18, 'license_required'=>0],

      ['slug'=>'golf-cart-hadaba-6posti', 'type'=>'golf_cart', 'name'=>'Golf cart 6 posti · Hadaba',
       'description'=>"Modello familiare 6 posti per gruppi. Disponibile presso il resort di Hadaba con accesso libero alla zona privata.",
       'cover_image'=>'https://images.unsplash.com/photo-1606140797900-b30c2569a5b3?w=1200&q=80',
       'gallery'=>json_encode(['https://images.unsplash.com/photo-1606140797900-b30c2569a5b3?w=1200&q=80']),
       'daily_price'=>35, 'weekly_price'=>210, 'monthly_price'=>700,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>80,
       'resort_name'=>'Hadaba Resort', 'resort_address'=>'Hadaba, Ras Um Sid, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','6 posti','Tetto']),
       'min_age'=>18, 'license_required'=>0],

      // ============ SCOOTER ============
      ['slug'=>'scooter-50cc', 'type'=>'scooter', 'name'=>'Scooter 50cc',
       'description'=>"Scooter agile per spostarti tra Naama Bay e Old Market. Casco incluso. Disponibile presso il punto noleggio di Naama Bay.",
       'cover_image'=>'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?w=1200&q=80',
       'gallery'=>json_encode(['https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?w=1200&q=80']),
       'daily_price'=>20, 'weekly_price'=>120, 'monthly_price'=>400,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>100,
       'resort_name'=>'Naama Bay - Punto noleggio',
       'resort_address'=>'Naama Bay Promenade angolo Sultan Hotel, Sharm El Sheikh',
       'features'=>json_encode(['50cc','2 posti','Casco incluso','Bauletto']),
       'helmet_included'=>1, 'min_age'=>18, 'license_required'=>1],

      // ============ MONOPATTINO ELETTRICO ============
      ['slug'=>'monopattino-elettrico', 'type'=>'escooter', 'name'=>'Monopattino elettrico',
       'description'=>"Monopattino elettrico per spostamenti rapidi sulla Promenade. Autonomia 30km, velocità max 25 km/h. Disponibile presso il resort di Nabq Bay.",
       'cover_image'=>'https://images.unsplash.com/photo-1609954551106-0d1c0bdca0e2?w=1200&q=80',
       'gallery'=>json_encode(['https://images.unsplash.com/photo-1609954551106-0d1c0bdca0e2?w=1200&q=80']),
       'daily_price'=>15, 'weekly_price'=>80, 'monthly_price'=>250,
       'long_stay_discount_7'=>5, 'long_stay_discount_14'=>10,
       'security_deposit'=>50,
       'resort_name'=>'Nabq Bay Resort', 'resort_address'=>'Nabq Bay Resort Area, Sharm El Sheikh',
       'features'=>json_encode(['Elettrico','Autonomia 30km','Pieghevole','25 km/h max','Caschetto incluso']),
       'helmet_included'=>1, 'min_age'=>16],

      // ============ ESCURSIONI BARCA ============
      ['slug'=>'snorkeling-ras-mohammed', 'type'=>'boat_excursion', 'name'=>'Snorkeling Ras Mohammed',
       'description'=>"Giornata in barca al Parco Nazionale di Ras Mohammed: 2 stop di snorkeling tra i reef più belli del Mar Rosso, pranzo a bordo incluso, partenza dal porto di Sharm.",
       'cover_image'=>'https://images.unsplash.com/photo-1559599189-fe84dea4eb79?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1559599189-fe84dea4eb79?w=1200&q=80',
         'https://images.unsplash.com/photo-1582610116397-edb318620f90?w=1200&q=80',
         'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&q=80',
       ]),
       'price_per_person'=>45, 'duration_hours'=>8, 'group_size_min'=>1, 'group_size_max'=>40,
       'meeting_point'=>'Porto turistico Sharm El Sheikh',
       'includes'=>json_encode(['Transfer da/per hotel','Pranzo a bordo','Bevande','Maschera e pinne','Guida italiana']),
       'excludes'=>json_encode(['Tasse parco (5 USD)','Mance']),
       'schedule_days'=>'Lun,Mar,Mer,Gio,Ven,Sab,Dom'],

      ['slug'=>'isola-tiran-snorkeling', 'type'=>'boat_excursion', 'name'=>'Isola di Tiran in barca',
       'description'=>"Giornata sull'Isola di Tiran, 4 punti di snorkeling tra reef e relitti, acque turchesi cristalline. Pranzo a bordo incluso.",
       'cover_image'=>'https://images.unsplash.com/photo-1582610116397-edb318620f90?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1582610116397-edb318620f90?w=1200&q=80',
         'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&q=80',
       ]),
       'price_per_person'=>40, 'duration_hours'=>8, 'group_size_min'=>1, 'group_size_max'=>40,
       'meeting_point'=>'Porto turistico Sharm El Sheikh',
       'includes'=>json_encode(['Transfer','Pranzo a bordo','Bevande','Maschera e pinne']),
       'schedule_days'=>'Lun,Mer,Ven,Sab,Dom'],

      // ============ ESCURSIONI DESERTO ============
      ['slug'=>'safari-quad-deserto', 'type'=>'desert_excursion', 'name'=>'Safari Quad nel deserto',
       'description'=>"Avventura in quad nel deserto del Sinai al tramonto. Visita a un villaggio beduino tradizionale con cena tipica e narghilè sotto le stelle.",
       'cover_image'=>'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=80',
         'https://images.unsplash.com/photo-1571679654681-ba01b9e1e117?w=1200&q=80',
         'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=1200&q=80',
       ]),
       'price_per_person'=>35, 'duration_hours'=>5, 'group_size_min'=>1, 'group_size_max'=>20,
       'meeting_point'=>'Pickup hotel ore 14:30',
       'includes'=>json_encode(['Pickup','Quad singolo o doppio','Tè beduino','Cena tipica','Spettacolo']),
       'excludes'=>json_encode(['Mance','Bevande extra']),
       'schedule_days'=>'Lun,Mar,Mer,Gio,Ven,Sab,Dom'],

      ['slug'=>'beduino-cammelli-cena', 'type'=>'desert_excursion', 'name'=>'Beduini, cammelli e cena nel deserto',
       'description'=>"Esperienza tradizionale: dorso del cammello al tramonto, cena tipica beduina sotto le stelle, musica dal vivo e narghilè.",
       'cover_image'=>'https://images.unsplash.com/photo-1571679654681-ba01b9e1e117?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1571679654681-ba01b9e1e117?w=1200&q=80',
         'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=1200&q=80',
         'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=80',
       ]),
       'price_per_person'=>30, 'duration_hours'=>5, 'group_size_min'=>1, 'group_size_max'=>30,
       'meeting_point'=>'Pickup hotel ore 16:00',
       'includes'=>json_encode(['Pickup','Cammello','Cena tipica','Spettacolo','Narghilè']),
       'schedule_days'=>'Lun,Mer,Gio,Sab,Dom'],

      // ============ DIVING / DAY TRIP ============
      ['slug'=>'immersione-singola-thistlegorm', 'type'=>'diving', 'name'=>'Immersione Thistlegorm',
       'description'=>"Day trip al relitto della SS Thistlegorm, uno dei diving site più famosi al mondo. 2 immersioni guidate, pranzo a bordo. Solo divers certificati.",
       'cover_image'=>'https://images.unsplash.com/photo-1583212292454-1fe6229603b7?w=1200&q=80',
       'gallery'=>json_encode(['https://images.unsplash.com/photo-1583212292454-1fe6229603b7?w=1200&q=80']),
       'price_per_person'=>110, 'duration_hours'=>10, 'group_size_min'=>1, 'group_size_max'=>20,
       'meeting_point'=>'Porto turistico Sharm El Sheikh ore 5:30',
       'includes'=>json_encode(['2 immersioni','Pranzo','Bevande','Bombole','Pesi','Guida diving']),
       'excludes'=>json_encode(['Attrezzatura completa (noleggio 25€)','Tasse parco']),
       'schedule_days'=>'Mar,Gio,Sab'],

      // ============ TOUR CULTURALI ============
      ['slug'=>'cairo-piramidi-day-trip', 'type'=>'tour', 'name'=>'Cairo & Piramidi · Day trip',
       'description'=>"Volo a/r in giornata al Cairo: Piramidi di Giza, Sfinge, Museo Egizio, mercato Khan El Khalili. Guida italiana dedicata.",
       'cover_image'=>'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=1200&q=80',
         'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=1200&q=80',
         'https://images.unsplash.com/photo-1587974928442-77dc3e0dba72?w=1200&q=80',
       ]),
       'price_per_person'=>250, 'duration_hours'=>16, 'group_size_min'=>2, 'group_size_max'=>15,
       'meeting_point'=>'Aeroporto Sharm ore 4:00 (pickup hotel ore 3:00)',
       'includes'=>json_encode(['Voli a/r','Trasferimenti','Pranzo','Guida italiana','Ingressi monumenti']),
       'excludes'=>json_encode(['Bevande','Mance']),
       'schedule_days'=>'Lun,Mer,Sab'],

      ['slug'=>'monte-sinai-alba', 'type'=>'tour', 'name'=>'Monte Sinai · Alba sulla cima',
       'description'=>"Salita notturna al Monte Sinai per assistere all'alba, visita al Monastero di Santa Caterina. Esperienza unica.",
       'cover_image'=>'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=1200&q=80',
       'gallery'=>json_encode([
         'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=1200&q=80',
         'https://images.unsplash.com/photo-1517816743773-6e0fd518b4a6?w=1200&q=80',
       ]),
       'price_per_person'=>55, 'duration_hours'=>14, 'group_size_min'=>2, 'group_size_max'=>20,
       'meeting_point'=>'Pickup hotel ore 22:00',
       'includes'=>json_encode(['Pickup','Guida','Ingresso monastero','Tè caldo']),
       'excludes'=>json_encode(['Bastone (5 EGP)','Cammello opzionale','Mance']),
       'schedule_days'=>'Mar,Gio,Sab'],

      // ============ TRANSFER ============
      ['slug'=>'transfer-aeroporto-naama-bay', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Naama Bay',
       'description'=>"Transfer privato dall'aeroporto di Sharm El Sheikh agli hotel/appartamenti di Naama Bay. Auto privata fino a 4 persone.",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'price_per_group'=>25, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Naama Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','Bagagli inclusi','24/7'])],

      ['slug'=>'transfer-aeroporto-hadaba', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Hadaba',
       'description'=>"Transfer privato aeroporto-Hadaba/Ras Um Sid. Auto fino a 4 persone.",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'price_per_group'=>30, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Hadaba / Ras Um Sid',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','24/7'])],

      ['slug'=>'transfer-aeroporto-nabq', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Nabq Bay',
       'description'=>"Transfer privato aeroporto-Nabq Bay. Tragitto circa 30 minuti.",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'price_per_group'=>35, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Nabq Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata','24/7'])],

      ['slug'=>'transfer-aeroporto-sharks-bay', 'type'=>'transfer', 'name'=>'Transfer aeroporto → Sharks Bay',
       'description'=>"Transfer privato aeroporto-Sharks Bay (zona diving).",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'price_per_group'=>30, 'vehicle_capacity'=>4,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Sharks Bay',
       'features'=>json_encode(['Auto privata','Fino a 4 persone','Aria condizionata'])],

      ['slug'=>'transfer-minibus-7posti', 'type'=>'transfer', 'name'=>'Transfer minibus 7 posti',
       'description'=>"Minibus privato 7 posti per gruppi familiari. Da/per qualsiasi villaggio o appartamento di Sharm.",
       'cover_image'=>'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1200&q=80',
       'price_per_group'=>50, 'vehicle_capacity'=>7,
       'from_location'=>'Aeroporto SSH', 'to_location'=>'Qualsiasi zona di Sharm',
       'features'=>json_encode(['Minibus 7 posti','Bagagli','Aria condizionata','Conducente'])],
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
