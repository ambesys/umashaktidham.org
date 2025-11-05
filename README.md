# Uma Shakti Dham Website

A comprehensive PHP-based website for Uma Shakti Dham, a Hindu temple and community center serving the community with religious, cultural, and social services.

## Features

### 🏛️ Temple & Community Services
- **Temple Information**: Location, hours, facilities, and contact details
- **Religious Content**: Hindu gods, scriptures, rituals, and festivals
- **Cultural Events**: Community gatherings, festivals, and celebrations
- **Youth Corner**: Programs and activities for younger community members
- **Business Directory**: Local business networking and directory

### 👥 User Management
- **Multi-role System**: Users, Sponsors, Moderators, and Administrators
- **Family Management**: Complete family profile management
- **Secure Authentication**: Traditional login, OAuth (Google/Facebook), WebAuthn/Passkeys
- **Password Reset**: Secure email-based password recovery

### 📅 Event Management
- **Event Registration**: Full event management with guest support
- **Ticketing System**: Multiple ticket types and pricing
- **Coupon System**: Discount codes with usage limits
- **Check-in System**: Admin attendee check-in with timestamps
- **Capacity Management**: Automatic capacity enforcement

### 💰 Payment & Sponsorship
- **Secure Payments**: Payment processing for donations and sponsorships
- **Sponsorship Tracking**: Payment history and sponsorship management
- **Affiliate Links**: Custom discount links for sponsors

### 🔧 Administration
- **User Management**: Admin tools for user and family data management
- **Event Administration**: Create, edit, and manage events
- **Content Management**: Update site information and static content
- **Broadcast Communications**: Email notifications to users and sponsors

## Technology Stack

- **Backend**: PHP 7.4+ / 8.0+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Authentication**: OAuth2 (Google/Facebook), WebAuthn/Passkeys
- **Testing**: PHPUnit
- **Dependencies**: Composer for PHP package management

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx) with mod_rewrite

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/ambesys/umashaktidham.org.git
   cd umashaktidham.org
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your database credentials and other settings:
   ```env
   DB_HOST=localhost
   DB_NAME=umashaktidham
   DB_USER=your_db_user
   DB_PASS=your_db_password

   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   FACEBOOK_CLIENT_ID=your_facebook_client_id
   FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
   ```

4. **Database Setup**
   ```bash
   # Create database
   mysql -u your_user -p -e "CREATE DATABASE umashaktidham;"

   # Run migrations
   mysql -u your_user -p umashaktidham < database/migrations/2025_01_15_create_initial_schema.sql

   # Seed initial data
   mysql -u your_user -p umashaktidham < database/seeds/roles_seed.sql
   ```

5. **Web Server Configuration**
   - Point your web server document root to the `public/` directory
   - Ensure proper rewrite rules for clean URLs
   - Set appropriate file permissions for uploads and cache directories

6. **File Permissions**
   ```bash
   chmod 755 public/assets/uploads
   chmod 755 storage/logs
   ```

## Usage

### User Roles

- **Public Users**: Browse temple information, view events, contact forms
- **Registered Users**: All public features plus event registration, family management
- **Sponsors**: User features plus sponsorship management and affiliate links
- **Moderators**: User features plus event creation, user management, statistics
- **Administrators**: Full system access and configuration

### Key URLs

- `/` - Homepage
- `/events` - Event listing and registration
- `/membership` - User registration and login
- `/donate` - Donation and sponsorship
- `/admin` - Administrative dashboard (admin/moderator only)

## Development

### Running Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/EventServiceTest.php
```

### Code Structure
```
├── config/           # Configuration files
├── database/         # Migrations and seeds
│   ├── migrations/   # Database schema files
│   └── seeds/        # Initial data
├── public/           # Web root directory
│   ├── assets/       # CSS, JS, images
│   └── index.php     # Front controller
├── scripts/          # Utility scripts
├── src/              # Application source code
│   ├── Controllers/  # HTTP request handlers
│   ├── Models/       # Data models
│   ├── Services/     # Business logic
│   ├── Views/        # Templates and layouts
│   └── helpers.php   # Utility functions
├── tests/            # Unit tests
└── vendor/           # Composer dependencies
```

### Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

- All user inputs are validated and sanitized
- Passwords are hashed using secure algorithms
- Session management with secure tokens
- CSRF protection on forms
- SQL injection prevention with prepared statements
- XSS protection with output escaping

## License

This project is proprietary software for Uma Shakti Dham organization.

## Support

For technical support or questions:
- Email: admin@umashaktidham.org
- Create an issue in this repository

## Deployment

### Production Setup
1. Set up production database
2. Configure environment variables
3. Run database migrations
4. Set up SSL certificate
5. Configure backup procedures
6. Set up monitoring and logging

### Automated Deployment
Use the provided `scripts/install.sh` for initial setup, then configure your CI/CD pipeline for automated deployments.

---

**Uma Shakti Dham** - Serving the community with devotion, culture, and service since 2000.
