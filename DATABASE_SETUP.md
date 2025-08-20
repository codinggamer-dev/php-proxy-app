# SQLite Database Migration Guide

This guide helps you migrate from text-based auth codes to SQLite database storage.

## Quick Setup

1. **Initialize the database:**
   ```bash
   php init_db.php
   ```

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