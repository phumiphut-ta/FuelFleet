<?php
namespace App\Core;

use PDO;
use Exception;

class DiscordNotifier
{
    // Helper to get Discord configuration from system_settings
    public static function getSettings(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'discord_notification_settings' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val) {
                return json_decode($val, true) ?? [];
            }
        } catch (\Throwable $e) {
            // Ignore database errors during bootstrap
        }
        return [];
    }

    // Helper to send message to Discord webhook
    private static function sendToChannel(string $channelKey, string $topicKey, array $embed): void
    {
        $settings = self::getSettings();
        $channelConfig = $settings['channels'][$channelKey] ?? null;
        if (!$channelConfig) {
            return;
        }

        $webhookUrl = trim($channelConfig['webhook_url'] ?? '');
        if (empty($webhookUrl)) {
            return;
        }

        $topicEnabled = ($channelConfig['topics'][$topicKey] ?? '0') === '1';
        if (!$topicEnabled) {
            return;
        }

        $payload = json_encode([
            'embeds' => [$embed]
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Prevent blocking user execution
        $response = curl_exec($ch);
        if ($response === false) {
            error_log('Discord Webhook Curl Error: ' . curl_error($ch));
        }
        curl_close($ch);
    }

    private static function hexColor(string $hex): int
    {
        return hexdec(str_replace('#', '', $hex));
    }

    // Topic 1: New Booking
    public static function sendNewBooking(int $bookingId): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.*, e.full_name AS employee_name, c.license_plate, c.fuel_type
                FROM car_booking b
                LEFT JOIN employee e ON b.employee_id = e.id
                LEFT JOIN car_detail c ON b.car_id = c.id
                WHERE b.id = :id
            ");
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();
            if (!$booking)
                return;

            $stmtProv = $db->prepare("SELECT province_name FROM car_booking_provinces WHERE booking_id = :id");
            $stmtProv->execute(['id' => $bookingId]);
            $provinces = $stmtProv->fetchAll(PDO::FETCH_COLUMN);
            $provincesStr = implode(', ', $provinces);

            $embed = [
                'title' => '📅 จองรถยนต์สำเร็จ',
                'description' => 'มีคำขอจองรถคันรอการตรวจสอบและอนุมัติในระบบ',
                'color' => self::hexColor('#2ecc71'), // Green
                'fields' => [
                    ['name' => '👤 ผู้ขอจอง', 'value' => $booking['employee_name'], 'inline' => true],
                    ['name' => '🚗 ทะเบียนรถ', 'value' => $booking['license_plate'] . ' (' . $booking['fuel_type'] . ')', 'inline' => true],
                    ['name' => '📅 วันเวลาเดินทาง', 'value' => date('d/m/Y', strtotime($booking['start_time'])) . ' ถึง ' . date('d/m/Y', strtotime($booking['end_time'])), 'inline' => false],
                    ['name' => '📍 จังหวัดปลายทาง', 'value' => $provincesStr ?: 'ไม่ระบุ', 'inline' => false],
                    ['name' => '📝 วัตถุประสงค์', 'value' => $booking['purpose'], 'inline' => false],
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('booking_alerts', 'new_booking', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 2: Booking Cancelled
    public static function sendCancelledBooking(int $bookingId, string $reason, string $cancelledBy = 'ผู้ใช้งาน'): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.*, e.full_name AS employee_name, c.license_plate, c.fuel_type
                FROM car_booking b
                LEFT JOIN employee e ON b.employee_id = e.id
                LEFT JOIN car_detail c ON b.car_id = c.id
                WHERE b.id = :id
            ");
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();
            if (!$booking)
                return;

            $embed = [
                'title' => '🚫 ยกเลิกการจองรถยนต์',
                'color' => self::hexColor('#e67e22'), // Orange
                'fields' => [
                    ['name' => '👤 ผู้ขอจอง', 'value' => $booking['employee_name'], 'inline' => true],
                    ['name' => '🚗 ทะเบียนรถ', 'value' => $booking['license_plate'], 'inline' => true],
                    ['name' => '📅 วันเวลาเดินทางเดิม', 'value' => date('d/m/Y', strtotime($booking['start_time'])) . ' ถึง ' . date('d/m/Y', strtotime($booking['end_time'])), 'inline' => false],
                    ['name' => '❌ ผู้ทำรายการยกเลิก', 'value' => $cancelledBy, 'inline' => true],
                    ['name' => '📝 เหตุผลในการยกเลิก', 'value' => $reason ?: 'ไม่ระบุเหตุผล', 'inline' => false]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('booking_alerts', 'cancel_booking', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 3: Vehicle Suspension
    public static function sendSuspensionCreated(int $suspensionId): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT s.*, c.license_plate, u.full_name AS admin_name
                FROM car_suspension s
                LEFT JOIN car_detail c ON s.car_id = c.id
                LEFT JOIN admin_users u ON s.created_by = u.id
                WHERE s.id = :id
            ");
            $stmt->execute(['id' => $suspensionId]);
            $s = $stmt->fetch();
            if (!$s)
                return;

            $embed = [
                'title' => '🚫 ระงับการใช้งานรถยนต์ชั่วคราว',
                'description' => 'ยานพาหนะถูกระงับการใช้งานชั่วคราว',
                'color' => self::hexColor('#e74c3c'), // Red
                'fields' => [
                    ['name' => '🚗 ทะเบียนรถ', 'value' => $s['license_plate'], 'inline' => true],
                    ['name' => '👤 ผู้สั่งการ', 'value' => $s['admin_name'], 'inline' => true],
                    ['name' => '📅 ช่วงเวลาปิดปรับปรุง', 'value' => date('d/m/Y', strtotime($s['start_date'])) . ' ถึง ' . date('d/m/Y', strtotime($s['end_date'])), 'inline' => false],
                    ['name' => '🛠️ เหตุผลในการระงับใช้', 'value' => $s['reason'], 'inline' => false]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('vehicle_status', 'suspension', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 3 (part 2): Vehicle Suspension Cancelled (Reactivated)
    public static function sendSuspensionCancelled(int $suspensionId): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT s.*, c.license_plate
                FROM car_suspension s
                LEFT JOIN car_detail c ON s.car_id = c.id
                WHERE s.id = :id
            ");
            $stmt->execute(['id' => $suspensionId]);
            $s = $stmt->fetch();
            if (!$s)
                return;

            $embed = [
                'title' => '✅ ปลดล็อกการใช้งานรถยนต์ปกติ',
                'description' => 'ยานพาหนะถูกยกเลิกคำสั่งระงับ และนำกลับเข้าสู่สแตนด์บายการจองปกติ',
                'color' => self::hexColor('#2ecc71'), // Green
                'fields' => [
                    ['name' => '🚗 ทะเบียนรถ', 'value' => $s['license_plate'], 'inline' => true],
                    ['name' => '📝 รายละเอียดเดิม', 'value' => $s['reason'], 'inline' => false]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('vehicle_status', 'suspension', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 4 & 5: Check and Send Quota Alerts
    public static function checkAndSendQuotaAlerts(int $carId, string $yearMonth): void
    {
        try {
            $quotaService = new \App\Services\QuotaService();
            $status = $quotaService->getCarQuotaStatus($carId, $yearMonth);

            $db = Database::getConnection();
            $stmtCar = $db->prepare("SELECT license_plate, COALESCE(remaining_low_threshold, 20.00) AS threshold FROM car_detail WHERE id = :id");
            $stmtCar->execute(['id' => $carId]);
            $car = $stmtCar->fetch();
            if (!$car)
                return;

            $quota = $status['quota_liters'];
            $used = $status['liters_used'];
            $remaining = $quota - $used;
            $threshold = (float) $car['threshold'];

            if ($quota <= 0)
                return;

            $monthStr = date('m/Y', strtotime($yearMonth . '-01'));

            if ($used >= $quota) {
                // Topic 5: Quota empty or over limit
                $exceeded = max(0.0, $used - $quota);
                $embed = [
                    'title' => '🚨 คำเตือนวิกฤต: โควต้าน้ำมันหมด / เกินกำหนด',
                    'description' => 'ยานพาหนะใช้งานเชื้อเพลิงเต็มหรือล้นขีดจำกัดโควต้าของเดือนนี้แล้ว',
                    'color' => self::hexColor('#e74c3c'), // Red
                    'fields' => [
                        ['name' => '🚗 ทะเบียนรถ', 'value' => $car['license_plate'], 'inline' => true],
                        ['name' => '📅 ประจำเดือน', 'value' => $monthStr, 'inline' => true],
                        ['name' => '⛽ โควต้าจำกัด (ลิตร)', 'value' => number_format($quota, 2), 'inline' => true],
                        ['name' => '📈 เติมจริงสะสม (ลิตร)', 'value' => number_format($used, 2), 'inline' => true],
                        ['name' => '⚠️ ปริมาณน้ำมันส่วนที่เกิน (ลิตร)', 'value' => number_format($exceeded, 2), 'inline' => true]
                    ],
                    'timestamp' => date('c')
                ];
                self::sendToChannel('fuel_quotas', 'quota_over', $embed);
            } elseif ($remaining <= $threshold) {
                // Topic 4: Quota running low
                $embed = [
                    'title' => '⛽ แจ้งเตือน: โควต้าน้ำมันใกล้หมด',
                    'description' => 'ปริมาณน้ำมันคงเหลือของยานพาหนะลดลงจนถึงเกณฑ์แจ้งเตือนลิตรคงเหลือต่ำสุด',
                    'color' => self::hexColor('#f1c40f'), // Yellow
                    'fields' => [
                        ['name' => '🚗 ทะเบียนรถ', 'value' => $car['license_plate'], 'inline' => true],
                        ['name' => '📅 ประจำเดือน', 'value' => $monthStr, 'inline' => true],
                        ['name' => '⛽ โควต้าทั้งหมด (ลิตร)', 'value' => number_format($quota, 2), 'inline' => true],
                        ['name' => '📈 เติมสะสมแล้ว (ลิตร)', 'value' => number_format($used, 2), 'inline' => true],
                        ['name' => '📥 คงเหลือที่ใช้ได้ (ลิตร)', 'value' => number_format($remaining, 2) . ' ลิตร (เกณฑ์เตือน: ' . number_format($threshold, 2) . ' ลิตร)', 'inline' => false]
                    ],
                    'timestamp' => date('c')
                ];
                self::sendToChannel('fuel_quotas', 'quota_low', $embed);
            }
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 6: Receipt Uploaded / Awaiting Verification
    public static function sendReceiptPending(int $receiptId): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT r.*, e.full_name AS employee_name, c.license_plate, a.file_path
                FROM gas_receipt r
                LEFT JOIN employee e ON r.employee_id = e.id
                LEFT JOIN car_detail c ON r.car_id = c.id
                LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
                WHERE r.id = :id
            ");
            $stmt->execute(['id' => $receiptId]);
            $r = $stmt->fetch();
            if (!$r)
                return;

            $embed = [
                'title' => '🧾 มีใบเสร็จน้ำมันรอตรวจสอบและอนุมัติ',
                'description' => 'พนักงานทำการอัปโหลดหลักฐานการเติมน้ำมันเข้าสู่ระบบเรียบร้อย',
                'color' => self::hexColor('#3498db'), // Blue
                'fields' => [
                    ['name' => '🧾 เลขที่ใบเสร็จ', 'value' => $r['receipt_number'], 'inline' => true],
                    ['name' => '🚗 ทะเบียนรถ / ผู้เบิก', 'value' => $r['license_plate'] . ' / ' . $r['employee_name'], 'inline' => true],
                    ['name' => '📅 วันที่เติมน้ำมัน', 'value' => date('d/m/Y', strtotime($r['receipt_date'])), 'inline' => true],
                    ['name' => '💰 จำนวนเงินเติมจริง', 'value' => number_format($r['amount'], 2) . ' บาท (' . number_format($r['liters'], 2) . ' ลิตร)', 'inline' => true]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('receipt_approvals', 'receipt_pending', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 7: Receipt Verification Result
    public static function sendReceiptVerificationResult(int $receiptId, string $status, string $verifier): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT r.*, e.full_name AS employee_name, c.license_plate
                FROM gas_receipt r
                LEFT JOIN employee e ON r.employee_id = e.id
                LEFT JOIN car_detail c ON r.car_id = c.id
                WHERE r.id = :id
            ");
            $stmt->execute(['id' => $receiptId]);
            $r = $stmt->fetch();
            if (!$r)
                return;

            $isApproved = $status === 'Approved';

            $embed = [
                'title' => $isApproved ? '✅ อนุมัติใบเสร็จค่าน้ำมันเรียบร้อย' : '❌ ปฏิเสธ/ยกเลิกใบเสร็จค่าน้ำมัน',
                'color' => $isApproved ? self::hexColor('#2ecc71') : self::hexColor('#e74c3c'),
                'fields' => [
                    ['name' => '🧾 เลขที่ใบเสร็จ', 'value' => $r['receipt_number'], 'inline' => true],
                    ['name' => '🚗 ทะเบียนรถ / ผู้เบิก', 'value' => $r['license_plate'] . ' / ' . $r['employee_name'], 'inline' => true],
                    ['name' => '💰 ยอดรายการเงิน', 'value' => number_format($r['amount'], 2) . ' บาท (' . number_format($r['liters'], 2) . ' ลิตร)', 'inline' => true],
                    ['name' => '👤 ผู้ตรวจสอบการอนุมัติ', 'value' => $verifier, 'inline' => true]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('receipt_approvals', 'receipt_status', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 8: Audit Logs & Security
    public static function sendSecurityAlert(string $actionType, array $details): void
    {
        try {
            $fields = [];
            $color = self::hexColor('#9b59b6'); // Purple
            $title = '🚨 ประวัติความเคลื่อนไหวสำคัญของระบบหลังบ้าน';

            if ($actionType === 'Login') {
                $title = '🔐 มีการเข้าสู่ระบบของผู้ดูแลระบบ (Admin)';
                $fields[] = ['name' => '👤 แอดมินล็อกอิน', 'value' => $details['full_name'] . ' (' . $details['username'] . ')', 'inline' => true];
                $fields[] = ['name' => '🛡️ บทบาทสิทธิ์', 'value' => $details['role'], 'inline' => true];
                $color = self::hexColor('#2ecc71');
            } elseif ($actionType === 'Change Password') {
                $title = '🔑 มีการเปลี่ยนแปลงรหัสผ่านผู้ใช้';
                $fields[] = ['name' => '👤 ผู้ทำรายการเปลี่ยน', 'value' => $details['full_name'] . ' (' . $details['username'] . ')', 'inline' => false];
                $fields[] = ['name' => '🔒 ความปลอดภัยรหัส', 'value' => 'ผ่านการเข้ารหัสลับแฮช Bcrypt (Cost: 12) เรียบร้อย', 'inline' => false];
                $color = self::hexColor('#e67e22');
            } elseif ($actionType === 'Update quota') {
                $title = '⛽ มีการแก้ไขสิทธิ์โควต้าน้ำมันรถยนต์';
                $fields[] = ['name' => '🚗 ทะเบียนรถ', 'value' => $details['license_plate'], 'inline' => true];
                $fields[] = ['name' => '⛽ โควต้าปรับใหม่', 'value' => number_format($details['monthly_quota'], 2) . ' ลิตร', 'inline' => true];
                $fields[] = ['name' => '📅 เดือนที่มีผล', 'value' => $details['effective_month'], 'inline' => true];
                $fields[] = ['name' => '👤 ผู้อนุมัติการแก้ไข', 'value' => $details['admin_full_name'] . ' (' . $details['admin_username'] . ')', 'inline' => false];
                $color = self::hexColor('#3498db');
            } else {
                $fields[] = ['name' => 'Action', 'value' => $actionType, 'inline' => true];
                $fields[] = ['name' => 'Details', 'value' => json_encode($details, JSON_UNESCAPED_UNICODE), 'inline' => false];
            }

            $embed = [
                'title' => $title,
                'color' => $color,
                'fields' => $fields,
                'timestamp' => date('c')
            ];

            self::sendToChannel('system_logs', 'audit_security', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }

    // Topic 9: System Errors
    public static function sendSystemError(\Throwable $e): void
    {
        try {
            $embed = [
                'title' => '🚨 ตรวจพบข้อผิดพลาดร้ายแรงของระบบ (System Error)',
                'description' => 'เกิดเหตุขัดข้องของระบบและโปรแกรมขัดจังหวะการทำงานของผู้ใช้งาน (Unhandled Exception)',
                'color' => self::hexColor('#e74c3c'), // Red
                'fields' => [
                    ['name' => '⚠️ รายละเอียด Error', 'value' => '```' . substr($e->getMessage(), 0, 500) . '```', 'inline' => false],
                    ['name' => '📁 ตำแหน่งไฟล์', 'value' => '`' . $e->getFile() . '` (บรรทัดที่ ' . $e->getLine() . ')', 'inline' => false],
                    ['name' => '📅 เวลาเกิดเหตุ', 'value' => date('d/m/Y H:i:s'), 'inline' => true]
                ],
                'timestamp' => date('c')
            ];

            self::sendToChannel('system_logs', 'system_errors', $embed);
        } catch (\Throwable $e) {
            // Do not break execution
        }
    }
}
