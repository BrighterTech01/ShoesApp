# Deployment Guide

This project has two deployment parts:

- PHP/MySQL web system for the backend API and admin/seller panel
- Expo React Native app for customers

## 1. Deploy The PHP Backend And Admin Panel

Use a PHP + MySQL host. For free school/demo hosting, InfinityFree or AwardSpace can work.

### Upload These Files

Upload these project items to the hosting web root, usually `htdocs` or `public_html`:

- `backend/`
- `web-admin/`
- `storage/`
- `index.php`

Do not upload:

- `mobile-app/node_modules/`
- `mobile-app/dist-*`
- local XAMPP files

### Create The Hosting Database

In the hosting control panel:

1. Create a MySQL database.
2. Create or copy the database username.
3. Copy the database host, database name, username, and password.

### Edit Backend Config

Open `backend/config/app.php` on the hosting server and update:

```php
'base_url' => 'https://your-domain.com',
'backend_url' => 'https://your-domain.com/backend',
'uploads_url' => 'https://your-domain.com/backend/uploads',
```

Then update the database section:

```php
'host' => 'your-hosting-mysql-host',
'name' => 'your-hosting-database-name',
'username' => 'your-hosting-database-user',
'password' => 'your-hosting-database-password',
'auto_create_database' => false,
```

Most free/shared hosting does not allow PHP to create the database itself, so keep `auto_create_database` as `false` after you manually create the database in the hosting panel.

### Run Setup

Open this once in your browser:

```text
https://your-domain.com/backend/setup.php
```

Then open the admin panel:

```text
https://your-domain.com/web-admin/login.php
```

Default login:

- Email: `admin@shoes.local`
- Password: `admin12345`

Change this password after deployment.

## 2. Build A Downloadable Android App

The app uses Expo EAS Build. The project already has `mobile-app/eas.json`.

### Set The Live API URL

In `mobile-app/.env`, set:

```env
EXPO_PUBLIC_API_BASE_URL=https://your-domain.com/backend
```

If your host places the project in a subfolder, use:

```env
EXPO_PUBLIC_API_BASE_URL=https://your-domain.com/shoesS/backend
```

### Build APK For Direct Download

Run:

```powershell
cd C:\xampp\htdocs\shoesS\mobile-app
npx eas-cli@latest login
npx eas-cli@latest build --platform android --profile preview
```

When the build finishes, Expo gives you a download link for the APK. Open that link on your phone and install the app.

### Build AAB For Google Play

Run:

```powershell
cd C:\xampp\htdocs\shoesS\mobile-app
npx eas-cli@latest build --platform android --profile production
```

This creates an `.aab` file for Google Play Console.

## Important Notes

- Android phones may block direct APK installs until you allow "Install unknown apps" for your browser or file manager.
- The app must use the deployed backend URL, not `localhost` or `10.0.2.2`.
- Product images require `backend/uploads/` to be writable on the hosting server.
- If setup works locally but not online, the most common issue is wrong hosting database credentials.
