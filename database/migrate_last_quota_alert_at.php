<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Database;

try {
    $db = Database::getConnection();
    
    // Add last_quota_alert_at column to car_detail table if not exists
    $stmt = $db->query("SHOW COLUMNS FROM car_detail LIKE 'last_quota_alert_at'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE car_detail ADD COLUMN last_quota_alert_at DATETIME NULL DEFAULT NULL AFTER remaining_low_threshold;");
        echo "Added last_quota_alert_at column to car_detail table.\n";
    } else {
        echo "last_quota_alert_at column already exists.\n";
    }
    
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
