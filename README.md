# Sonam Homestay - Homestay Booking


## Setup (XAMPP)

1. Copy project to `C:\xampp\htdocs\HomeStay`
2. Start Apache + MySQL
3. Open: http://localhost/HomeStay/database/install.php
4. Open website: http://localhost/HomeStay/

## Important rules

- **users or homestays** — database starts empty
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
