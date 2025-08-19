
<center>
  
![](https://img.shields.io/packagist/dt/Athlon1600/php-proxy-app.svg) ![](https://img.shields.io/github/last-commit/Athlon1600/php-proxy-app.svg) ![](https://img.shields.io/github/license/Athlon1600/php-proxy-app.svg)

</center>


# php-proxy-app

Web Proxy Application built on [**php-proxy library**](https://github.com/Athlon1600/php-proxy) ready to be installed on your server

![alt text](http://i.imgur.com/KrtU5KE.png?1 "This is how PHP-Proxy looks when installed")

## 🔐 Authentication System

This proxy now includes a built-in authentication system that requires users to login with codes provided by administrators:

### Features
- **Code-based Authentication**: Users must enter a valid login code to access the proxy
- **Admin Panel**: Administrators can manage login codes through a web interface
- **Session Management**: Authenticated sessions with configurable timeout
- **Real-time Code Validation**: When codes are removed, users are automatically logged out on their next request
- **Secure Storage**: Login codes are stored in a protected file outside the web root

### Quick Setup
1. Enable authentication in `config.php`: `$config['auth_enable'] = true;`
2. Access the proxy - you'll be redirected to the login page
3. Use one of the default codes: `DEMO123`, `CODE456`, or `TEST789`
4. After login, access the admin panel via the "Admin Panel" link to manage codes

### Default Login Codes
The system comes with these demo codes (change them immediately):
- `admin` → `DEMO123`
- `user1` → `CODE456` 
- `test` → `TEST789`

### Configuration Options
```php
$config['auth_enable'] = true;                    // Enable/disable authentication
$config['auth_codes_file'] = './auth_codes.txt';  // Path to codes file
$config['auth_session_timeout'] = 3600;           // Session timeout in seconds (1 hour)
```

### Admin Panel
- Access via the "Admin Panel" link after authentication
- Add new login codes with custom names
- Delete existing codes (users with deleted codes are immediately logged out)
- View system information and code statistics

### Security Notes
- The `auth_codes.txt` file is automatically excluded from version control
- Login codes are case-sensitive
- Sessions expire after the configured timeout period
- Users are automatically logged out when their code is deleted

## To Do List

As of **March 25**, 2018:

* Plugin for facebook.com  
* Plugin for dailymotion.com
* Better support/documentation for Plugin Development
* Better Javascript support

## Web-Proxy vs Proxy Server

Keep in mind that sites/pages that are too script-heavy or with too many "dynamic parts", may not work with this proxy script.
That is a known limitation of web proxies. For such sites, you should use an actual proxy server to route your browser's HTTP requests through:  

https://www.proxynova.com/proxy-software/


## Installation

Keep in mind that this is a **project** and not a library. Installing this via *require* would do you not good.
A project such as this, should be installed straight into the public directory of your web server.

```bash
composer create-project athlon1600/php-proxy-app:dev-master /var/www/
```

If you do not have composer or trying to host this application on either a **shared hosting**, or a VPS hosting with limited permissions (dreamhost.com), then download a pre-installed version of this app as a ZIP archive from [**www.php-proxy.com**](https://www.php-proxy.com/).

**Direct Link:**  
https://www.php-proxy.com/download/php-proxy.zip

## Keep it up-to-date

Application itself rarely will change, vast majority of changes will be done to its requirement packages like php-proxy. Simply call this command once in a while to make sure your proxy is always using the latest versions.

```
composer update
```

#### config.php

This file will be loaded into the global Config class.

#### /templates/

This should have been named "views", but for historic purposes we keep it named as templates for now.

#### /plugins/

PHP-Proxy provides many of its own native plugins, but users are free to write their own custom plugins, which could then be automatically loaded from this very folder. See /plugins/TestPlugin.php for an example.
