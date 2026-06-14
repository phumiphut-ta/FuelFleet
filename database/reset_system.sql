-- ============================================================
-- SQL Script: ล้างข้อมูลเพื่อเริ่มต้นระบบใหม่ (System Reset Script)
-- ระบบ: FuelFleet™
-- คำเตือน: สคริปต์นี้จะลบข้อมูลธุรกรรมและการจองทั้งหมดเพื่อเริ่มต้นระบบใหม่
-- ============================================================

-- ปิดการตรวจสอบ Foreign Key ชั่วคราวเพื่อทำความสะอาดทุกตารางได้อย่างราบรื่น
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. ล้างข้อมูลส่วนของธุรกรรมและบันทึกการทำงาน (Transaction & Log Tables)
-- ------------------------------------------------------------
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE report_print_log;
TRUNCATE TABLE booking_cancel_log;
TRUNCATE TABLE receipt_attachment;
TRUNCATE TABLE gas_receipt;
TRUNCATE TABLE car_quota_history;
TRUNCATE TABLE car_suspension;
TRUNCATE TABLE car_booking_provinces;
TRUNCATE TABLE car_booking;

-- ------------------------------------------------------------
-- 2. ล้างข้อมูลประวัติและข้อมูลหลัก (Master Data & Profiles)
-- ตารางเหล่านี้จะถูกล้างเพื่อให้คุณสามารถระบุข้อมูลจริงขององค์กรได้เอง
-- ------------------------------------------------------------
TRUNCATE TABLE employee_assignment;
TRUNCATE TABLE employee;
TRUNCATE TABLE position;
TRUNCATE TABLE department;
TRUNCATE TABLE division;
TRUNCATE TABLE car_detail;

-- ------------------------------------------------------------
-- 3. จัดการบัญชีผู้ดูแลระบบ (Admin User)
-- ทำการล้างตารางผู้ดูแลระบบ และสร้างบัญชีผู้ดูแลระบบสูงสุด (Super Admin) เผื่อไว้ให้เข้าสู่ระบบได้
-- ------------------------------------------------------------
TRUNCATE TABLE admin_users;

-- รหัสผ่านเริ่มต้นคือ 'admin123'
-- Hash สำหรับ 'admin123' คือ: $2y$12$UInh/74SgJS2uedPqao.d.8NaxHstBcFUgvWRD700yQxvzy/QEXrW
INSERT INTO admin_users (username, password, full_name, role) VALUES 
('admin', '$2y$12$UInh/74SgJS2uedPqao.d.8NaxHstBcFUgvWRD700yQxvzy/QEXrW', 'ผู้ดูแลระบบสูงสุด', 'admin');

-- ------------------------------------------------------------
-- 4. ตั้งค่าระบบพื้นฐาน (System Settings)
-- ป้องกันระบบขัดข้องโดยการล้างและใส่ค่าตั้งค่าพื้นฐานของระบบที่จำเป็นไว้
-- ------------------------------------------------------------
TRUNCATE TABLE system_settings;

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('pdf_report_footer', 'รายงานนี้สร้างและพิมพ์โดยระบบควบคุมโควต้าน้ำมันยานพาหนะอัตโนมัติ <strong>FuelFleet™</strong><br>พิมพ์ใบเสร็จและภาพแนบย้อนหลังถูกต้องตามข้อบังคับระเบียบราชการองค์กร', 'ข้อความท้ายกระดาษของรายงาน PDF ทุกฉบับ'),
('footer_copyright', '© 2026 FuelFleet™. ระบบบริหารรถส่วนราชการ. สงวนลิขสิทธิ์ทั้งหมด.', 'ข้อความลิขสิทธิ์ที่แสดงด้านล่างเว็บไซต์');

-- เปิดการตรวจสอบ Foreign Key กลับมาทำงานปกติ
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- สำเร็จ! ระบบได้รับการรีเซ็ตเพื่อเริ่มต้นใช้งานจริงแล้ว
-- บัญชีเข้าใช้งานระบบสูงสุด:
-- Username: admin
-- Password: admin123
-- ============================================================
