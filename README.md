# Casa Vacanza — Gestionale appartamenti turistici (PHP/MySQL)

Web app full-stack per gestire **multi-appartamento + sito pubblico clienti**.
Stack: **PHP 8 · MySQL · PDO · Tailwind (CDN) · Alpine.js · Chart.js · Lucide icons**.
Pronto per **deploy Git automatico su Hostinger** (no build, no Node).

## Setup locale (opzionale)

```bash
# 1) crea config.php (gitignored)
cp config.sample.php config.php
# 2) edita le credenziali MySQL
# 3) avvia
php -S localhost:8100
# 4) apri http://localhost:8100/setup.php (crea schema + dati demo)
```

## Deploy Hostinger

1. Crea database MySQL su Hostinger (es. `u749757264_apppatrizia`).
2. Modifica `config.php` con le credenziali reali (NON committare).
3. Hostinger → Avanzate → GIT:
   - URL repo HTTPS
   - Ramo: `main`
   - Direttorio: vuoto (deploy in `public_html`)
4. Clicca **Distribuisci**.
5. Carica `config.php` via File Manager dentro `public_html/`.
6. Apri `https://tuosito.com/setup.php` → crea schema + dati demo.
7. Login admin: `admin@casavacanza.it` / `admin123` (cambialo subito da Impostazioni).

## Struttura

```
/                      sito pubblico
/admin/                area admin (sessione PHP)
/api/quote.php         endpoint AJAX prezzo live
/api/booking.php       endpoint richiesta prenotazione
/setup.php             schema MySQL + dati demo (idempotente)
/lib/                  funzioni core (db, auth, pricing, utils, messages)
/partials/             head, header pubblico/footer, shell admin
/uploads/              foto e documenti (gitignored)
```

## Sezioni

**Pubblico** (`/`)
- Homepage con search bar
- Lista appartamenti con filtri città/date/ospiti/prezzo
- Dettaglio appartamento con gallery, calendario disponibilità live, recensioni, modulo prenotazione con quote in tempo reale

**Admin** (`/admin/`)
- Dashboard con KPI + grafici Chart.js (linea + doughnut)
- Appartamenti CRUD + galleria foto (upload locale + URL)
- Calendario mensile con stati colorati + blocco date
- Prenotazioni: lista, creazione, dettaglio (pagamenti, documenti, contratto stampabile, QR check-in, messaggi WhatsApp/Email)
- Prezzi avanzati: regole stagionali per intervallo + copia tra appartamenti
- Spese & bilancio per anno/appartamento, export CSV
- Template messaggi con variabili dinamiche e preview live
- Recensioni con moderazione
- Coupon (% e fisso) con limite usi
- Impostazioni + cambio password + backup JSON
- Notifiche + activity log

## Sicurezza
- Password: `password_hash`
- Sessione PHP con cookie httponly/secure
- CSRF token su tutti i form admin
- Output sempre via `e()` (htmlspecialchars)
- Query sempre via prepared statements PDO
- File `config.php` gitignored
