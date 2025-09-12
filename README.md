# FAQ System

A PHP-based FAQ system with admin panel and public interface.

## Features

### Admin Section (/admin)
- Create new articles
- View all existing articles
- Get shareable links for articles (www.domeinnaam.be/search.php?search="artikel_title")

### Public Section
- Overview of all articles (/)
- Individual article pages (www.domeinnaam.be/search.php?search="artikel_title")

## Tech Stack
- PHP 8.0+ with snake_case naming conventions
- Bramus Router for routing
- Twig templating engine
- TailwindCSS for styling
- MySQL database
- PHPStan for static analysis

## Setup

1. Install dependencies:
```bash
composer install
```

2. Import database:
```bash
mysql -u root -p < database.sql
```

3. Configure database connection in `src/Database.php` if needed

4. Run PHPStan validation:
```bash
vendor/bin/phpstan analyse
```

## Database Configuration

Update the database credentials in `src/Database.php`:
```php
private string $host = 'localhost',
private string $db_name = 'faq_system',
private string $username = 'root',
private string $password = ''
```

## URLs

- Home: `/`
- Admin: `/admin`
- Article: `/search.php?search=Article+Title`