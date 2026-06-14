<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Database;

try {
    $db = Database::getConnection();
    
    // Create booking_agreements table
    $db->exec("
        CREATE TABLE IF NOT EXISTS booking_agreements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            agreement_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created table booking_agreements (if not exists).\n";
    
    // Alter car_booking table: add cancel_reason
    $stmt = $db->query("SHOW COLUMNS FROM car_booking LIKE 'cancel_reason'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE car_booking ADD COLUMN cancel_reason TEXT NULL AFTER status;");
        echo "Added cancel_reason column to car_booking table.\n";
    } else {
        echo "cancel_reason column already exists.\n";
    }
    
    // Modify status column default
    $db->exec("ALTER TABLE car_booking MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending';");
    echo "Modified status column default in car_booking table to 'Pending'.\n";
    
    // Seed initial agreements if table is empty
    $count = $db->query("SELECT COUNT(*) FROM booking_agreements")->fetchColumn();
    if ($count == 0) {
        $stmtSeed = $db->prepare("INSERT INTO booking_agreements (agreement_text) VALUES (:text)");
        $stmtSeed->execute(['text' => 'ผู้ขอใช้รถจะต้องดูแลความสะอาดและรักษาทรัพย์สินของทางราชการตลอดการเดินทาง']);
        $stmtSeed->execute(['text' => 'ผู้ขอใช้รถจะต้องส่งคืนกุญแจรถพร้อมบันทึกเลขไมล์และแนบใบเสร็จน้ำมันทันทีหลังเสร็จสิ้นภารกิจ']);
        $stmtSeed->execute(['text' => 'หากเกิดอุบัติเหตุหรือรถยนต์ขัดข้องในระหว่างเดินทาง ต้องรายงานผู้บริหารและประสานงานฝ่ายกองกลางทันที']);
        echo "Seeded default booking agreements.\n";
    } else {
        echo "Agreements already seeded.\n";
    }
    
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
