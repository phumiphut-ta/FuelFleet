<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Database;

try {
    $db = Database::getConnection();
    
    // Add sort_order column to booking_agreements
    $stmt = $db->query("SHOW COLUMNS FROM booking_agreements LIKE 'sort_order'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE booking_agreements ADD COLUMN sort_order INT DEFAULT 0 AFTER agreement_text;");
        echo "Added sort_order column to booking_agreements table.\n";
    } else {
        echo "sort_order column already exists.\n";
    }
    
    // Initialize sort_order sequentially based on ID
    $agreements = $db->query("SELECT id FROM booking_agreements ORDER BY id ASC")->fetchAll();
    $stmtUpdate = $db->prepare("UPDATE booking_agreements SET sort_order = :so WHERE id = :id");
    foreach ($agreements as $index => $a) {
        $stmtUpdate->execute([
            'so' => $index + 1,
            'id' => $a['id']
        ]);
    }
    echo "Initialized sort_order values sequentially.\n";
    
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
