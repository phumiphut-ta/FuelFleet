-- ============================================================
-- Migration: Master Data Hierarchy
-- กอง → แผนก → ตำแหน่ง, พนักงานบางคนไม่มีกอง/แผนก
-- ============================================================

-- 1. เพิ่ม division_id ใน department
ALTER TABLE department ADD COLUMN division_id INT NULL AFTER name;
ALTER TABLE department ADD CONSTRAINT fk_dept_division
    FOREIGN KEY (division_id) REFERENCES division (id) ON UPDATE CASCADE ON DELETE SET NULL;

-- ลบ UNIQUE constraint เดิมบน name (เพราะชื่อแผนกเดียวกันอาจอยู่หลายกองได้)
ALTER TABLE department DROP INDEX name;

-- 2. เพิ่ม department_id ใน position
ALTER TABLE position ADD COLUMN department_id INT NULL AFTER name;
ALTER TABLE position ADD CONSTRAINT fk_pos_department
    FOREIGN KEY (department_id) REFERENCES department (id) ON UPDATE CASCADE ON DELETE SET NULL;

-- ลบ UNIQUE constraint เดิมบน name ใน position ด้วย
ALTER TABLE position DROP INDEX name;

-- 3. ทำให้ employee.division_id และ employee.department_id เป็น NULL ได้
ALTER TABLE employee MODIFY COLUMN division_id INT NULL;
ALTER TABLE employee MODIFY COLUMN department_id INT NULL;

-- 4. ทำเช่นกันใน employee_assignment
ALTER TABLE employee_assignment MODIFY COLUMN division_id INT NULL;
ALTER TABLE employee_assignment MODIFY COLUMN department_id INT NULL;
