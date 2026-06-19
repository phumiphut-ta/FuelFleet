<?php
namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\DiscordNotifier;
use App\Core\Database;
use PDO;
use PDOStatement;

class DiscordNotifierTest extends TestCase {
    private array $fakeSettings = [
        'channels' => [
            'booking_alerts' => [
                'webhook_url' => 'http://localhost/fake-webhook',
                'topics' => ['new_booking' => '1', 'cancel_booking' => '1']
            ],
            'vehicle_status' => [
                'webhook_url' => 'http://localhost/fake-webhook',
                'topics' => ['suspension' => '1']
            ],
            'fuel_quotas' => [
                'webhook_url' => 'http://localhost/fake-webhook',
                'topics' => ['quota_low' => '1', 'quota_over' => '1']
            ],
            'receipt_approvals' => [
                'webhook_url' => 'http://localhost/fake-webhook',
                'topics' => ['receipt_pending' => '1', 'receipt_status' => '1']
            ],
            'system_logs' => [
                'webhook_url' => 'http://localhost/fake-webhook',
                'topics' => ['audit_security' => '1', 'system_errors' => '1']
            ]
        ]
    ];

    protected function setUp(): void {
        parent::setUp();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    private function injectMockPdo($mockPdo) {
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
    }

    private function cleanMockPdo() {
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }

    public function testGetSettingsEmpty() {
        $mockPdo = $this->createMock(PDO::class);
        $mockStmt = $this->createMock(PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(false);
        $mockPdo->method('prepare')->willReturn($mockStmt);

        $this->injectMockPdo($mockPdo);
        $settings = DiscordNotifier::getSettings();
        $this->assertEquals([], $settings);
        $this->cleanMockPdo();
    }

    public function testSendNewBooking() {
        $mockPdo = $this->createMock(PDO::class);
        
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));

        $mockStmtBooking = $this->createMock(PDOStatement::class);
        $mockStmtBooking->method('execute')->willReturn(true);
        $mockStmtBooking->method('fetch')->willReturn([
            'employee_name' => 'John Doe',
            'license_plate' => 'กข-1234',
            'fuel_type' => 'Gasoline',
            'start_time' => '2026-06-20 08:00:00',
            'end_time' => '2026-06-22 17:00:00',
            'purpose' => 'Meeting'
        ]);

        $mockStmtProvinces = $this->createMock(PDOStatement::class);
        $mockStmtProvinces->method('execute')->willReturn(true);
        $mockStmtProvinces->method('fetchAll')->willReturn(['Bangkok', 'Nonthaburi']);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtBooking, $mockStmtProvinces) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            if (strpos($sql, 'car_booking_provinces') !== false) {
                return $mockStmtProvinces;
            }
            return $mockStmtBooking;
        });

        $this->injectMockPdo($mockPdo);
        // Ensure no exception is thrown
        DiscordNotifier::sendNewBooking(1);
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testSendCancelledBooking() {
        $mockPdo = $this->createMock(PDO::class);
        
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));

        $mockStmtBooking = $this->createMock(PDOStatement::class);
        $mockStmtBooking->method('execute')->willReturn(true);
        $mockStmtBooking->method('fetch')->willReturn([
            'employee_name' => 'John Doe',
            'license_plate' => 'กข-1234',
            'fuel_type' => 'Gasoline',
            'start_time' => '2026-06-20 08:00:00',
            'end_time' => '2026-06-22 17:00:00',
            'purpose' => 'Meeting'
        ]);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtBooking) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            return $mockStmtBooking;
        });

        $this->injectMockPdo($mockPdo);
        DiscordNotifier::sendCancelledBooking(1, 'Personal Reason', 'John Doe');
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testSendSuspensionCreatedAndCancelled() {
        $mockPdo = $this->createMock(PDO::class);
        
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));

        $mockStmtSuspension = $this->createMock(PDOStatement::class);
        $mockStmtSuspension->method('execute')->willReturn(true);
        $mockStmtSuspension->method('fetch')->willReturn([
            'license_plate' => 'กข-1234',
            'admin_name' => 'Super Admin',
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-25',
            'reason' => 'Engine overhaul'
        ]);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtSuspension) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            return $mockStmtSuspension;
        });

        $this->injectMockPdo($mockPdo);
        DiscordNotifier::sendSuspensionCreated(1);
        DiscordNotifier::sendSuspensionCancelled(1);
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testSendReceiptPendingAndVerificationResult() {
        $mockPdo = $this->createMock(PDO::class);
        
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));

        $mockStmtReceipt = $this->createMock(PDOStatement::class);
        $mockStmtReceipt->method('execute')->willReturn(true);
        $mockStmtReceipt->method('fetch')->willReturn([
            'receipt_number' => 'REC-999',
            'employee_name' => 'John Doe',
            'license_plate' => 'กข-1234',
            'receipt_date' => '2026-06-17',
            'amount' => 1200.00,
            'liters' => 35.50
        ]);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtReceipt) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            return $mockStmtReceipt;
        });

        $this->injectMockPdo($mockPdo);
        DiscordNotifier::sendReceiptPending(1);
        DiscordNotifier::sendReceiptVerificationResult(1, 'Approved', 'Admin User');
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testSendSecurityAlert() {
        $mockPdo = $this->createMock(PDO::class);
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));
        $mockPdo->method('prepare')->willReturn($mockStmtSettings);

        $this->injectMockPdo($mockPdo);
        DiscordNotifier::sendSecurityAlert('Login', [
            'full_name' => 'Test Admin',
            'username' => 'testadmin',
            'role' => 'editor'
        ]);
        DiscordNotifier::sendSecurityAlert('Change Password', [
            'full_name' => 'Test Admin',
            'username' => 'testadmin'
        ]);
        DiscordNotifier::sendSecurityAlert('Update quota', [
            'license_plate' => 'กข-1234',
            'monthly_quota' => 250.00,
            'effective_month' => '07/2026',
            'admin_full_name' => 'Super Admin',
            'admin_username' => 'superadmin'
        ]);
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testSendSystemError() {
        $mockPdo = $this->createMock(PDO::class);
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));
        $mockPdo->method('prepare')->willReturn($mockStmtSettings);

        $this->injectMockPdo($mockPdo);
        $exception = new \Exception("Database connection timed out");
        DiscordNotifier::sendSystemError($exception);
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testCheckAndSendQuotaAlertsFirstTime() {
        $mockPdo = $this->createMock(PDO::class);
        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($this->fakeSettings));

        $mockStmtCar = $this->createMock(PDOStatement::class);
        $mockStmtCar->method('execute')->willReturn(true);
        $mockStmtCar->method('fetch')->willReturn([
            'license_plate' => 'กข-1234',
            'threshold' => 20.00,
            'last_quota_alert_at' => null,
            'monthly_quota' => 100.00,
            'total_liters' => 90.00
        ]);

        $mockStmtUpdate = $this->createMock(PDOStatement::class);
        $mockStmtUpdate->method('execute')->willReturn(true);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtCar, $mockStmtUpdate) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            if (strpos($sql, 'UPDATE car_detail SET last_quota_alert_at') !== false) {
                return $mockStmtUpdate;
            }
            return $mockStmtCar;
        });

        $this->injectMockPdo($mockPdo);
        
        $mockStmtQuery = $this->createMock(PDOStatement::class);
        $mockStmtQuery->method('fetchAll')->willReturn([]);
        $mockPdo->method('query')->willReturn($mockStmtQuery);

        DiscordNotifier::checkAndSendQuotaAlerts(1, '2026-06');
        $this->assertTrue(true);
        $this->cleanMockPdo();
    }

    public function testCheckAndSendQuotaAlertsCycleThrottled() {
        $mockPdo = $this->createMock(PDO::class);
        
        // 1hour cycle, last alert was 10 mins ago -> should NOT alert (no UPDATE executed)
        $settings = $this->fakeSettings;
        $settings['channels']['fuel_quotas']['alert_cycle'] = '1hour';

        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($settings));

        $mockStmtCar = $this->createMock(PDOStatement::class);
        $mockStmtCar->method('execute')->willReturn(true);
        $mockStmtCar->method('fetch')->willReturn([
            'license_plate' => 'กข-1234',
            'threshold' => 20.00,
            'last_quota_alert_at' => date('Y-m-d H:i:s', time() - 600), // 10 mins ago
            'monthly_quota' => 100.00,
            'total_liters' => 90.00
        ]);

        $mockStmtUpdate = $this->createMock(PDOStatement::class);
        // We expect UPDATE should NOT be called since it is throttled
        $mockStmtUpdate->expects($this->never())->method('execute');

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtCar, $mockStmtUpdate) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            if (strpos($sql, 'UPDATE car_detail SET last_quota_alert_at') !== false) {
                return $mockStmtUpdate;
            }
            return $mockStmtCar;
        });

        $this->injectMockPdo($mockPdo);
        
        $mockStmtQuery = $this->createMock(PDOStatement::class);
        $mockStmtQuery->method('fetchAll')->willReturn([]);
        $mockPdo->method('query')->willReturn($mockStmtQuery);

        DiscordNotifier::checkAndSendQuotaAlerts(1, '2026-06');
        $this->cleanMockPdo();
    }

    public function testCheckAndSendQuotaAlertsCycleTriggered() {
        $mockPdo = $this->createMock(PDO::class);
        
        // 1hour cycle, last alert was 61 mins ago -> should alert (UPDATE executed)
        $settings = $this->fakeSettings;
        $settings['channels']['fuel_quotas']['alert_cycle'] = '1hour';

        $mockStmtSettings = $this->createMock(PDOStatement::class);
        $mockStmtSettings->method('execute')->willReturn(true);
        $mockStmtSettings->method('fetchColumn')->willReturn(json_encode($settings));

        $mockStmtCar = $this->createMock(PDOStatement::class);
        $mockStmtCar->method('execute')->willReturn(true);
        $mockStmtCar->method('fetch')->willReturn([
            'license_plate' => 'กข-1234',
            'threshold' => 20.00,
            'last_quota_alert_at' => date('Y-m-d H:i:s', time() - 3660), // 61 mins ago
            'monthly_quota' => 100.00,
            'total_liters' => 90.00
        ]);

        $mockStmtUpdate = $this->createMock(PDOStatement::class);
        $mockStmtUpdate->expects($this->once())->method('execute')->willReturn(true);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtSettings, $mockStmtCar, $mockStmtUpdate) {
            if (strpos($sql, 'discord_notification_settings') !== false) {
                return $mockStmtSettings;
            }
            if (strpos($sql, 'UPDATE car_detail SET last_quota_alert_at') !== false) {
                return $mockStmtUpdate;
            }
            return $mockStmtCar;
        });

        $this->injectMockPdo($mockPdo);
        
        $mockStmtQuery = $this->createMock(PDOStatement::class);
        $mockStmtQuery->method('fetchAll')->willReturn([]);
        $mockPdo->method('query')->willReturn($mockStmtQuery);

        DiscordNotifier::checkAndSendQuotaAlerts(1, '2026-06');
        $this->cleanMockPdo();
    }

    public function testCheckAndSendQuotaAlertsResetAboveThreshold() {
        $mockPdo = $this->createMock(PDO::class);
        
        // Remaining fuel > threshold -> should reset last_quota_alert_at to NULL
        $mockStmtCar = $this->createMock(PDOStatement::class);
        $mockStmtCar->method('execute')->willReturn(true);
        $mockStmtCar->method('fetch')->willReturn([
            'license_plate' => 'กข-1234',
            'threshold' => 20.00,
            'last_quota_alert_at' => date('Y-m-d H:i:s'),
            'monthly_quota' => 100.00,
            'total_liters' => 50.00 // 50 remaining, > 20 threshold
        ]);

        $mockStmtReset = $this->createMock(PDOStatement::class);
        $mockStmtReset->expects($this->once())->method('execute')->willReturn(true);

        $mockPdo->method('prepare')->willReturnCallback(function($sql) use ($mockStmtCar, $mockStmtReset) {
            if (strpos($sql, 'UPDATE car_detail SET last_quota_alert_at = NULL') !== false) {
                return $mockStmtReset;
            }
            return $mockStmtCar;
        });

        $this->injectMockPdo($mockPdo);
        
        $mockStmtQuery = $this->createMock(PDOStatement::class);
        $mockStmtQuery->method('fetchAll')->willReturn([]);
        $mockPdo->method('query')->willReturn($mockStmtQuery);

        DiscordNotifier::checkAndSendQuotaAlerts(1, '2026-06');
        $this->cleanMockPdo();
    }
}
