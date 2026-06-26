# Novaone Deploy Guide

## 1. Chuan bi source

Copy `.env.example` thanh `.env` tren server va sua:

```env
APP_NAME=Novaone
APP_ENV=production
APP_DEBUG=false
APP_URL=https://novaone.tenmiencuaban.com

DEMO_EMAIL=admin@novaone.local
DEMO_PASSWORD=mat-khau-manh
DEMO_NAME=Admin Novaone
DEMO_ROLE=Admin
```

Thu muc can co quyen ghi:

```text
storage/
public/uploads/
```

## 2. Shared hosting Apache

Upload source len hosting. Neu hosting cho tro domain vao thu muc goc source, giu `.htaccess` hien tai.

Dam bao cac thu muc sau khong truy cap public:

```text
app/
config/
database/
scripts/
storage/
```

File `.htaccess` hien tai da chan cac thu muc nay.

## 3. VPS Nginx

Vi du source o:

```text
/var/www/novaone
```

Nginx:

```nginx
server {
    listen 80;
    server_name novaone.tenmiencuaban.com;
    root /var/www/novaone;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/(app|config|database|scripts|storage)(/|$) {
        deny all;
    }

    location ~ /\.(env|git) {
        deny all;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Quyen ghi:

```bash
sudo chown -R www-data:www-data /var/www/novaone/storage /var/www/novaone/public/uploads
sudo chmod -R 775 /var/www/novaone/storage /var/www/novaone/public/uploads
```

SSL:

```bash
sudo certbot --nginx -d novaone.tenmiencuaban.com
```

## 4. Tro APK vao domain public

Sua `capacitor.config.json`:

```json
{
  "appId": "com.novaone.admin",
  "appName": "Novaone",
  "webDir": "mobile-www",
  "server": {
    "url": "https://novaone.tenmiencuaban.com",
    "cleartext": false
  }
}
```

Build lai APK:

```powershell
npm.cmd run apk:debug
```

APK nam tai:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```
