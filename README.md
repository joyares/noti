# Noti

A open source PHP MVC based note-taking web application with tag-based organization, rich text support, and mobile API integration.

## Description

Noti is a lightweight, self-hosted note-taking application built with a custom PHP MVC architecture. It provides users with a powerful way to organize, search, and manage their notes through notebooks, tags, and a trash system. The application includes a full REST API for mobile app integration and supports attachments (images, files, audio).

## Features

- **User Authentication** — Secure login and registration system
- **Notebooks** — Organize notes by color-coded notebooks with custom icons
- **Notes** — Create and edit rich-text notes (HTML-based)
- **Tags** — Flexible tag-based note organization for cross-notebook categorization
- **Full-Text Search** — Search notes by title and content
- **Attachments** — Attach images, files, and audio to notes
- **Pinned Notes** — Pin important notes for quick access
- **Trash System** — Soft-delete notes with restore capability
- **Locked Notebooks** — Password-protected notebooks (e.g., for sensitive data)
- **REST API** — Complete API for mobile app integration with token-based authentication
- **Responsive UI** — Clean, modern interface built with vanilla JavaScript

## Minimum Requirements

- **PHP** 8.1+
- **MySQL** 8.0+
- **Web Server** (Apache, Nginx, or PHP built-in server)
- **Modern Browser** with JavaScript enabled

## Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/joyares/noti.git
cd noti
```

### 2. Configure Database
Create a MySQL database named 
oti:
```bash
mysql -u root -e "CREATE DATABASE noti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Initialize Database Schema
```bash
mysql -u root noti < migrations/001_init.sql
```

### 4. Configure Application
Copy and update the configuration file:
```bash
cp config/config.php config/config.local.php
```

Edit config/config.local.php with your database credentials:
```php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'noti',
        'user' => 'root',
        'pass' => '',  // Set your MySQL password if needed
    ],
    // ... other config
];
```

### 5. Create Upload Directory
```bash
mkdir -p storage/uploads
chmod 755 storage/uploads
```

### 6. Start Development Server
```bash
php -S 127.0.0.1:8123 -t public server.php
```

Then open http://127.0.0.1:8123 in your browser.

## Test Account

A test account is included for development:
- **Email:** test@noti.app
- **Password:** password123

## API Documentation

The complete REST API specification is documented in API.md. The API supports:
- User authentication
- CRUD operations for notebooks, notes, and tags
- Search functionality
- File uploads
- Token-based authentication for mobile clients

## Project Structure

```
noti/
├── app/
│   ├── Core/              # MVC framework core (Router, Controller, Model, etc.)
│   ├── Controllers/       # Web and API controllers
│   ├── Models/            # Database models
│   ├── Services/          # Business logic services
│   ├── Helpers/           # Utility functions and icon assets
│   └── Views/             # UI templates
├── config/                # Application configuration
├── migrations/            # Database schema
├── public/                # Web root (assets, index.php)
├── storage/               # User uploads directory
├── server.php             # Development server router
└── API.md                 # REST API documentation
```

## Technology Stack

- **Backend:** PHP 8.1+ (custom MVC, no external framework)
- **Database:** MySQL 8.0+ with InnoDB
- **Frontend:** Vanilla JavaScript, HTML5, CSS3
- **Architecture:** Single responsibility, middleware-based

## Author

**Mostafa Joy**
- LinkedIn: [joyoares](https://www.linkedin.com/in/joyoares/)
- GitHub: [joyares](https://github.com/joyares)

## License

Open source — see LICENSE file for details.

## Support

For issues, feature requests, or contributions, please visit the [GitHub repository](https://github.com/joyares/noti).
