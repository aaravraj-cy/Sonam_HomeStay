<div align="center">

<img src="assets/images/sonam-homestay-hero.png" alt="Sonam Homestay" width="180" />

# Sonam Homestay : Booking & Host Management System

### PHP-Based Homestay Reservation Platform
**Guest Booking, Owner Dashboard, Aiven MySQL, Railway Deployment**

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](#requirements)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](#aiven-mysql-setup)
[![PDO](https://img.shields.io/badge/PDO-prepared%20statements-blue?style=for-the-badge)](#security-controls)
[![Railway](https://img.shields.io/badge/Railway-ready-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)](#railway-deployment)
[![Aiven](https://img.shields.io/badge/Aiven-MySQL%20SSL-FF6B00?style=for-the-badge)](#aiven-mysql-setup)
[![Security](https://img.shields.io/badge/security-reviewed-brightgreen?style=for-the-badge&logo=shieldsdotio&logoColor=white)](#security-controls)

*This repository contains the deployable Sonam Homestay web application,*
*prepared for Railway hosting and Aiven MySQL with SSL/TLS database access.*

</div>

---

## Table of Contents

- [Sonam Homestay : Booking \& Host Management System](#sonam-homestay--booking--host-management-system)
    - [PHP-Based Homestay Reservation Platform](#php-based-homestay-reservation-platform)
  - [Table of Contents](#table-of-contents)
  - [Purpose \& Scope](#purpose--scope)
  - [Features](#features)
  - [Architecture](#architecture)
  - [Requirements](#requirements)
  - [Quick Start](#quick-start)
    - [Local Development](#local-development)
    - [Local Database Connection Test](#local-database-connection-test)
  - [Configuration](#configuration)
  - [Aiven MySQL Setup](#aiven-mysql-setup)
    - [Database initialization](#database-initialization)
    - [SSL CA certificate](#ssl-ca-certificate)
  - [Railway Deployment](#railway-deployment)
    - [Required Railway variables](#required-railway-variables)
  - [Upload Storage](#upload-storage)
  - [Security Controls](#security-controls)
    - [Operational security notes](#operational-security-notes)
  - [Quality Assurance \& Verification](#quality-assurance--verification)
  - [Project Structure](#project-structure)
  - [Troubleshooting](#troubleshooting)
    - [The app shows "Database is not ready"](#the-app-shows-database-is-not-ready)
    - [MySQL SSL fails on Railway](#mysql-ssl-fails-on-railway)
    - [Tables are missing](#tables-are-missing)
    - [Uploads disappear after redeploy](#uploads-disappear-after-redeploy)
  - [Governance \& Maintenance](#governance--maintenance)
  - [License](#license)

---

## Purpose & Scope

Sonam Homestay is a PHP and MySQL booking platform built for a homestay
business workflow. The application provides public browsing, guest
registration, booking requests, host-side management, room inventory, photo
gallery management, reviews, invoices, and notification features.

This repository has been prepared for the following deployment model:

- local development through XAMPP or equivalent PHP/MySQL tooling;
- production PHP hosting on Railway using the included Dockerfile;
- production MySQL hosting on Aiven MySQL 8.4;
- database connectivity through PDO with SSL/TLS certificate verification;
- environment-based configuration without committing secrets.

The application intentionally preserves its existing procedural PHP
architecture, route structure, table names, and business flow. Security work
in this repository focuses on hardening, validation, configuration safety, and
deployment readiness without redesigning the user interface or rewriting the
system.

---

## Features

| Capability | Description |
| --- | --- |
| **Public homestay website** | Visitors can view the homestay, rooms, gallery, guest reviews, and booking entry points. |
| **Guest accounts** | Guests can register, log in, manage profile details, view bookings, submit reviews, and receive notifications. |
| **Owner dashboard** | Hosts can manage homestay details, rooms, bookings, room photos, gallery images, reviews, earnings, and profile settings. |
| **Booking flow** | Guests select check-in/check-out dates, guest count, room, and contact details before proceeding through the demo payment flow. |
| **Demo payment flow** | The system validates card/UPI demo inputs and records booking/payment status for application workflow testing. |
| **Invoice support** | Authenticated users and relevant owners can view booking invoices. |
| **Photo uploads** | Profile, homestay, room, and gallery images are validated and stored under `assets/uploads`. |
| **Aiven MySQL support** | Production database credentials and SSL CA configuration are supplied through environment variables. |
| **Railway deployment** | The included `Dockerfile`, `.dockerignore`, and `railway.json` prepare the app for Railway deployment. |
| **Security hardening** | CSRF protection, prepared statements, secure cookies, validation helpers, guarded installer behavior, and private-file access controls are included. |

---

## Architecture

```text
                        ┌──────────────────────────┐
                        │        Web Browser       │
                        │  Guest / Owner Interface │
                        └─────────────┬────────────┘
                                      │ HTTPS
                                      ▼
                        ┌──────────────────────────┐
                        │     PHP Application      │
                        │  Railway / Apache / PHP  │
                        │                          │
                        │  ├─ authentication/      │
                        │  ├─ pages/               │
                        │  ├─ user/                │
                        │  ├─ owner/               │
                        │  ├─ includes/            │
                        │  └─ config/               │
                        └─────────────┬────────────┘
                                      │ PDO + SSL/TLS
                                      ▼
                        ┌──────────────────────────┐
                        │      Aiven MySQL 8.4     │
                        │          sonamDB         │
                        └──────────────────────────┘
```

Runtime-uploaded files are stored separately from MySQL:

```text
assets/uploads/profiles/
assets/uploads/homestays/
assets/uploads/rooms/
assets/uploads/gallery/
```

---

## Requirements

The following are required for local development or deployment:

- **PHP 8.4** or compatible PHP 8.x runtime
- **PDO MySQL extension**
- **MySQL 8.4** for production, or local MySQL for development
- **Aiven MySQL CA certificate** for SSL/TLS production database access
- **Railway account** for the current production hosting path
- **GitHub repository** connected to Railway

For local XAMPP development:

- Apache
- MySQL
- PHP with PDO MySQL enabled

---

## Quick Start

### Local Development

From the project root:

```bash
cp .env.example .env
```

For XAMPP-style local defaults:

```env
APP_ENV=local
APP_DEBUG=0
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sonamDB
DB_USER=root
DB_PASS=
DB_DEBUG=0
DB_AUTO_MIGRATE=0
BASE_URL=http://localhost/Sonam_HomeStay/
```

Then:

1. Place the project in the XAMPP web root.
2. Start Apache and MySQL.
3. Import `database/sonam.sql` into local MySQL.
4. Open:

```text
http://localhost/Sonam_HomeStay/
```

### Local Database Connection Test

```bash
php scripts/test-db-connection.php
```

This script is CLI-only and does not print the database password.

---

## Configuration

All deployment-sensitive settings should be provided through `.env` locally
or Railway service variables in production.

Real `.env` files, database passwords, and CA certificate files must never be
committed.

| Area | Variables | Notes |
| --- | --- | --- |
| Application | `APP_ENV`, `APP_DEBUG`, `BASE_URL` | Keep debug disabled in production. `BASE_URL` must match the deployed HTTPS URL. |
| Database | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_DEBUG`, `DB_AUTO_MIGRATE` | `DB_NAME` should remain `sonamDB`. Keep `DB_AUTO_MIGRATE=0` during normal production traffic. Use `DB_DEBUG=1` only temporarily during troubleshooting. |
| SSL/TLS | `DB_SSL_CA`, `DB_SSL_CA_CONTENT`, `DB_SSL_CA_BASE64` | Use `DB_SSL_CA` for local file paths. Prefer `DB_SSL_CA_BASE64` on Railway. |
| Installer safety | `INSTALL_ALLOW_REMOTE` | Keep `0` in production. Set to `1` only for deliberate first-time remote initialization. |

Example:

```env
APP_ENV=production
APP_DEBUG=0

DB_HOST=
DB_PORT=16540
DB_NAME=sonamDB
DB_USER=
DB_PASS=
DB_DEBUG=0
DB_AUTO_MIGRATE=0

DB_SSL_CA=
DB_SSL_CA_CONTENT=
DB_SSL_CA_BASE64=

BASE_URL=https://your-app.up.railway.app/
INSTALL_ALLOW_REMOTE=0
```

---

## Aiven MySQL Setup

The application expects the database name:

```text
sonamDB
```

Aiven may provide `defaultdb` by default, but this application should use
`sonamDB` to preserve schema compatibility.

### Database initialization

Create an empty database named `sonamDB`, then import:

```text
database/sonam.sql
```

Important: `database/sonam.sql` contains `DROP TABLE IF EXISTS` statements.
It should only be imported into a new or intentionally reset database.

### SSL CA certificate

Download the Aiven CA certificate and save it locally as:

```text
private/certs/ca.pem
```

For local testing:

```env
DB_SSL_CA=private/certs/ca.pem
```

For Railway, encode the CA certificate as a single-line value:

```bash
base64 -i private/certs/ca.pem | tr -d '\n'
```

Set the output as:

```env
DB_SSL_CA_BASE64=<one-line-base64-certificate>
```

When using `DB_SSL_CA_BASE64`, keep these empty:

```env
DB_SSL_CA=
DB_SSL_CA_CONTENT=
```

---

## Railway Deployment

The repository includes Railway deployment files:

```text
Dockerfile
railway.json
.dockerignore
```

Railway should use the Dockerfile builder.

### Required Railway variables

```env
APP_ENV=production
APP_DEBUG=0
DB_HOST=<aiven-host>
DB_PORT=16540
DB_NAME=sonamDB
DB_USER=<aiven-user>
DB_PASS=<aiven-password>
DB_DEBUG=0
DB_AUTO_MIGRATE=0
DB_SSL_CA=
DB_SSL_CA_CONTENT=
DB_SSL_CA_BASE64=<base64-ca-certificate>
BASE_URL=https://your-railway-domain.up.railway.app/
INSTALL_ALLOW_REMOTE=0
```

After Railway generates a public domain, set `BASE_URL` to that exact HTTPS
domain and redeploy.

---

## Upload Storage

The application stores uploaded images on disk, not in MySQL.

| Upload type | Directory |
| --- | --- |
| Profile images | `assets/uploads/profiles/` |
| Homestay images | `assets/uploads/homestays/` |
| Room images | `assets/uploads/rooms/` |
| Gallery images | `assets/uploads/gallery/` |

On Railway, attach a persistent volume at:

```text
/var/www/html/assets/uploads
```

The Docker startup command seeds the volume from bundled upload assets once.
For long-term production, object storage should be considered for stronger
backup and portability guarantees.

---

## Security Controls

The following controls are currently implemented:

| Control | Implementation |
| --- | --- |
| **Secret management** | `.env`, `.env.*`, certificates, and zip backups are ignored by Git. |
| **Database safety** | PDO prepared statements are used across user-input SQL paths. |
| **SSL/TLS database access** | Aiven CA certificate support is available through file, content, or base64 environment variables. |
| **CSRF protection** | POST forms use session CSRF tokens. |
| **Session protection** | Cookies are HTTP-only, SameSite=Lax, and secure when HTTPS is detected behind Railway. |
| **Password storage** | Passwords are hashed with `password_hash()`. |
| **Input validation** | Shared helpers validate strings, names, phone numbers, passwords, and money values. |
| **Authentication throttling** | Login and password-reset requests are session rate-limited. |
| **File upload hardening** | Uploads validate MIME type, real image metadata, file size, image dimensions, and randomized filenames. |
| **Safe deletion** | Uploaded-file deletion is constrained by basename and realpath checks. |
| **Private file protection** | `.htaccess` blocks direct web access to private folders, scripts, SQL dumps, dotfiles, and certificate files. |
| **Production diagnostics** | DB connection diagnostics are logged only when `DB_DEBUG=1`. |

### Operational security notes

- Keep `APP_DEBUG=0` and `DB_DEBUG=0` in production.
- Rotate database credentials if they appear in screenshots, logs, or chat.
- Do not commit `.env`, `ca.pem`, database exports, or zip backups.
- Do not re-import `database/sonam.sql` after real production data exists.
- Run external vulnerability assessment before public commercial use.

---

## Quality Assurance & Verification

The following checks were used during the hardening pass:

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
git diff --check
git ls-files | rg '\.env$|ca\.pem$|\.zip$|\.DS_Store' || true
```

Expected result:

- every PHP file reports `No syntax errors detected`;
- `git diff --check` returns no whitespace errors;
- no `.env`, `ca.pem`, zip backup, or `.DS_Store` file is tracked.

---

## Project Structure

```text
.
├── authentication/          # Login, registration, reset, logout
├── assets/
│   ├── css/                 # Application styles
│   ├── images/              # Static images and placeholders
│   ├── js/                  # Frontend scripts
│   └── uploads/             # Runtime-uploaded images
├── config/                  # Constants, database connection, sessions
├── database/                # Installer and schema
├── includes/                # Shared helpers and layout files
├── owner/                   # Owner dashboard and management pages
├── pages/                   # Public and booking pages
├── private/certs/           # Local-only CA certificate location
├── scripts/                 # CLI utilities
├── user/                    # Guest dashboard and account pages
├── Dockerfile               # Railway container build
├── railway.json             # Railway builder configuration
├── .dockerignore            # Docker build exclusions
├── .env.example             # Placeholder-only environment template
└── README.md
```

---

## Troubleshooting

### The app shows "Database is not ready"

Check Railway variables:

```env
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
DB_SSL_CA_BASE64
BASE_URL
```

Temporarily enable:

```env
DB_DEBUG=1
```

Redeploy, inspect Railway deploy logs, fix the issue, then set:

```env
DB_DEBUG=0
```

### MySQL SSL fails on Railway

Use `DB_SSL_CA_BASE64`. Clear `DB_SSL_CA` and `DB_SSL_CA_CONTENT` when using
the base64 variable.

### Tables are missing

Import `database/sonam.sql` into the empty `sonamDB` database.

### Uploads disappear after redeploy

Attach a Railway volume at:

```text
/var/www/html/assets/uploads
```

---

## Governance & Maintenance

Before using the deployment for real bookings, the maintainer should:

- rotate any Aiven password that has appeared in screenshots or support logs;
- verify Railway variables are correct and secrets are not committed;
- enable Aiven backups and test restoration;
- attach persistent upload storage;
- keep `database/sonam.sql` as an initialization script only;
- document who owns production credentials and deployment access;
- perform a final manual booking, cancellation, upload, login, and owner
  dashboard test after each production deployment.

---

## License

Private project repository unless a separate license is added.
