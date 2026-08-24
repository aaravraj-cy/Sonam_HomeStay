# Sonam Homestay - Homestay Booking


## Setup (XAMPP)

1. Copy project to `C:\xampp\htdocs\HomeStay`
2. Start Apache + MySQL
3. Open: http://localhost/HomeStay/database/install.php
4. Open website: http://localhost/HomeStay/

Local defaults still work without a `.env` file:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sonamDB
DB_USER=root
DB_PASS=
BASE_URL=http://localhost/Sonam_HomeStay/
```

## Production configuration

Copy `.env.example` to `.env` on the server, or set the same variables in the hosting platform environment. Do not commit `.env`.

```env
DB_HOST=
DB_PORT=3306
DB_NAME=sonamDB
DB_USER=
DB_PASS=
DB_SSL_CA=
DB_SSL_CA_CONTENT=
DB_SSL_CA_BASE64=
BASE_URL=
```

For Aiven MySQL, keep `DB_NAME=sonamDB` to match the existing application/schema. Aiven's `defaultdb` is only the default database supplied by the service; create a separate `sonamDB` database in Aiven before importing this app schema.

`database/sonam.sql` contains `CREATE DATABASE IF NOT EXISTS sonamDB`, `USE sonamDB`, and `DROP TABLE IF EXISTS` statements. Import it only into a new/empty `sonamDB` database or after taking a backup.

Download the Aiven CA certificate from the Aiven Console for the MySQL service. For a normal server, save it outside the public web root where possible and set `DB_SSL_CA` to that file path. For Railway, prefer `DB_SSL_CA_BASE64` so the certificate can be stored as one line. `DB_SSL_CA_CONTENT` also works when multiline values are preserved correctly.

## Railway deployment

This repository includes a `Dockerfile` for Railway. Configure these Railway service variables:

```env
DB_HOST=
DB_PORT=16540
DB_NAME=sonamDB
DB_USER=
DB_PASS=
DB_SSL_CA_CONTENT=
DB_SSL_CA_BASE64=
BASE_URL=
```

Set `BASE_URL` after Railway gives you the public app URL, for example:

```env
BASE_URL=https://your-app.up.railway.app/
```

Uploads are stored in `assets/uploads`. For production, attach a Railway Volume or move uploads to object storage before relying on user-uploaded images long term.

Safe connection check:

```bash
php scripts/test-db-connection.php
```

## Important rules

- **users or homestays** — database_starts_empty
- **Only ONE owner** can register in the whole system
- Many **guest users** can register
- Forms use normal **POST/GET**
- Simple **vanilla JavaScript** only (dark mode, swiper, etc.)

## How to use

1. Register the **Owner** first (Become Owner / Owner register)
2. Owner adds homestay + rooms
3. Register as **Guest User**
4. Search → Book → Dummy payment → Owner accepts

## Demo payment

Card number: `4111111111111111`

## Tech

- PHP (procedural style)
- MySQL + PDO prepared statements
- HTML, CSS, Bootstrap 5
- Simple JavaScript (no jQuery)
- Font Awesome, AOS, Swiper (CDN)

## Folders

- `authentication/` login, register, owner-register, logout
- `user/` guest dashboard
- `owner/` owner dashboard (single owner)
- `pages/` search, details, book, payment, invoice
- `config/` database + session
- `includes/` header, footer, functions
