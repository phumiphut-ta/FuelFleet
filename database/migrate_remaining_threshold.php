<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Database;

try {
    $db = Database::getConnection();
    
    // Alter car_detail table: add remaining_low_threshold
    $stmt = $db->query("SHOW COLUMNS FROM car_detail LIKE 'remaining_low_threshold'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE car_detail ADD COLUMN remaining_low_threshold DECIMAL(10,2) DEFAULT 20.00 AFTER note;");
        echo "Added remaining_low_threshold column to car_detail table.\n";
    } else {
        echo "remaining_low_threshold column already exists.\n";
    }
    
    // Seed default line_announcement_template in system_settings if not exists
    $stmtSettings = $db->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = 'line_announcement_template'");
    $stmtSettings->execute();
    $count = $stmtSettings->fetchColumn();
    if ($count == 0) {
        $defaultTemplate = "📢 อัปเดตโควต้าน้ำมันรถยนต์ส่วนกลาง (ประจำวันที่ {date})\n\n{vehicle_list}\n🛑 โปรดทราบ:\nหากมีการใช้งานน้ำมันเกินโควต้าที่กำหนด จะไม่สามารถเบิกใบเสร็จค่าน้ำมันส่วนที่เกินได้\nขอให้ทุกท่านระมัดระวังและวางแผนการเดินทางอย่างรอบคอบ";
        $stmtInsert = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (:key, :val, :desc)");
        $stmtInsert->execute([
            'key' => 'line_announcement_template',
            'val' => $defaultTemplate,
            'desc' => 'เทมเพลตสำหรับข้อความประกาศอัปเดตโควต้าน้ำมันทาง LINE'
        ]);
        echo "Seeded default line_announcement_template in system_settings.\n";
    } else {
        echo "line_announcement_template already exists in system_settings.\n";
    }
    
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
