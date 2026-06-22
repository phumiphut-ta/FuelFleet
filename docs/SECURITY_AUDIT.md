# รายงานผลการตรวจสอบความปลอดภัยของระบบ (Security Audit Report - FuelFleet)

จากการตรวจสอบโครงสร้างและโค้ดของแอปพลิเคชัน **FuelFleet** ทั้งในส่วนระบบหลังบ้าน (Admin) และหน้าจอบริการสาธารณะ (Public/Liff) เราได้สรุปผลการประเมินความปลอดภัยตามมาตรฐาน **OWASP Top 10** พร้อมข้อเสนอแนะในการปรับปรุงแก้ไขดังนี้ครับ:

---

## 1. ตารางสรุปภาพรวมสถานะความปลอดภัย (Security Status Summary)

| หัวข้อความปลอดภัย (OWASP) | สถานะปัจจุบัน | รายละเอียดและการประเมินความเสี่ยง | ความเร่งด่วน |
| :--- | :---: | :--- | :---: |
| **SQL Injection (SQLi)** | 🟢 ปลอดภัยสูง | ระบบส่วนใหญ่ใช้ **PDO Prepared Statements** ในการเข้าถึงฐานข้อมูล ทำให้ไม่เกิด SQL Injection ยกเว้นบางฟิลด์ที่มีการส่งค่าโดยตรง แต่มีการ Cast ชนิดข้อมูลเป็น `(int)` ล่วงหน้าแล้ว | ต่ำ (ปรับปรุงตาม Best Practice) |
| **Cross-Site Scripting (XSS)** | 🟢 ปลอดภัย | ในหน้า View มีการใช้ `htmlspecialchars()` ครอบเพื่อหลีกเลี่ยงการเรนเดอร์แท็กสคริปต์ และในปฏิทินใช้ AlpineJS `x-text` ที่แปลงเป็น `textContent` โดยอัตโนมัติ | ปลอดภัย |
| **Broken Authentication** | 🟢 ปลอดภัย | รหัสผ่านผู้ดูแลระบบจัดเก็บในรูปแบบแฮช **Bcrypt (Cost: 12)** ซึ่งมีความแข็งแกร่งสูง มีระบบ AuthMiddleware ตรวจสอบเซสชันในทุก Admin Controller | ปลอดภัย |
| **Cross-Site Request Forgery (CSRF)** | 🔴 มีช่องโหว่ | ทุกฟอร์มที่เปลี่ยนสถานะข้อมูล (POST/PUT/DELETE) ทั้งฝั่งแอดมินและฝั่งสาธารณะ **ไม่มีการตรวจสอบ CSRF Token** | **สูง (ควรแก้ไข)** |
| **Session Cookie Hardening** | 🟡 ปานกลาง | มีการใช้งาน `session_start()` ทันทีโดยไม่ได้กำหนดคุณสมบัติความปลอดภัยของ Cookie เซสชัน (เช่น HttpOnly, Secure, SameSite) | **ปานกลาง (ควรแก้ไข)** |
| **Unrestricted File Upload** | 🟢 ปลอดภัยสูง | การอัปโหลดใบเสร็จมีระบบตรวจสอบนามสกุลไฟล์แบบ Whitelist (`jpg`, `jpeg`, `png`, `webp`, `pdf`) และทำการสุ่มชื่อไฟล์เพื่อป้องกันการโจมตี | ปลอดภัย |

---

## 2. รายละเอียดข้อบกพร่องและแนวทางแก้ไข (Vulnerabilities & Remediations)

### 🔴 2.1 ขาดการป้องกัน CSRF (Cross-Site Request Forgery)
> [!CAUTION]
> **ความเสี่ยงสูง**
> หากผู้ดูแลระบบล็อกอินค้างไว้ แล้วเผลอกดลิงก์ที่เป็นอันตรายจากภายนอก ผู้โจมตีสามารถเขียนสคริปต์ส่งคำขอ POST ปลอมแปลงมายังระบบเพื่อทำการ อนุมัติการจอง, ยกเลิกใบเสร็จน้ำมัน, หรือลบข้อมูลผู้ใช้งานได้ทันที เนื่องจากเซสชันคุกกี้จะถูกแนบส่งไปพร้อมกับคำขอโดยอัตโนมัติ

* **จุดที่พบ**: ทุก Route ที่เป็น POST/DELETE ใน [index.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/public/index.php) เช่น:
  * `/admin/bookings/approve/{id}`
  * `/admin/receipts/cancel/{id}`
  * `/booking/cancel`
* **แนวทางแก้ไขแนะนำ**:
  1. สร้างคลาสสร้างและตรวจสอบ Token ตัวอย่าง:
     ```php
     namespace App\Core;
     class Csrf {
         public static function generateToken(): string {
             if (empty($_SESSION['csrf_token'])) {
                 $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
             }
             return $_SESSION['csrf_token'];
         }
         public static function validateToken(?string $token): bool {
             return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
         }
     }
     ```
  2. แทรกลูกเล่นฟิลด์อินพุตซ่อนในฟอร์ม HTML:
     ```html
     <input type="hidden" name="csrf_token" value="<?= \App\Core\Csrf::generateToken() ?>">
     ```
  3. ตรวจสอบในจุดรับ POST ของคอนโทรลเลอร์หรือ Middleware ก่อนเริ่มงานบันทึกข้อมูล

---

### 🟡 2.2 โครงสร้าง Session Cookie ยังไม่รัดกุม (Session Cookie Security flags)
> [!WARNING]
> **ความเสี่ยงปานกลาง**
> แอปพลิเคชันทำการเรียกใช้งาน `session_start()` ทันทีโดยไม่มีการตั้งค่าพารามิเตอร์คุกกี้ที่ปลอดภัย ทำให้บราวเซอร์ยอมปล่อยเซสชันคุกกี้ผ่าน HTTP ปกติ (ไม่มี TLS) และยอมให้สคริปต์ใน JavaScript ภายนอกเข้าถึงคุกกี้เซสชันได้ (เสี่ยงต่อ XSS Session Hijacking)

* **จุดที่พบ**: จุดเริ่มต้นเซสชันใน [public/index.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/public/index.php#L3-L5) และ [AuthMiddleware.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/src/Core/AuthMiddleware.php)
* **แนวทางแก้ไขแนะนำ**:
  ตั้งค่าพารามิเตอร์ก่อนเริ่มรันเซสชันใน `public/index.php` เพื่อบังคับคุกกี้ให้มีความปลอดภัยสูง:
  ```diff
  -if (session_status() === PHP_SESSION_NONE) {
  -    session_start();
  -}
  +if (session_status() === PHP_SESSION_NONE) {
  +    session_set_cookie_params([
  +        'lifetime' => 0,
  +        'path' => '/',
  +        'domain' => '',
  +        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  +        'httponly' => true,
  +        'samesite' => 'Lax'
  +    ]);
  +    session_start();
  +}
  ```

---

### 🟡 2.3 การเขียนคิวรีต่อตัวแปรโดยตรง (Direct Parameter Concatenation in SQL)
> [!NOTE]
> **ความเสี่ยงต่ำ (แต่ผิดแนวทางที่ปลอดภัยที่สุด)**
> ตรวจพบคำสั่ง SQL คิวรีแบบสืบค้นตรงบางจุดที่ใช้การเขียนตัวแปรแทรกกลางคำสั่งแทนที่จะเป็น Placeholder บายพาส แม้ว่าก่อนหน้านี้ตัวแปร `$carId` และ `$employeeId` จะถูก Cast ให้เป็น `(int)` แล้วก็ตาม แต่ในทางปฏิบัติควรเขียนเป็น Prepared Statements ทั้งหมดเพื่อป้องกันความผิดพลาดในอนาคต

* **จุดที่พบ**:
  * [ReceiptController.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/src/Controllers/Admin/ReceiptController.php#L186): `SELECT * FROM car_detail WHERE id = {$carId}`
  * [ReportController.php](file:///Users/phumiphut/.gemini/antigravity/playground/FuelFleet/src/Controllers/Admin/ReportController.php#L540): `SELECT * FROM car_detail WHERE id = {$carId}`
* **แนวทางแก้ไขแนะนำ**:
  เปลี่ยนคิวรีแบบ Direct ไปเป็นแบบเตรียมคำสั่ง (Prepared Queries) เช่น:
  ```diff
  -$car = $db->query("SELECT * FROM car_detail WHERE id = {$carId}")->fetch();
  +$stmt = $db->prepare("SELECT * FROM car_detail WHERE id = :id");
  +$stmt->execute(['id' => $carId]);
  +$car = $stmt->fetch();
  ```

---

### 🟢 2.4 ตรวจสอบระบบจำกัดสิทธิ์ของอัปโหลดไฟล์ (File Upload Verification Check)
ระบบอัปโหลดเอกสารใบเสร็จน้ำมันทำได้อย่างรัดกุมมาก:
* มีการกรองนามสกุลแบบ Whitelist เฉพาะ `jpg, jpeg, png, webp, pdf`
* สุ่มชื่อไฟล์ใหม่ด้วย `time() . '_' . rand(1000, 9999)` เสมอ ทำให้ผู้ประสงค์ร้ายไม่สามารถอัปโหลดสคริปต์ `.php` ขึ้นไปรันบนระบบได้
* *ข้อเสนอแนะเพิ่มเติม*: หากต้องการความปลอดภัยขั้นสูงสุด สามารถยกระดับความปลอดภัยโดยใช้ฟังก์ชันตรวจสอบประเภทของไฟล์จริง (MIME Type) แทนการอ้างอิงจากนามสกุลไฟล์:
  ```php
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $fileTmpPath);
  $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
  if (!in_array($mimeType, $allowedMimes)) {
      throw new Exception("รูปแบบไฟล์จริงไม่ถูกต้องตามที่กำหนด");
  }
  ```
