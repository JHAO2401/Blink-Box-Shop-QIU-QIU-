# Blink-Box-Shop-QIU-QIU-

A PHP-based online shop website.

## Features

- Product browsing and storefront
- Shopping cart / order flow
- Admin management panel (`admin_manage`)
- User accounts and authentication
- Product images and static assets

## Tech Stack

- PHP
- HTML/CSS/JavaScript
- MySQL (data layer under `data/`)

## Project Structure

```
admin_manage/   # Admin backend
css/            # Stylesheets
data/           # Data files / database
function/       # PHP helper functions
image/          # Product and site images
js/             # JavaScript
store/          # Store-related pages
system/         # System core files
index.php       # Entry point
```

## Getting Started

1. Clone the repository:

```bash
git clone https://github.com/JHAO2401/Blink-Box-Shop-QIU-QIU-.git
cd Blink-Box-Shop-QIU-QIU-
```

2. Place the files on a PHP-enabled web server (e.g., XAMPP, WampServer, or Apache + PHP).

3. Import the database schema from `data/` into MySQL and update the database connection settings in `system/` or `function/` as needed.

4. Open `index.php` in your browser.
