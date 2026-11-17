# JetLog

Aircraft maintenance tracking for a learning project. All aircraft, maintenance records, work orders, and users are simulated demo data. Not affiliated with Cathay Pacific.

## Requirements

- XAMPP with PHP 8.0 or newer and MariaDB or MySQL
- A browser

## Setup

1. Put this folder at `C:\xampp\htdocs\JetLog`.
2. Start Apache and MySQL in the XAMPP control panel.
3. Import `sql/schema.sql`, then `sql/seed.sql` in phpMyAdmin. The database name is `jetlog`.

   From a terminal:

   ```
   C:\xampp\mysql\bin\mysql.exe -u root < sql/schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root < sql/seed.sql
   ```

4. Check the database settings in `config.local.php`.
5. Open `http://localhost/JetLog/login.php`.

Demo login: `tech` / `jetlog123`

The app assumes it is served from `/JetLog`. If you rename the folder, change `BASE_URL` in `config.php`.

## Gemini summaries

Summaries are optional and only run when you click a button.

1. Get an API key from Google AI Studio.
2. Add it to `config.local.php`:

   ```php
   define('GEMINI_API_KEY', 'your-key-here');
   ```

3. To use another model, set `GEMINI_MODEL` in `config.local.php`. The default is `gemini-3.6-flash`.

The call uses the Interactions API at `POST /v1beta/interactions`, sent from the server with cURL. The key goes in the `x-goog-api-key` header, and `store` is `false` so Google does not keep the request or response.

The prompt asks Gemini to summarize the records shown and forbids diagnosis, maintenance recommendations, and airworthiness or safety judgments. Summaries are stored in `ai_summaries` with the model name, user, and time.

Record text comes from user input, so treat it as untrusted. The prompt limits the model to summarization, but review every summary before relying on it.

## QR codes

The QR page encodes the aircraft URL. Scanning it opens the aircraft page, and the person scanning still needs to log in. On a phone, use the computer's LAN address instead of `localhost` and make sure Apache is reachable on the local network.

## Reset demo data

Import `sql/schema.sql` and `sql/seed.sql` again. This drops and recreates all tables.

## Design and interface

The interface is a white working surface with a deep green rail and one soft green shift-briefing card per page. Green is the accent: the active section, the primary action, serviceable states and the figures that need attention. Everything else uses a neutral grey ramp, roughly 70% neutral surface to 30% green.

- On desktop the rail is a compact taskbar with the logo and section icons; hovering it expands the full navigation. On small screens the logo itself opens the drawer.
- Every navigation row spans the full width of the sidebar, the active row is a light green tint, and hovering a row shows the same light green.
- On narrow screens, tables stack into labeled cards so no page scrolls sideways.
- IBM Plex Sans is the interface type; IBM Plex Mono carries registrations, work order numbers, dates and figures. Fonts are stored locally in `assets/fonts/` under the SIL Open Font License 1.1; see `assets/fonts/OFL.txt`.
- Statuses use a dot and a quiet chip. Grounded, overdue and high severity are the only solid ink chips.
- Hover, focus and state changes are color only. `prefers-reduced-motion` removes transitions.
- Photos and the mark in `assets/img/` are demo assets and are not covered by the font licence. Treat them as placeholders before any public use.

## Folder map

```
config.php          app settings, base URL, model name
config.local.php    local database credentials and API key, not committed
index.php           dashboard
login.php           login form
logout.php          session logout
includes/           shared setup, header, footer
lib/                database, auth, validation, formatting, Gemini call
aircraft/           registry, details, edit, QR page
discrepancies/      report and edit discrepancies
work-orders/        list, create, details, status update
schedule/           scheduled tasks and mark done
logbook/            log entries and filters
reports/            aggregate reports
summaries/          Gemini request handler
sql/                schema and demo data
assets/             stylesheet, fonts, small scripts, QR library
```

## What to learn from each feature

| Feature | Concepts |
|---|---|
| Login and logout | Sessions, password hashing, CSRF tokens, redirects |
| Aircraft registry | CRUD, prepared statements, validation, escaping output |
| QR page | URL building, access control, print layout, browser-side library |
| Discrepancies | Foreign keys, joins, record ownership, status fields |
| Work orders | Relationships, transactions, generated numbers, state changes |
| Schedule | Date math, derived display state, filters |
| Logbook | Insert-only records, pagination, audit thinking |
| Reports | GROUP BY, aggregates, date ranges |
| Summaries | Server-side API calls, secrets, timeouts, error handling, storing results |

## Security notes

This is a local demo, not a production deployment.

- SQL uses prepared statements.
- POST forms use CSRF tokens.
- Output is escaped with `htmlspecialchars`.
- Sessions use HttpOnly and SameSite cookies.
- Gemini calls run on the server. The API key is never sent to the browser.

Before any real deployment, use HTTPS, move `config.local.php` outside the web root, set a strong database password, and replace the demo accounts.
