# HAIPULSE â€” Business Directory & Classifieds Platform

> A comprehensive B2B business directory and classifieds platform for the UAE market.

---

## Overview

HAIPULSE is a full-featured business directory and B2B SaaS platform built for the UAE market. It provides businesses with a searchable online presence, lead generation tools, and a powerful admin dashboard for managing listings, subscriptions, and inquiries.

## Features

- Business directory with 700K+ company listings
- Advanced search with category, location, and keyword filters
- Company profiles with photos, contact info, and product listings
- Classifieds and job postings
- User accounts with favourites, search history, and inquiry tracking
- Admin dashboard with full CRUD, DataTables, and role-based access control
- Subscription tiers with Stripe payment integration
- Email queue system with PHPMailer
- SEO-optimised pages with JSON-LD structured data
- REST API endpoints

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.2+ (OOP, MVC-style) |
| Database | MySQL 8.1 |
| Frontend | Bootstrap 5, jQuery, HTML5 |
| Payments | Stripe |
| Email | PHPMailer + queue system |
| Server | LAMP / CloudLinux (production) |

## Requirements

- PHP 7.2 or higher
- MySQL 8.0 or higher
- Apache with `mod_rewrite` enabled
- Composer (for dependencies)

## Setup

1. Clone the repository
2. Copy `.env.example` to a location **outside the web root** (e.g. `G:\xampp\.env`) and fill in your credentials
3. Import the database schema from `database/`
4. Run `composer install`
5. Point your web server document root to the project folder
6. Visit the site in your browser

## Project Structure

```
pages/          Public-facing frontend pages
dashboard/      Admin backend (listings, CRUD, reports)
classes/        Core PHP classes and DataTable handlers
config/         Database connection, globals, helpers
includes/       Shared layout (header, footer, partials)
api/            JSON REST API endpoints
assets/         CSS, JS, fonts, images
cron/           Scheduled jobs (email alerts, sync tasks)
```

## Security

- CSRF protection on all forms and AJAX actions
- Prepared statements throughout (no raw user input in SQL)
- Role-based access control on all dashboard routes
- XSS prevention with `htmlspecialchars()` / `e()` helper on all output
- `.env` credentials stored outside the web root

## License

Proprietary â€” All rights reserved. HAI Technologies.

- **[ai/domain-rules.md](ai/domain-rules.md)** - Business logic
  - Company verification, categories, HS codes
  - Email campaigns, invoicing, referrals
  - User roles, permissions, validation rules (700+ lines)

### API & Integration (30 minutes)
- **[INDEX.md](INDEX.md)** - JSON API documentation
  - 5 public endpoints
  - 746K+ companies data
  - Quick code examples

---

## ðŸ“ Project Structure

```
G:\xampp\htdocs\haipulse\
â”œâ”€â”€ pages/              â†’ 87 frontend public pages
â”œâ”€â”€ dashboard/          â†’ 100+ admin listing pages, CRUD, email system
â”œâ”€â”€ classes/            â†’ DB registry, frontend models, 50+ DataTable handlers
â”œâ”€â”€ config/             â†’ Database, globals (2813 lines), logging, images
â”œâ”€â”€ includes/           â†’ Layout templates, reusable components
â”œâ”€â”€ api/                â†’ 5 JSON API endpoints
â”œâ”€â”€ index.php           â†’ Main router with 150+ routes
â””â”€â”€ .github/
    â””â”€â”€ copilot-instructions.md â†’ AI coding standards
â””â”€â”€ ai/
    â”œâ”€â”€ project-context.md â†’ Comprehensive project overview
    â”œâ”€â”€ architecture.md â†’ System architecture & patterns
    â””â”€â”€ domain-rules.md â†’ Business logic & validation
```

---

## ðŸŽ¯ For Different Users

### ðŸ‘¨â€ðŸ’» Developer Starting New Feature
1. Read: [.github/copilot-instructions.md](.github/copilot-instructions.md) (coding standards)
2. Read: [ai/architecture.md](ai/architecture.md) (how system works)
3. Reference: [ai/domain-rules.md](ai/domain-rules.md) (business logic)
4. Check: [claude.md](claude.md) (quick commands & troubleshooting)

### ðŸ—ï¸ Architect Reviewing System
1. Read: [ai/project-context.md](ai/project-context.md) (project scope)
2. Read: [ai/architecture.md](ai/architecture.md) (system design)
3. Reference: [ai/domain-rules.md](ai/domain-rules.md) (business constraints)

### ðŸ¤– AI/Claude Working on Code
1. Read instructions from: [.github/copilot-instructions.md](.github/copilot-instructions.md)
2. Context from: [ai/project-context.md](ai/project-context.md)
3. Patterns from: [ai/architecture.md](ai/architecture.md)
4. Rules from: [ai/domain-rules.md](ai/domain-rules.md)

### ðŸ“± Developer Integrating API
1. Start: [INDEX.md](INDEX.md) (API quick start)
2. Details: [INDEX.md](INDEX.md) (full API reference)
3. Examples: [INDEX.md](INDEX.md) (code samples)

---

## ðŸš€ Quick Commands

```powershell
# Clear logs
Clear-Content "dashboard/CONSOLIDATED_ERROR_LOG.txt"

# Database backup
$dt = Get-Date -Format "yyyyMMdd_HHmmss"
& "C:\xampp\mysql\bin\mysqldump" -u root -phai@30 haipulse > "backup_$dt.sql"

# Test PHP syntax
& "C:\xampp\php\php.exe" -l "path/to/file.php"

# Start XAMPP servers
& "C:\xampp\xampp_start.exe"  # or use XAMPP Control Panel
```

---

## ðŸ’¾ Database Credentials

```
Database: haipulse
User: root
Password: hai@30
Local: localhost:3306
Frontend: http://127.0.0.1/haipulse/
Dashboard: http://127.0.0.1/haipulse/dashboard/
phpMyAdmin: http://127.0.0.1/phpmyadmin/
```

---

## âœ… Documentation Status

- âœ… Copilot Instructions ([.github/copilot-instructions.md](.github/copilot-instructions.md)) - Complete (400+ lines)
- âœ… Project Context ([ai/project-context.md](ai/project-context.md)) - Complete (600+ lines)
- âœ… Architecture ([ai/architecture.md](ai/architecture.md)) - Complete (1,000+ lines)
- âœ… Domain Rules ([ai/domain-rules.md](ai/domain-rules.md)) - Complete (700+ lines)
- âœ… API Reference ([INDEX.md](INDEX.md)) - Complete (515 lines)
- âœ… Quick Reference ([claude.md](claude.md)) - Complete (429 lines)

**Last Updated:** February 28, 2026

