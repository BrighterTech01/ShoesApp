# Local Development

Run the backend locally:

```powershell
cd C:\xampp\htdocs\shoesS
powershell -ExecutionPolicy Bypass -File scripts\start-local-backend.ps1
```

This starts:

- Web Admin: `http://127.0.0.1:8000/web-admin/login.php`
- Backend Health: `http://127.0.0.1:8000/backend/health.php`

Default local database settings used by the script:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `shoesap`
- Username: `root`
- Password: `Barrientos90`

You can override them:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-local-backend.ps1 -DbPass ""
```

Run the mobile app locally:

```powershell
cd C:\xampp\htdocs\shoesS
powershell -ExecutionPolicy Bypass -File scripts\start-local-mobile.ps1
```

For Expo Go on a physical phone, start the backend first, then start the mobile app script. The mobile script automatically uses your local network IP like:

```text
http://192.168.x.x:8000/backend
```

Helpful modes:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-local-mobile.ps1 -Mode go
powershell -ExecutionPolicy Bypass -File scripts\start-local-mobile.ps1 -Mode android:go
powershell -ExecutionPolicy Bypass -File scripts\start-local-mobile.ps1 -Mode web
```
