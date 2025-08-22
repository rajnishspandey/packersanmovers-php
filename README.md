# PackersAnMovers PHP

A professional packers and movers website built with PHP, MySQL, and Bootstrap.

## Local Development Setup

### Prerequisites
1. **Install PHP** (if not already installed)
   ```bash
   # Check if PHP is installed
   php --version
   
   # If not installed, install via Homebrew
   brew install php
   ```

2. **Install Apache** (for .htaccess support)
   ```bash
   # Install Apache via Homebrew
   brew install httpd
   
   # Start Apache
   brew services start httpd
   ```

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   git clone <your-repo-url>
   cd packersanmovers-php
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup database**
   - Create MySQL database
   - Import `mysql-schema.sql`
   - Or run `/run-migration` for existing databases

4. **Configure Apache**
   ```bash
   # Edit Apache config
   sudo nano /usr/local/etc/httpd/httpd.conf
   
   # Find and uncomment these lines:
   # LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so
   # Include /usr/local/etc/httpd/extra/httpd-vhosts.conf
   
   # Change DocumentRoot to your project path:
   # DocumentRoot "/path/to/your/packersanmovers-php"
   # <Directory "/path/to/your/packersanmovers-php">
   #     AllowOverride All
   #     Require all granted
   # </Directory>
   ```

5. **Restart Apache**
   ```bash
   brew services restart httpd
   ```

6. **Access the website**
   - Open browser and go to: `http://localhost:8080`
   - Test clean URLs: `http://localhost:8080/about`, `http://localhost:8080/services`

### Alternative: Quick Testing with PHP Server

If you don't want to configure Apache, use this for basic testing:

1. **Start PHP server with router**
   ```bash
   php -S localhost:8000 router.php
   ```

2. **Test URLs**
   - Home: `http://localhost:8000`
   - About: `http://localhost:8000/about`
   - Services: `http://localhost:8000/services`
   - Contact: `http://localhost:8000/contact`
   - Admin: `http://localhost:8000/pmlogin`

### Features

- Clean URLs (no .php extensions)
- Responsive design with Bootstrap 5
- Admin panel for managing settings
- Lead management system
- Contact form with email notifications
- SEO optimized

### File Structure

```
packersanmovers-php/
├── assets/
│   ├── css/style.css
│   ├── img/
│   └── js/main.js
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── mail.php
├── .htaccess (for Apache)
├── router.php (for PHP server)
├── config.php
├── mysql-schema.sql
├── migrate.php
├── composer.json
└── *.php (page files)
```

### Deployment to Production

1. Upload all files to your domain's public_html folder
2. Run `composer install` on server
3. Create `.env` file with database credentials
4. Import `mysql-schema.sql` or run `/run-migration`
5. The `.htaccess` file will automatically handle clean URLs

### Troubleshooting

**Clean URLs not working?**
- Ensure Apache mod_rewrite is enabled
- Check `.htaccess` file permissions
- Verify AllowOverride is set to All in Apache config

**Database errors?**
- Check MySQL connection in `.env` file
- Run `/run-migration` to fix schema issues
- Ensure MySQL extension is installed: `php -m | grep mysql`

**Email not sending?**
- Update SMTP settings in `config.php`
- Use app-specific passwords for Gmail