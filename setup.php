<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/messages.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = db();

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
  country VARCHAR(120) DEFAULT 'Italia',
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
echo "✓ Schema creato\n";

ensureAdminUser();
echo "✓ Utente admin: " . cfg('admin_default.email') . " / " . cfg('admin_default.password') . "\n";

// Templates default
foreach (defaultTemplates() as $t) {
    $existing = row('SELECT id FROM message_templates WHERE template_key = ?', [$t['key']]);
    if ($existing) continue;
    q('INSERT INTO message_templates (id, template_key, name, channel, subject, body, active) VALUES (?, ?, ?, ?, ?, ?, 1)',
        [newId(), $t['key'], $t['name'], $t['channel'], $t['subject'], $t['body']]);
}
echo "✓ Template messaggi caricati\n";

// Seed demo se vuoto
$count = (int)val('SELECT COUNT(*) FROM apartments');
if ($count > 0) {
    echo "✓ Dati demo già presenti ($count appartamenti)\n";
} else {
    $demos = [
        ['slug' => 'villa-sole-amalfi', 'name' => 'Villa Sole · Amalfi Coast',
         'description' => "Splendida villa con piscina e vista mozzafiato sul golfo di Amalfi. Tre camere, due bagni, cucina attrezzata e ampia terrazza per cene al tramonto.\n\nA pochi passi dal centro, perfetta per coppie e famiglie.",
         'address' => 'Via dei Limoni 12, Amalfi', 'city' => 'Amalfi',
         'guests' => 6, 'bedrooms' => 3, 'bathrooms' => 2, 'beds' => 4, 'size_sqm' => 110,
         'amenities' => json_encode(['WiFi','Aria condizionata','Piscina','Cucina','Parcheggio','TV','Lavatrice','Vista mare']),
         'rules' => "Vietato fumare\nNo feste\nAnimali ammessi su richiesta\nCheck-in dalle 15:00",
         'base_price' => 220, 'weekend_price' => 250, 'weekly_price' => 1400, 'monthly_price' => 4800,
         'cleaning_fee' => 80, 'city_tax' => 3, 'city_tax_max_nights' => 7, 'security_deposit' => 300,
         'cover_image' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&q=80',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80',
            'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=1200&q=80',
            'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1200&q=80',
         ]],
        ['slug' => 'loft-navigli-milano', 'name' => 'Loft Navigli · Milano Design',
         'description' => "Loft di design nel cuore dei Navigli. Ideale per coppie e business traveler, vicino a metro e principali attrazioni.",
         'address' => 'Ripa di Porta Ticinese 23, Milano', 'city' => 'Milano',
         'guests' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'beds' => 1, 'size_sqm' => 55,
         'amenities' => json_encode(['WiFi','Aria condizionata','Cucina','TV','Lavatrice','Smart TV','Asciugamani']),
         'base_price' => 110, 'weekend_price' => 130, 'weekly_price' => 700, 'monthly_price' => 2200,
         'cleaning_fee' => 40, 'city_tax' => 5, 'city_tax_max_nights' => 14, 'security_deposit' => 150,
         'cover_image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80',
            'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200&q=80',
            'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=1200&q=80',
            'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1200&q=80',
         ]],
        ['slug' => 'casetta-trastevere-roma', 'name' => 'Casetta Trastevere · Roma',
         'description' => "Caratteristica casa nel quartiere più autentico di Roma. Travi a vista, mattoncini a vista, atmosfera unica.",
         'address' => 'Vicolo del Cinque 8, Roma', 'city' => 'Roma',
         'guests' => 4, 'bedrooms' => 2, 'bathrooms' => 1, 'beds' => 3, 'size_sqm' => 70,
         'amenities' => json_encode(['WiFi','Cucina','TV','Lavatrice','Riscaldamento']),
         'base_price' => 140, 'weekend_price' => 160, 'weekly_price' => 850,
         'cleaning_fee' => 50, 'city_tax' => 4, 'city_tax_max_nights' => 10,
         'cover_image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&q=80',
            'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=1200&q=80',
            'https://images.unsplash.com/photo-1567016376408-0226e4d0c1ea?w=1200&q=80',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80',
         ]],
        ['slug' => 'appartamento-portovenere', 'name' => 'Appartamento Vista Mare · Portovenere',
         'description' => "Appartamento panoramico a 50 metri dal mare. Terrazza privata, perfetto per famiglie.",
         'address' => 'Via Capellini 4, Portovenere', 'city' => 'Portovenere',
         'guests' => 4, 'bedrooms' => 2, 'bathrooms' => 1, 'beds' => 2, 'size_sqm' => 65,
         'amenities' => json_encode(['WiFi','Aria condizionata','Cucina','TV','Vista mare','Balcone']),
         'base_price' => 160, 'weekend_price' => 180, 'weekly_price' => 980,
         'cleaning_fee' => 50, 'city_tax' => 2, 'city_tax_max_nights' => 5,
         'cover_image' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200&q=80',
         'photos' => [
            'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200&q=80',
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&q=80',
            'https://images.unsplash.com/photo-1571055107559-3e67626fa8be?w=1200&q=80',
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
    echo "✓ " . count($demos) . " appartamenti demo creati\n";

    $c1 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c1, 'Luca Bianchi', 'luca@example.com', '+393331112233', 'Italia']);
    $c2 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c2, 'Sarah Müller', 'sarah@example.com', '+491701234567', 'Germania']);
    $c3 = newId(); q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)', [$c3, 'Marco Rossi', 'marco@example.com', '+393344455667', 'Italia']);

    $today = strtotime('today');
    $bk = function($n) use ($today) { return date('Y-m-d', $today + $n * 86400); };

    $b1 = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, city_tax, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$b1, 'CV-2026-0001', $aptIds['villa-sole-amalfi'], $c1, $bk(7), $bk(12), 5, 4, 1100, 80, 60, 1240, 400, 'confirmed', 'direct']);
    q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)', [newId(), $b1, 400, 'deposit', 'bank']);

    $b2 = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, city_tax, discount, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$b2, 'CV-2026-0002', $aptIds['loft-navigli-milano'], $c2, $bk(-10), $bk(-3), 7, 2, 770, 40, 70, 70, 810, 810, 'completed', 'airbnb']);
    q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)', [newId(), $b2, 810, 'balance', 'stripe']);

    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, city_tax, total, paid, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [newId(), 'CV-2026-0003', $aptIds['casetta-trastevere-roma'], $c3, $bk(20), $bk(23), 3, 2, 420, 50, 24, 494, 0, 'pending', 'direct']);

    foreach ([
        [$aptIds['villa-sole-amalfi'], 'pulizie', 80, $bk(-3), 'Pulizia post check-out'],
        [$aptIds['villa-sole-amalfi'], 'manutenzione', 250, $bk(-30), 'Riparazione condizionatore'],
        [$aptIds['loft-navigli-milano'], 'bollette', 95, $bk(-15), 'Energia + acqua'],
        [$aptIds['loft-navigli-milano'], 'commissioni', 81, $bk(-3), 'Commissioni Airbnb 10%'],
        [$aptIds['casetta-trastevere-roma'], 'internet', 35, $bk(-20), null],
    ] as $e) {
        q('INSERT INTO expenses (id, apartment_id, category, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)',
            array_merge([newId()], $e));
    }

    foreach ([
        [$aptIds['villa-sole-amalfi'], 'Marco D.', 5, 'Esperienza fantastica', 'Vista incredibile e proprietari super disponibili. Torneremo!'],
        [$aptIds['villa-sole-amalfi'], 'Laura B.', 5, 'Magico', 'Tramonti indimenticabili. La piscina è una favola.'],
        [$aptIds['loft-navigli-milano'], 'Andrea P.', 4, null, 'Posizione perfetta, design curato.'],
        [$aptIds['casetta-trastevere-roma'], 'Sofia R.', 5, null, 'Quartiere top, casa accogliente.'],
    ] as $r) {
        q('INSERT INTO reviews (id, apartment_id, author_name, rating, title, body, approved) VALUES (?, ?, ?, ?, ?, ?, 1)',
            array_merge([newId()], $r));
    }

    q('INSERT INTO coupons (id, code, type, value, max_uses) VALUES (?, ?, ?, ?, ?)', [newId(), 'SUMMER10', 'percent', 10, 100]);
    q('INSERT INTO coupons (id, code, type, value, max_uses) VALUES (?, ?, ?, ?, ?)', [newId(), 'WELCOME50', 'fixed', 50, 50]);

    q('INSERT INTO notifications (id, type, title, body, link) VALUES (?, ?, ?, ?, ?)',
        [newId(), 'new_booking', 'Nuova prenotazione', 'Marco Rossi ha richiesto Casetta Trastevere', '/admin/prenotazioni.php']);

    echo "✓ Dati demo caricati\n";
}

echo "\n✅ Setup completato!\n";
echo "   Login admin: " . cfg('admin_default.email') . " / " . cfg('admin_default.password') . "\n";
echo "   Sito: /\n   Admin: /admin/\n";
