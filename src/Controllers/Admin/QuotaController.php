<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\MySQL\QuotaRepository;
use App\Repositories\MySQL\CarRepository;
use Exception;

class QuotaController {
    protected QuotaRepository $quotaRepo;
    protected CarRepository $carRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->quotaRepo = new QuotaRepository();
        $this->carRepo = new CarRepository();
    }

    public function index(Request $request, Response $response) {
        $quotas = $this->quotaRepo->all();
        $cars = $this->carRepo->all();

        // Get current active quota limit for each vehicle for this month
        $db = Database::getConnection();
        $activeMonth = date('Y-m-01');
        
        $currentQuotas = [];
        foreach ($cars as $car) {
            $quotaRec = $this->quotaRepo->getCurrentQuotaForCar((int)$car['id'], date('Y-m'));
            $currentQuotas[$car['id']] = $quotaRec ? (float)$quotaRec['monthly_quota'] : 0.0;
        }

        $success = $_SESSION['quota_success'] ?? null;
        $error = $_SESSION['quota_error'] ?? null;
        unset($_SESSION['quota_success'], $_SESSION['quota_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/quota/index', [
            'quotas' => $quotas,
            'cars' => $cars,
            'currentQuotas' => $currentQuotas,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response) {
        $body = $request->getBody();
        $carId = (int)($body['car_id'] ?? 0);
        $quota = (float)($body['monthly_quota'] ?? 0);
        $effectiveMonth = trim($body['effective_month'] ?? '');

        if (!$carId || $quota <= 0 || empty($effectiveMonth)) {
            $_SESSION['quota_error'] = 'กรุณาระบุปริมาณน้ำมันโควต้า (ลิตร) และเดือนที่เริ่มมีผลบังคับใช้ให้ถูกต้อง';
            $response->redirect('/admin/quotas');
        }

        try {
            // Reformat effective month to standard Y-m-01
            $formattedMonth = date('Y-m-01', strtotime($effectiveMonth . '-01'));

            // Create new quota version
            $quotaId = $this->quotaRepo->create([
                'car_id' => $carId,
                'monthly_quota' => $quota,
                'effective_month' => $formattedMonth
            ]);

            // Audit Log
            $car = $this->carRepo->find($carId);
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Update quota', 'car_quota_history', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $quotaId,
                'new_value' => json_encode(['car_id' => $carId, 'license_plate' => $car['license_plate'] ?? '', 'monthly_quota' => $quota, 'effective_month' => $formattedMonth])
            ]);

            // Discord notification for quota update
            \App\Core\DiscordNotifier::sendSecurityAlert('Update quota', [
                'license_plate' => $car['license_plate'] ?? '',
                'monthly_quota' => $quota,
                'effective_month' => date('m/Y', strtotime($formattedMonth)),
                'admin_full_name' => $_SESSION['admin_user']['full_name'],
                'admin_username' => $_SESSION['admin_user']['username']
            ]);

            $_SESSION['quota_success'] = 'ปรับปรุงโควต้าน้ำมันและบันทึกประวัติโควต้าเวอร์ชันใหม่เรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['quota_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        $response->redirect('/admin/quotas');
    }
}
