<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Core\DiscordNotifier;
use Exception;

class DiscordSettingsController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();
        
        // Fetch current settings
        $settings = DiscordNotifier::getSettings();
        
        // Define default structure if database record is empty
        if (empty($settings)) {
            $settings = [
                'channels' => [
                    'booking_alerts' => [
                        'webhook_url' => '',
                        'topics' => ['new_booking' => '0', 'cancel_booking' => '0']
                    ],
                    'vehicle_status' => [
                        'webhook_url' => '',
                        'topics' => ['suspension' => '0']
                    ],
                    'fuel_quotas' => [
                        'webhook_url' => '',
                        'topics' => ['quota_low' => '0', 'quota_over' => '0'],
                        'alert_cycle' => 'always'
                    ],
                    'receipt_approvals' => [
                        'webhook_url' => '',
                        'topics' => ['receipt_pending' => '0', 'receipt_status' => '0']
                    ],
                    'system_logs' => [
                        'webhook_url' => '',
                        'topics' => ['audit_security' => '0', 'system_errors' => '0']
                    ]
                ]
            ];
        }

        $success = $_SESSION['discord_success'] ?? null;
        $error = $_SESSION['discord_error'] ?? null;
        unset($_SESSION['discord_success'], $_SESSION['discord_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/discord_settings/index', [
            'settings' => $settings,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function save(Request $request, Response $response) {
        $body = $request->getBody();
        $submittedChannels = $body['channels'] ?? [];

        // Structure the settings data with defaults for checkboxes/radio buttons
        $channelsData = [
            'booking_alerts' => [
                'webhook_url' => trim($submittedChannels['booking_alerts']['webhook_url'] ?? ''),
                'topics' => [
                    'new_booking' => $submittedChannels['booking_alerts']['topics']['new_booking'] ?? '0',
                    'cancel_booking' => $submittedChannels['booking_alerts']['topics']['cancel_booking'] ?? '0'
                ]
            ],
            'vehicle_status' => [
                'webhook_url' => trim($submittedChannels['vehicle_status']['webhook_url'] ?? ''),
                'topics' => [
                    'suspension' => $submittedChannels['vehicle_status']['topics']['suspension'] ?? '0'
                ]
            ],
            'fuel_quotas' => [
                'webhook_url' => trim($submittedChannels['fuel_quotas']['webhook_url'] ?? ''),
                'topics' => [
                    'quota_low' => $submittedChannels['fuel_quotas']['topics']['quota_low'] ?? '0',
                    'quota_over' => $submittedChannels['fuel_quotas']['topics']['quota_over'] ?? '0'
                ],
                'alert_cycle' => $submittedChannels['fuel_quotas']['alert_cycle'] ?? 'always'
            ],
            'receipt_approvals' => [
                'webhook_url' => trim($submittedChannels['receipt_approvals']['webhook_url'] ?? ''),
                'topics' => [
                    'receipt_pending' => $submittedChannels['receipt_approvals']['topics']['receipt_pending'] ?? '0',
                    'receipt_status' => $submittedChannels['receipt_approvals']['topics']['receipt_status'] ?? '0'
                ]
            ],
            'system_logs' => [
                'webhook_url' => trim($submittedChannels['system_logs']['webhook_url'] ?? ''),
                'topics' => [
                    'audit_security' => $submittedChannels['system_logs']['topics']['audit_security'] ?? '0',
                    'system_errors' => $submittedChannels['system_logs']['topics']['system_errors'] ?? '0'
                ]
            ]
        ];

        $settings = ['channels' => $channelsData];
        $settingsJson = json_encode($settings);

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description)
                VALUES ('discord_notification_settings', :val1, 'โครงสร้างและการตั้งค่าการแจ้งเตือน Discord Webhook')
                ON DUPLICATE KEY UPDATE setting_value = :val2
            ");
            $stmt->execute([
                'val1' => $settingsJson,
                'val2' => $settingsJson
            ]);

            $_SESSION['discord_success'] = 'บันทึกการตั้งค่าการแจ้งเตือน Discord Webhook เรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['discord_error'] = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
        }

        $response->redirect('/admin/discord-settings');
    }
}
