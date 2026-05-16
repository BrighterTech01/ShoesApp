# FIt Me In Sho Esia

Native PHP + MySQL backend with:

- `web-admin/` for admin and seller management
- `backend/` for the shared PHP API
- `mobile-app/` for the React Native customer app

## Included modules

- Admin/seller dashboard
- Category and brand management
- Product and variant management
- Inventory monitoring
- Order processing and status updates
- User management
- Sales and inventory reports
- Customer register/login
- Product browsing
- Product details and size/color selection
- Cart management
- COD checkout
- Order history
- Profile update

## MySQL auto-create

The PHP backend creates the database and tables automatically the first time it can connect to MySQL.

Default config is in `backend/config/app.php`:

- Database name: `shoesap`
- Host: `127.0.0.1`
- Port: `3306`
- Username: `root`
- Password: `Barrientos90`
- Auto-create database: enabled by default for local XAMPP

If your local MySQL account uses different credentials, update that one file.

## Default seeded accounts

- Admin: `admin@shoes.local`
- Seller: `seller@shoes.local`
- Customer: `customer@shoes.local`
- Password for all: `admin12345`

## Admin panel

Open:

- `http://localhost/shoesS/web-admin/login.php`

## API base

Main entry:

- `http://localhost/shoesS/backend/index.php`
- `http://localhost/shoesS/backend/setup.php`

## Mobile app

Inside `mobile-app/`:

1. Install dependencies with `npm.cmd install`
2. Start Expo with `npm.cmd run start`
3. For Android emulator, the API base already uses `10.0.2.2`
4. For a real phone or production build, set `EXPO_PUBLIC_API_BASE_URL`

Example:

```powershell
$env:EXPO_PUBLIC_API_BASE_URL="http://192.168.0.100/shoesS/backend"
npm.cmd run dev
```

You can also copy `mobile-app/.env.example` to `.env` and replace the URL with your live backend URL.

## Free hosting deployment

Recommended free host for this stack:

- InfinityFree: PHP, MySQL, SSL, and subdomain support
- AwardSpace: PHP and MySQL free hosting

### Deploy the PHP backend and admin panel

1. Create a free hosting account and create a MySQL database in the hosting panel.
2. Upload the project files using the host file manager or FTP.
3. Put the project contents inside your public web root, usually `htdocs/` or `public_html/`.
4. Update `backend/config/app.php` with the hosting database name, username, password, and your live site URL.
5. On shared hosting, set `DB_AUTO_CREATE=0` or change `'auto_create_database' => false` in `backend/config/app.php` if the host blocks `CREATE DATABASE`. The code now handles this case and will still create the tables inside an existing database.
6. Open `https://your-domain.example.com/backend/setup.php` once to create the tables.
7. Open `https://your-domain.example.com/web-admin/login.php` to verify the admin panel works.

## App deployment

This project now includes `mobile-app/eas.json` for Expo EAS builds.

### Android test APK

```powershell
cd mobile-app
npx eas-cli@latest login
$env:EXPO_PUBLIC_API_BASE_URL="https://your-domain.example.com/backend"
npx eas-cli@latest build --platform android --profile preview
```

### Android Play Store build

```powershell
cd mobile-app
npx eas-cli@latest login
$env:EXPO_PUBLIC_API_BASE_URL="https://your-domain.example.com/backend"
npx eas-cli@latest build --platform android --profile production
```

The `preview` profile creates an installable `.apk`. The `production` profile creates an `.aab` for Google Play.

## Notes

- PHP session files are stored inside `storage/sessions/` in this project so the app does not depend on XAMPP temp folder permissions.
- Product uploads go to `backend/uploads/`.
- Orders use Cash on Delivery only.
