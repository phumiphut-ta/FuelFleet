-- Drop tables if they exist to allow clean reinstall
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS report_print_log;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS booking_cancel_log;
DROP TABLE IF EXISTS receipt_attachment;
DROP TABLE IF EXISTS gas_receipt;
DROP TABLE IF EXISTS car_quota_history;
DROP TABLE IF EXISTS car_suspension;
DROP TABLE IF EXISTS car_booking_provinces;
DROP TABLE IF EXISTS car_booking;
DROP TABLE IF EXISTS car_detail;
DROP TABLE IF EXISTS employee_assignment;
DROP TABLE IF EXISTS employee;
DROP TABLE IF EXISTS position;
DROP TABLE IF EXISTS department;
DROP TABLE IF EXISTS division;
DROP TABLE IF EXISTS admin_users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. admin_users
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. division
CREATE TABLE division (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. department
CREATE TABLE department (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES division (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. position
CREATE TABLE position (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NULL,
    department_id INT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES division (id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES department (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. employee
CREATE TABLE employee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    division_id INT NULL,
    department_id INT NULL,
    position_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Active', -- Active, Transferred, Retired, Resigned, Suspended
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES division (id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES department (id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES position (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. employee_assignment
CREATE TABLE employee_assignment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    division_id INT NULL,
    department_id INT NULL,
    position_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE,
    FOREIGN KEY (division_id) REFERENCES division (id) ON UPDATE CASCADE,
    FOREIGN KEY (department_id) REFERENCES department (id) ON UPDATE CASCADE,
    FOREIGN KEY (position_id) REFERENCES position (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. car_detail
CREATE TABLE car_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_plate VARCHAR(50) NOT NULL UNIQUE,
    fuel_type VARCHAR(50) NOT NULL, -- Diesel, Gasohol 95, Gasohol 91, E20, E85, Benzene
    status VARCHAR(50) DEFAULT 'Active', -- Active, Suspended
    note TEXT NULL,
    remaining_low_threshold DECIMAL(10,2) DEFAULT 20.00,
    last_quota_alert_at DATETIME NULL DEFAULT NULL,
    color VARCHAR(50) DEFAULT '#4f46e5',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. car_booking
CREATE TABLE car_booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    car_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    purpose TEXT NOT NULL,
    cancellation_password VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Confirmed, Cancelled
    cancel_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee (id) ON UPDATE CASCADE,
    FOREIGN KEY (car_id) REFERENCES car_detail (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. car_booking_provinces
CREATE TABLE car_booking_provinces (
    booking_id INT NOT NULL,
    province_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (booking_id, province_name),
    FOREIGN KEY (booking_id) REFERENCES car_booking (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. car_suspension
CREATE TABLE car_suspension (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    created_by INT NOT NULL, -- admin_users
    status VARCHAR(50) DEFAULT 'Active', -- Active, Cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES car_detail (id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. car_quota_history
CREATE TABLE car_quota_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    monthly_quota DECIMAL(10,2) NOT NULL, -- In Liters
    effective_month DATE NOT NULL, -- e.g. 2026-05-01
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES car_detail (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. gas_receipt
CREATE TABLE gas_receipt (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(100) NOT NULL UNIQUE,
    receipt_date DATE NOT NULL,
    record_date DATE NOT NULL,
    employee_id INT NOT NULL,
    car_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    liters DECIMAL(10,2) NOT NULL,
    price_per_liter DECIMAL(10,2) NOT NULL, -- auto calculated
    mileage INT NULL,
    status VARCHAR(50) DEFAULT 'Pending verification', -- Pending verification, Verified, Cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee (id) ON UPDATE CASCADE,
    FOREIGN KEY (car_id) REFERENCES car_detail (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. receipt_attachment
CREATE TABLE receipt_attachment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receipt_id) REFERENCES gas_receipt (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. booking_cancel_log
CREATE TABLE booking_cancel_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES car_booking (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. audit_logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL, -- Create, Update, Deactivate, Cancel booking, Generate report
    table_name VARCHAR(100) NULL,
    record_id INT NULL,
    previous_value LONGTEXT NULL,
    new_value LONGTEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. report_print_log
CREATE TABLE report_print_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    printed_by VARCHAR(255) NOT NULL,
    filter_criteria TEXT NULL,
    print_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. system_settings
CREATE TABLE system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeding mock data
-- Default admin: admin / admin123
-- Hash for 'admin123' is: $2y$12$UInh/74SgJS2uedPqao.d.8NaxHstBcFUgvWRD700yQxvzy/QEXrW
INSERT INTO admin_users (username, password, full_name, role) VALUES 
('admin', '$2y$12$UInh/74SgJS2uedPqao.d.8NaxHstBcFUgvWRD700yQxvzy/QEXrW', 'ผู้ดูแลระบบสูงสุด', 'admin');

-- Seed master data
INSERT INTO division (name) VALUES 
('สำนักปลัด'),
('กองกลาง'),
('กองคลัง'),
('กองช่าง');

INSERT INTO department (division_id, name) VALUES 
('1', 'ฝ่ายบริหารงานทั่วไป'),
('2', 'ฝ่ายการเงินและบัญชี'),
('3', 'ฝ่ายโยธา'),
('4', 'ฝ่ายยานพาหนะ');

INSERT INTO position (department_id, name) VALUES 
('1', 'ผู้อำนวยการ'),
('1', 'หัวหน้าฝ่าย'),
('1', 'นักจัดการงานทั่วไป'),
('4', 'พนักงานขับรถ');

-- Seed employees
INSERT INTO employee (employee_code, full_name, division_id, department_id, position_id, status) VALUES 
('EMP001', 'สมชาย ใจดี', 1, 1, 1, 'Active'),
('EMP002', 'สมศรี รักสงบ', 2, 2, 2, 'Active'),
('EMP003', 'สมศักดิ์ ขยันยิ่ง', 3, 3, 3, 'Active'),
('EMP004', 'สมพร ขับดี', 4, 4, 4, 'Active');

-- Add initial employee assignments
INSERT INTO employee_assignment (employee_id, division_id, department_id, position_id, start_date) VALUES 
(1, 1, 1, 1, '2026-01-01'),
(2, 2, 2, 2, '2026-01-01'),
(3, 3, 3, 3, '2026-01-01'),
(4, 4, 4, 4, '2026-01-01');

-- Seed vehicles
INSERT INTO car_detail (license_plate, fuel_type, status, note) VALUES 
('กข-1234', 'Diesel', 'Active', 'รถกระบะขนส่งของกองกลาง'),
('มค-5678', 'Gasohol 95', 'Active', 'รถเก๋งผู้บริหาร สำนักปลัด'),
('สป-9999', 'Gasohol 91', 'Active', 'รถตู้โดยสารส่วนกลาง'),
('ชพ-4321', 'Diesel', 'Suspended', 'รถบรรทุก 6 ล้อ (ปิดซ่อมบำรุงระบบเกียร์)');

-- Seed quotas (for current and previous months)
INSERT INTO car_quota_history (car_id, monthly_quota, effective_month) VALUES 
(1, 300.00, '2026-05-01'),
(2, 200.00, '2026-05-01'),
(3, 400.00, '2026-05-01'),
(4, 500.00, '2026-05-01');

-- Seed system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('pdf_report_footer', 'รายงานนี้สร้างและพิมพ์โดยระบบควบคุมโควต้าน้ำมันยานพาหนะอัตโนมัติ <strong>FuelFleet™</strong><br>พิมพ์ใบเสร็จและภาพแนบย้อนหลังถูกต้องตามข้อบังคับระเบียบราชการองค์กร', 'ข้อความท้ายกระดาษของรายงาน PDF ทุกฉบับ'),
('footer_copyright', '© 2026 FuelFleet™. ระบบบริหารรถส่วนราชการ. สงวนลิขสิทธิ์ทั้งหมด.', 'ข้อความลิขสิทธิ์ที่แสดงด้านล่างเว็บไซต์'),
('line_announcement_template', '📢 อัปเดตโควต้าน้ำมันรถยนต์ส่วนกลาง (ประจำวันที่ {date})\n\n{vehicle_list}\n🛑 โปรดทราบ:\nหากมีการใช้งานน้ำมันเกินโควต้าที่กำหนด จะไม่สามารถเบิกใบเสร็จค่าน้ำมันส่วนที่เกินได้\nขอให้ทุกท่านระมัดระวังและวางแผนการเดินทางอย่างรอบคอบ', 'เทมเพลตสำหรับข้อความประกาศอัปเดตโควต้าน้ำมันทาง LINE');

-- 18. booking_agreements
CREATE TABLE booking_agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agreement_text TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO booking_agreements (agreement_text, sort_order) VALUES 
('ผู้ขอใช้รถจะต้องดูแลความสะอาดและรักษาทรัพย์สินของทางราชการตลอดการเดินทาง', 1),
('ผู้ขอใช้รถจะต้องส่งคืนกุญแจรถพร้อมบันทึกเลขไมล์และแนบใบเสร็จน้ำมันทันทีหลังเสร็จสิ้นภารกิจ', 2),
('หากเกิดอุบัติเหตุหรือรถยนต์ขัดข้องในระหว่างเดินทาง ต้องรายงานผู้บริหารและประสานงานฝ่ายกองกลางทันที', 3);

