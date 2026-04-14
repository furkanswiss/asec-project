# How to Run ASEC Mains Project

## Prerequisites
- XAMPP installed (you already have it at `C:\xampp`)
- PHP 7.4+ (comes with XAMPP)
- MySQL (comes with XAMPP)

## Step-by-Step Setup

### 1. Start XAMPP Services

**Option A: Using XAMPP Control Panel**
1. Open **XAMPP Control Panel** (search for "XAMPP Control Panel" in Start Menu)
2. Click **Start** button for **Apache**
3. Click **Start** button for **MySQL**
4. Both should show green "Running" status

**Option B: Using Command Line**
```powershell
# Start Apache
C:\xampp\apache_start.bat

# Start MySQL
C:\xampp\mysql_start.bat
```

### 2. Set Up Database

1. Open phpMyAdmin:
   - Go to: http://localhost/phpmyadmin
   - Or click "Admin" button next to MySQL in XAMPP Control Panel

2. Create the database:
   - Click "New" in the left sidebar
   - Database name: `db_asec`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. Import SQL files (in order):
   - Click on `db_asec` database
   - Click "Import" tab
   - Import these files in order:
     - `admin/database.sql`
     - `admin/uyeler_tablosu.sql`
     - `admin/etkinlik_fotolar.sql`

   **OR** manually run the SQL commands:
   ```sql
   CREATE DATABASE IF NOT EXISTS db_asec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE db_asec;
   -- Then copy and paste contents from the SQL files
   ```

### 3. Verify Database Connection

1. Open your browser
2. Go to: `http://localhost/asec-mains/test_db.php`
3. You should see: "Veritabanına başarıyla bağlandı!"

### 4. Access Your Website

1. Open your browser
2. Go to: `http://localhost/asec-mains/`
   - Or: `http://localhost/asec-mains/index.php`

### 5. Access Admin Panel

1. Go to: `http://localhost/asec-mains/admin/`
2. If you need to create an admin account:
   - Go to: `http://localhost/asec-mains/admin/create_admin.php`

## Troubleshooting

### Apache won't start
- Check if port 80 is in use (usually by Skype or IIS)
- Change Apache port in XAMPP Control Panel → Config → Apache → httpd.conf
- Look for `Listen 80` and change to `Listen 8080`
- Then access site at: `http://localhost:8080/asec-mains/`

### MySQL won't start
- Check if port 3306 is in use
- Make sure no other MySQL service is running

### Database connection error
- Verify database name is `db_asec` (not `asec_db`)
- Check MySQL is running in XAMPP
- Verify username is `root` and password is empty (default XAMPP)

### Page not found
- Make sure you're accessing: `http://localhost/asec-mains/`
- Check Apache is running
- Verify files are in `C:\xampp\htdocs\asec-mains\`

## Quick Start Commands

```powershell
# Navigate to project
cd C:\xampp\htdocs\asec-mains

# Check if Apache is running
netstat -ano | findstr :80

# Check if MySQL is running
netstat -ano | findstr :3306
```

## Important URLs

- **Homepage**: http://localhost/asec-mains/
- **Admin Login**: http://localhost/asec-mains/admin/login.php
- **Database Test**: http://localhost/asec-mains/test_db.php
- **phpMyAdmin**: http://localhost/phpmyadmin


