# Huong Dan Tao APK Novaone

Tai lieu nay huong dan khoi tao va build file APK cho ung dung Novaone. App Android hien tai duoc tao bang Capacitor, nghia la APK se mo website Novaone trong WebView Android. Muon app dung duoc day du chuc nang, website PHP phai dang chay va dien thoai phai truy cap duoc URL cua website.

## 1. Yeu Cau Moi Truong

Can cai cac cong cu sau tren may build:

- PHP 8.x va Laragon/XAMPP de chay website Novaone.
- Node.js va npm.
- Java JDK 21.
- Android Studio hoac Android SDK.
- Git neu muon lay source tu GitHub.
- Cloudflared neu muon test nhanh tren dien thoai bang link public mien phi.

Kiem tra nhanh:

```powershell
php -v
node -v
npm -v
java -version
```

Neu dung PHP cua Laragon trong du an nay, lenh PHP co the la:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' -v
```

## 2. Chay Website Novaone Local

Mo PowerShell tai thu muc du an:

```powershell
cd C:\laragon\www\Novaone
```

Chay server PHP:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' -S localhost:8000 router.php
```

Mo trinh duyet de kiem tra:

```text
http://localhost:8000
```

Dang nhap tai khoan test:

```text
Email: admin@novaone.local
Mat khau: admin123
```

## 3. Tao Link Public De App Android Truy Cap

Dien thoai Android khong truy cap duoc `localhost` tren may tinh cua ban. Vi vay can mot URL public hoac URL trong cung mang LAN.

Cach test nhanh mien phi bang Cloudflare Tunnel:

Mo PowerShell thu hai:

```powershell
cd C:\laragon\www\Novaone
cloudflared tunnel --url http://localhost:8000
```

Cloudflared se in ra link dang:

```text
https://xxxxx.trycloudflare.com
```

Hay copy link nay de cau hinh cho APK.

Luu y: link `trycloudflare.com` la link tam thoi. Khi tat tunnel, link co the mat. De giao cho khach test lau dai, nen dua website len hosting/VPS/domain that.

## 4. Cau Hinh Capacitor Cho APK

Mo file:

```text
capacitor.config.json
```

Noi dung mau:

```json
{
  "appId": "com.novaone.admin",
  "appName": "Novaone",
  "webDir": "mobile-www",
  "server": {
    "url": "https://your-public-domain.com",
    "cleartext": false
  }
}
```

Trong do:

- `appId`: ma dinh danh app Android.
- `appName`: ten hien thi cua app.
- `webDir`: thu muc web tinh cho Capacitor, van can co du de Capacitor build.
- `server.url`: URL website Novaone ma app se mo.
- `cleartext: false`: dung cho HTTPS.

Neu test bang link Cloudflare Tunnel, thay `server.url` bang link Cloudflare vua tao.

Vi du:

```json
"server": {
  "url": "https://xxxxx.trycloudflare.com",
  "cleartext": false
}
```

Neu dung HTTP noi bo, vi du `http://192.168.1.10:8000`, can de:

```json
"server": {
  "url": "http://192.168.1.10:8000",
  "cleartext": true
}
```

## 5. Cai Thu Vien Node

Neu moi clone source ve, chay:

```powershell
cd C:\laragon\www\Novaone
npm install
```

Lenh nay cai Capacitor CLI va thu vien Android theo `package.json`.

## 6. Dong Bo Cau Hinh Sang Android

Sau khi sua `capacitor.config.json`, chay:

```powershell
npm run mobile:sync
```

Hoac:

```powershell
npx cap sync android
```

Lenh nay cap nhat cau hinh app Android theo config moi.

## 7. Build File APK Debug

Chay lenh:

```powershell
npm run apk:debug
```

Lenh nay tuong duong:

```powershell
npx cap sync android
cd android
.\gradlew.bat assembleDebug
```

Sau khi build thanh cong, file APK nam tai:

```text
C:\laragon\www\Novaone\android\app\build\outputs\apk\debug\app-debug.apk
```

Day la file co the gui cho khach hang test.

## 8. Cai APK Len Dien Thoai Android

Co 2 cach cai:

### Cach 1: Copy APK vao dien thoai

1. Copy file `app-debug.apk` sang dien thoai.
2. Mo file APK tren dien thoai.
3. Cho phep "Cai dat ung dung khong ro nguon goc" neu Android hoi.
4. Bam cai dat.

### Cach 2: Cai bang ADB

Ket noi dien thoai voi may tinh, bat USB Debugging, sau do chay:

```powershell
adb install -r android\app\build\outputs\apk\debug\app-debug.apk
```

## 9. Cach Test App Sau Khi Cai

Truoc khi mo app tren dien thoai, can dam bao:

1. Website Novaone dang chay tren may tinh hoac server.
2. URL trong `capacitor.config.json` dang truy cap duoc tu dien thoai.
3. Neu dung Cloudflare Tunnel, cua so `cloudflared tunnel` van dang bat.
4. Dien thoai co internet.

Mo app Novaone tren dien thoai va dang nhap bang tai khoan test.

## 10. Loi Thuong Gap

### App mo len trang trang hoac khong tai duoc

Nguyen nhan thuong gap:

- Website PHP chua chay.
- Link Cloudflare Tunnel da tat hoac da doi.
- `server.url` trong `capacitor.config.json` sai.
- Chua chay `npm run mobile:sync` sau khi sua config.
- Dien thoai khong co internet.

Khac phuc:

```powershell
cd C:\laragon\www\Novaone
npm run mobile:sync
npm run apk:debug
```

Sau do cai lai APK moi.

### Build loi Java

Kiem tra Java:

```powershell
java -version
```

Du an nen dung JDK 21. Neu may co nhieu ban Java, can cau hinh `JAVA_HOME` tro ve JDK dung.

### Build loi Android SDK

Mo Android Studio va cai:

- Android SDK Platform.
- Android SDK Build-Tools.
- Android SDK Platform-Tools.

Sau do chay lai:

```powershell
npm run apk:debug
```

### APK cai duoc nhung chuc nang khong hoat dong

APK chi la lop vo Android boc website. Neu website backend khong online hoac URL public khong dung, chuc nang se khong chay.

Voi khach hang test that, nen deploy website Novaone len hosting/VPS/domain HTTPS, sau do build APK tro toi domain do.

## 11. Build APK Cho Khach Test Lau Dai

Quy trinh nen dung:

1. Deploy website Novaone len VPS/hosting.
2. Gan domain that, vi du:

```text
https://app.novaone.vn
```

3. Sua `capacitor.config.json`:

```json
"server": {
  "url": "https://app.novaone.vn",
  "cleartext": false
}
```

4. Dong bo va build:

```powershell
npm run mobile:sync
npm run apk:debug
```

5. Gui file:

```text
android\app\build\outputs\apk\debug\app-debug.apk
```

## 12. Lenh Nhanh

Chay web local:

```powershell
cd C:\laragon\www\Novaone
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' -S localhost:8000 router.php
```

Bat tunnel:

```powershell
cloudflared tunnel --url http://localhost:8000
```

Build APK:

```powershell
npm run apk:debug
```

Duong dan APK:

```text
android\app\build\outputs\apk\debug\app-debug.apk
```
