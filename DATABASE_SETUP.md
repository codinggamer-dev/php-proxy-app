# SQLite Database Migration Guide

This guide helps you migrate from text-based auth codes to SQLite database storage.

## Quick Setup

1. **Initialize the database:**
   ```bash
   php init_db.php
   ```
   
   **Note:** The database file will be created with proper permissions (664) to allow web server access. If you encounter "readonly database" errors, ensure:
   - The database file has 664 permissions (`chmod 664 auth_codes.db`)
   - The directory containing the database is writable by the web server
   - The web server user has read/write access to both the file and directory

2. **Create your first admin user:**
   ```bash
   php -r "
   require_once('AuthCodesDB.php');
   \$db = new AuthCodesDB('./auth_codes.db');
   \$db->addCode('admin', 'YOUR_ADMIN_CODE', 1);
   echo 'Admin user created with code: YOUR_ADMIN_CODE\n';
   "
   ```

3. **Access the admin panel:**
   - Login with your admin code
   - Go to `yoursite.com?admin=1`

## Database Schema

The SQLite database stores auth codes with the following structure:

- `id`: Unique identifier (auto-increment)
- `name`: Human-readable name for the code
- `code`: The actual authentication code (unique)
- `admin_access`: 0 = regular user, 1 = admin access
- `created_timestamp`: Unix timestamp of creation

## Migration from Text File

If you have existing codes in `auth_codes.txt`, you can migrate them manually through the admin panel or create a migration script.

## Troubleshooting

### "SQLSTATE[HY000]: General error: 8 attempt to write a readonly database"

This error occurs when the SQLite database file doesn't have proper permissions for the web server. To fix:

1. **Check file permissions:**
   ```bash
   ls -la auth_codes.db
   ```
   Should show `-rw-rw-r--` (664 permissions)

2. **Fix file permissions if needed:**
   ```bash
   chmod 664 auth_codes.db
   ```

3. **Check directory permissions:**
   ```bash
   ls -la .
   ```
   The directory should be writable by the web server user

4. **Fix directory permissions if needed:**
   ```bash
   chmod 775 .
   ```

5. **Re-initialize database with proper permissions:**
   ```bash
   rm auth_codes.db
   php init_db.php
   ```

The AuthCodesDB class now includes auto-initialization and detailed error messages to help diagnose permission issues.

## Admin Panel Features

- ✅ Add new codes with admin access control
- ✅ View all codes with admin status indicators  
- ✅ Toggle admin access for existing codes
- ✅ Delete codes
- ✅ Admin access verification

## Security Notes

- The `auth_codes.db` file is automatically added to `.gitignore`
- Only users with `admin_access = 1` can access the admin panel
- Database file should be placed outside the web root in production
- Consider using strong, unique codes for better security