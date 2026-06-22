# คู่มือการติดตั้งใช้งานบน Server จริง (Production Deployment Guide)

คู่มือนี้จะอธิบายขั้นตอนการนำระบบ **FuelFleet** ไปติดตั้งใช้งานบนโปรดักชันเซิร์ฟเวอร์ (Production Server) เช่น VPS, Web Hosting, Linux Cloud (Ubuntu/Debian) หรือระบบภายในองค์กร

---

## 1. ข้อกำหนดของระบบ (System Requirements)

- **OS**: Linux (แนะนำ Ubuntu 22.04 LTS หรือใหม่กว่า), macOS หรือ Windows Server
- **Web Server**: Apache (พร้อมเปิดใช้งาน `mod_rewrite`) หรือ Nginx (แนะนำ)
- **PHP**: เวอร์ชัน `8.3` หรือใหม่กว่า
  - **PHP Extensions**: `pdo`, `pdo_mysql`, `gd`, `zip`, `xml`, `mbstring`, `openssl`
- **Database**: MySQL `5.7+` หรือ MariaDB `10.3+`
- **Dependency Manager**: Composer

---

## 2. ขั้นตอนการติดตั้งแบบทีละสเตป (Step-by-Step Installation)

### ขั้นตอนที่ 1: อัปโหลดโค้ดขึ้นเซิร์ฟเวอร์
นำไฟล์ของโปรเจกต์ทั้งหมดขึ้นเซิร์ฟเวอร์ของคุณ โดยใช้โปรแกรม FTP/SFTP หรือทำการ Clone ผ่าน Git ไปยังไดเรกทอรีเว็บ เช่น `/var/www/fuelfleet`

### ขั้นตอนที่ 2: ติดตั้ง Dependencies (Composer)
เปิด Terminal บนเซิร์ฟเวอร์ เข้าไปยังไดเรกทอรีของโปรเจกต์ และรันคำสั่งด้านล่างเพื่อติดตั้งแพ็กเกจที่จำเป็นสำหรับระบบจริงโดยไม่ติดตั้งตัวทดสอบ (`--no-dev`):
```bash
composer install --no-dev --optimize-autoloader
```
*หมายเหตุ: สวิตช์ `--optimize-autoloader` จะช่วยจัดการแผนผังคลาสให้โปรแกรมโหลดเร็วขึ้นในสิ่งแวดล้อมจริง*

### ขั้นตอนที่ 3: ตั้งค่าฐานข้อมูล (Database Setup)
1. เข้าไปที่แผงควบคุมฐานข้อมูลของคุณ (เช่น phpMyAdmin) หรือเข้าผ่าน CLI
2. สร้างฐานข้อมูลใหม่ เช่น ชื่อฐานข้อมูล `fuel_fleet` (เลือก Collation เป็น `utf8mb4_unicode_ci`)
3. นำเข้าข้อมูลเริ่มต้นโดยการ Import ไฟล์ [database/schema.sql](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/database/schema.sql) เพื่อสร้างตารางและสร้างบัญชีแอดมินจำลอง

### ขั้นตอนที่ 4: ตั้งค่าเชื่อมต่อระบบฐานข้อมูล
ทำการแก้ไขไฟล์เชื่อมต่อฐานข้อมูล [config/database.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/config/database.php) ให้ตรงกับรหัสผ่านจริงของเซิร์ฟเวอร์ของคุณ:
```php
<?php
return [
    'host' => '127.0.0.1', // ไอพีฐานข้อมูล เช่น 'localhost' หรือ IP ของ Cloud Database
    'port' => '3306',
    'database' => 'fuel_fleet', // ชื่อฐานข้อมูลจริง
    'username' => 'your_db_user', // ชื่อผู้ใช้งาน MySQL
    'password' => 'your_db_secure_password', // รหัสผ่านฐานข้อมูลจริง
    'charset' => 'utf8mb4'
];
```

### ขั้นตอนที่ 5: ตั้งค่าสิทธิ์โฟลเดอร์สำหรับอัปโหลดใบเสร็จ (Directory Permissions)
ระบบจำเป็นต้องได้รับสิทธิ์เขียนไฟล์ในโฟลเดอร์อัปโหลดภาพใบเสร็จน้ำมัน เพื่อความปลอดภัยแนะนำให้ตั้งค่าสิทธิ์โฟลเดอร์บน Linux ดังนี้:
```bash
# กำหนดเจ้าของโฟลเดอร์ให้กับ Web Server User (เช่น www-data ใน Ubuntu)
sudo chown -R www-data:www-data public/uploads

# กำหนดสิทธิ์ให้สามารถเขียนและอ่านไฟล์ได้
sudo chmod -R 775 public/uploads
```

---

## 3. การกำหนดค่าเว็บเซิร์ฟเวอร์ (Web Server Configuration)

> [!IMPORTANT]  
> ไดเรกทอรีหลักของเว็บไซต์ (Document Root) ของคุณ **ต้องชี้ตรงไปยังโฟลเดอร์ `/public` เท่านั้น** ห้ามชี้ไปที่โฟลเดอร์ราก (Root Directory) ของโปรเจกต์ ทั้งนี้เพื่อความปลอดภัยของซอร์สโค้ดและเพื่อให้ระบบ Routing ทำงานได้อย่างถูกต้อง

### กรณีใช้ Apache (เช่น cPanel / DirectAdmin / Ubuntu + Apache)
- เราได้สร้างไฟล์ [public/.htaccess](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/public/.htaccess) ไว้ในระบบให้เรียบร้อยแล้ว 
- คุณเพียงแค่ชี้ Document Root ในแผงควบคุมโฮสติ้งของคุณไปยังโฟลเดอร์ `/public`
- ตรวจสอบให้มั่นใจว่า Apache ของคุณเปิดใช้งาน `mod_rewrite` แล้ว ระบบจะจัดการเส้นทาง URL (Clean URLs) ทั้งหมดให้อัตโนมัติ

### กรณีใช้ Nginx (แนะนำสำหรับความเร็วและพอร์ตที่เสถียร)
หากคุณใช้ VPS และติดตั้ง Nginx สามารถนำบล็อกการตั้งค่านี้ไปปรับใช้ในไฟล์ Virtual Host ของ Nginx (เช่น `/etc/nginx/sites-available/default`):

```nginx
server {
    listen 80;
    server_name fuelfleet.yourdomain.com; # ชื่อโดเมนของคุณ

    # ชี้ตรงไปยังโฟลเดอร์ public
    root /var/www/fuelfleet/public; 
    index index.php index.html;

    charset utf-8;

    # จัดการการดึงหน้าเว็บ
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ปฏิเสธการเข้าถึงโฟลเดอร์ที่อันตราย
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # ตั้งค่ารัน PHP-FPM (ปรับเวอร์ชัน php-fpm ให้ตรงกับบนเครื่องเซิร์ฟเวอร์)
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # ตั้งค่าแคชไฟล์ Static เพื่อความรวดเร็วในการโหลดหน้าเว็บ
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|otf)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

### กรณีใช้ IIS (Internet Information Services บน Windows Server)
- เราได้ทำไฟล์สิทธิ์และการจัดการ URL Rewrite สำหรับ IIS ไว้ใน [public/web.config](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/public/web.config) เรียบร้อยแล้ว
- **ขั้นตอนการชี้จุดเริ่มต้นการทำงาน (Document Root) ใน IIS Manager:**
  1. เปิดโปรแกรม **IIS Manager** (พิมพ์คำสั่ง `inetmgr` ในช่อง Run ของ Windows)
  2. ในพาเนลซ้ายมือ ให้ขยายเมนู **Sites**
     - หากเป็นการเพิ่มเว็บใหม่: คลิกขวาที่ **Sites** -> เลือก **Add Website...**
     - หากแก้ไขเว็บเดิมที่มีอยู่: คลิกเลือกเว็บเดิม (เช่น Default Web Site) -> ทางขวามือเลือก **Basic Settings...**
  3. ในช่อง **Physical Path** ให้เลือกไปยังโฟลเดอร์ `/public` ของโปรเจกต์ (เช่น `C:\inetpub\wwwroot\FuelFleet\public`)
  4. คลิก **OK** เพื่อบันทึกค่า
- **ข้อแนะนำเพิ่มเติมสำหรับ IIS:**
  - ตรวจสอบให้มั่นใจว่าเซิร์ฟเวอร์ติดตั้งเครื่องมือ **URL Rewrite Extension** ของ IIS เรียบร้อยแล้ว (หากไม่ได้ติดตั้ง จะทำให้เกิด error HTTP 500 เนื่องจากระบบไม่เข้าใจแท็ก `<rewrite>` ในไฟล์ `web.config`)
  - กำหนดสิทธิ์โฟลเดอร์สำหรับอัปโหลด: ไปที่ไฟล์ Explorer คลิกขวาที่โฟลเดอร์ `public/uploads` -> เลือก **Properties** -> ไปที่แท็บ **Security** -> คลิก **Edit** -> เพิ่มสิทธิ์ Write ให้แก่ผู้ใช้ `IIS_IUSRS` (หรือ `IUSR`) เพื่อรองรับการอัปโหลดไฟล์ใบเสร็จน้ำมัน

---

## 4. ข้อแนะนำด้านความปลอดภัยสำหรับการใช้งานจริง (Production Security Tips)

1. **เปลี่ยนรหัสผ่านเริ่มต้น**: หลังจากล็อกอินผ่าน `admin` / `admin123` ในครั้งแรก ให้รีบเข้าไปที่ส่วนจัดการข้อมูลหรือตั้งค่าเพื่อเปลี่ยนรหัสผ่านทันที
2. **เปิดใช้งาน HTTPS (SSL)**: แนะนำให้ติดตั้งใบรับรองความปลอดภัย SSL (เช่น ผ่านการใช้บริการฟรีของ Let's Encrypt / Certbot) เพื่อเข้ารหัสรหัสผ่านที่ส่งเข้าระบบ
3. **ปิดการแสดง Errors ของ PHP**: ตรวจสอบว่าในไฟล์ `php.ini` บนเซิร์ฟเวอร์จริงตั้งค่า `display_errors = Off` และ `log_errors = On` เพื่อไม่ให้แฮกเกอร์มองเห็นรายละเอียดสถาปัตยกรรมไดเรกทอรีเมื่อเกิดข้อผิดพลาดขึ้น
