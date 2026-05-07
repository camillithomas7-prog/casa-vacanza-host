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
SQL;

foreach (preg_split('/;\s*\n/', $schema) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt) $pdo->exec($stmt);
}
echo "✓ Schema creato/verificato\n";

if ($reset) {
    echo "⚠ RESET RICHIESTO — pulizia dati...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['payments','documents','bookings','customers','date_blocks','price_rules','photos','reviews','expenses','notifications','activity_logs','coupons','apartments','message_templates','settings'] as $t) {
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
}

echo "\n✅ Setup completato!\n";
echo "   Login admin: " . cfg('admin_default.email') . " / " . cfg('admin_default.password') . "\n";
echo "   Sito pubblico: /\n   Area admin: /admin/\n";
if ($reset) echo "\n⚠ Ricordati di rimuovere il parametro ?reset=YES dall'URL.\n";
