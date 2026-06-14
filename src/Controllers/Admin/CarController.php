<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\MySQL\CarRepository;
use Exception;

class CarController {
    protected CarRepository $carRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->carRepo = new CarRepository();
    }

    public function index(Request $request, Response $response) {
        $cars = $this->carRepo->all();

        $success = $_SESSION['car_success'] ?? null;
        $error = $_SESSION['car_error'] ?? null;
        unset($_SESSION['car_success'], $_SESSION['car_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/car/index', [
            'cars' => $cars,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function new(Request $request, Response $response) {
        $router = new Router($request, $response);
        return $router->renderView('admin/car/new');
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $plate = trim($body['license_plate'] ?? '');
        $fuelType = trim($body['fuel_type'] ?? '');
        $status = trim($body['status'] ?? 'Active');
        $note = trim($body['note'] ?? '');
        $color = trim($body['color'] ?? '#4f46e5');

        if (empty($plate) || empty($fuelType)) {
            $_SESSION['car_error'] = 'กรุณากรอกทะเบียนรถและประเภทน้ำมัน';
            $response->redirect('/admin/cars/new');
        }

        try {
            $existing = $this->carRepo->findByPlate($plate);
            if ($existing) {
                $_SESSION['car_error'] = "มีเลขทะเบียนรถ \"{$plate}\" ในระบบแล้ว";
                $response->redirect('/admin/cars/new');
            }

            $carId = $this->carRepo->create([
                'license_plate' => $plate,
                'fuel_type' => $fuelType,
                'status' => $status,
                'note' => $note,
                'color' => $color
            ]);

            // Set a default quota (e.g. 300 liters) for the current month when a new car is created
            $db = Database::getConnection();
            $stmtQuota = $db->prepare("
                INSERT INTO car_quota_history (car_id, monthly_quota, effective_month)
                VALUES (:car_id, :monthly_quota, :effective_month)
            ");
            $stmtQuota->execute([
                'car_id' => $carId,
                'monthly_quota' => 300.00,
                'effective_month' => date('Y-m-01')
            ]);

            // Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'car_detail', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $carId,
                'new_value' => json_encode(['license_plate' => $plate, 'fuel_type' => $fuelType, 'status' => $status])
            ]);

            $_SESSION['car_success'] = 'เพิ่มยานพาหนะเข้าระบบและตั้งค่าโควต้าตั้งต้น 300 ลิตรเรียบร้อยแล้ว';
            $response->redirect('/admin/cars');
        } catch (Exception $e) {
            $_SESSION['car_error'] = 'เกิดข้อผิดพลาดในการบันทึกรถ: ' . $e->getMessage();
            $response->redirect('/admin/cars/new');
        }
    }

    public function edit(Request $request, Response $response, int $id) {
        $car = $this->carRepo->find($id);
        if (!$car) {
            $_SESSION['car_error'] = 'ไม่พบยานพาหนะดังกล่าว';
            $response->redirect('/admin/cars');
        }

        $error = $_SESSION['car_error'] ?? null;
        unset($_SESSION['car_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/car/edit', [
            'car' => $car,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response, int $id) {
        $car = $this->carRepo->find($id);
        if (!$car) {
            $_SESSION['car_error'] = 'ไม่พบยานพาหนะดังกล่าว';
            $response->redirect('/admin/cars');
        }

        $body = $request->getBody();
        $plate = trim($body['license_plate'] ?? '');
        $fuelType = trim($body['fuel_type'] ?? '');
        $status = trim($body['status'] ?? 'Active');
        $note = trim($body['note'] ?? '');
        $color = trim($body['color'] ?? '#4f46e5');

        if (empty($plate) || empty($fuelType)) {
            $_SESSION['car_error'] = 'กรุณากรอกทะเบียนรถและประเภทน้ำมัน';
            $response->redirect("/admin/cars/edit/{$id}");
        }

        try {
            $this->carRepo->update($id, [
                'license_plate' => $plate,
                'fuel_type' => $fuelType,
                'status' => $status,
                'note' => $note,
                'color' => $color
            ]);

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'car_detail', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($car),
                'new_value' => json_encode(['license_plate' => $plate, 'fuel_type' => $fuelType, 'status' => $status])
            ]);

            $_SESSION['car_success'] = 'แก้ไขข้อมูลยานพาหนะสำเร็จ';
            $response->redirect('/admin/cars');
        } catch (Exception $e) {
            $_SESSION['car_error'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลรถ: ' . $e->getMessage();
            $response->redirect("/admin/cars/edit/{$id}");
        }
    }

    public function history(Request $request, Response $response, int $id) {
        $car = $this->carRepo->find($id);
        if (!$car) {
            $_SESSION['car_error'] = 'ไม่พบยานพาหนะดังกล่าว';
            $response->redirect('/admin/cars');
        }

        $bookings = $this->carRepo->getBookingHistory($id);
        $fuels = $this->carRepo->getFuelUsageHistory($id);
        $quotas = $this->carRepo->getQuotaHistory($id);

        $router = new Router($request, $response);
        return $router->renderView('admin/car/history', [
            'car' => $car,
            'bookings' => $bookings,
            'fuels' => $fuels,
            'quotas' => $quotas
        ]);
    }
}
