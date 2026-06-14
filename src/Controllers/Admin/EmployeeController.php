<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\MySQL\EmployeeRepository;
use Exception;

class EmployeeController {
    protected EmployeeRepository $employeeRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->employeeRepo = new EmployeeRepository();
    }

    public function index(Request $request, Response $response) {
        $employees = $this->employeeRepo->all();
        
        $success = $_SESSION['employee_success'] ?? null;
        $error = $_SESSION['employee_error'] ?? null;
        unset($_SESSION['employee_success'], $_SESSION['employee_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/employee/index', [
            'employees' => $employees,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function new(Request $request, Response $response) {
        $db = Database::getConnection();
        $divisions = $db->query("SELECT * FROM division ORDER BY name ASC")->fetchAll();
        $departments = $db->query("SELECT * FROM department ORDER BY name ASC")->fetchAll();
        $positions = $db->query("SELECT * FROM position ORDER BY name ASC")->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('admin/employee/new', [
            'divisions' => $divisions,
            'departments' => $departments,
            'positions' => $positions
        ]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $code = trim($body['employee_code'] ?? '');
        $name = trim($body['full_name'] ?? '');
        $posId  = (int)($body['position_id'] ?? 0);
        $status = trim($body['status'] ?? 'Active');

        if (empty($code) || empty($name) || !$posId) {
            $_SESSION['employee_error'] = 'กรุณากรอกรหัสพนักงาน ชื่อ-นามสกุล และตำแหน่งงาน';
            $response->redirect('/admin/employees/new');
        }

        // Fetch division_id and department_id from the selected Position to ensure perfect consistency
        $divId = null;
        $deptId = null;
        $db = Database::getConnection();
        $stmtPos = $db->prepare("SELECT division_id, department_id FROM position WHERE id = :id");
        $stmtPos->execute(['id' => $posId]);
        $posInfo = $stmtPos->fetch();
        if ($posInfo) {
            $divId = $posInfo['division_id'] ? (int)$posInfo['division_id'] : null;
            $deptId = $posInfo['department_id'] ? (int)$posInfo['department_id'] : null;
        }

        try {
            // Check code duplicates
            $existing = $this->employeeRepo->findByCode($code);
            if ($existing) {
                $_SESSION['employee_error'] = "รหัสพนักงาน \"{$code}\" ถูกใช้งานแล้ว";
                $response->redirect('/admin/employees/new');
            }

            // Create employee
            $empId = $this->employeeRepo->create([
                'employee_code' => $code,
                'full_name' => $name,
                'division_id' => $divId,
                'department_id' => $deptId,
                'position_id' => $posId,
                'status' => $status
            ]);

            // Add first assignment history
            $this->employeeRepo->assign($empId, $divId, $deptId, $posId, date('Y-m-d'));

            // Log activity
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'employee', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $empId,
                'new_value' => json_encode(['employee_code' => $code, 'full_name' => $name, 'status' => $status])
            ]);

            $_SESSION['employee_success'] = 'เพิ่มทะเบียนพนักงานสำเร็จเสร็จสิ้น';
            $response->redirect('/admin/employees');
        } catch (Exception $e) {
            $_SESSION['employee_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
            $response->redirect('/admin/employees/new');
        }
    }

    public function edit(Request $request, Response $response, int $id) {
        $employee = $this->employeeRepo->find($id);
        if (!$employee) {
            $_SESSION['employee_error'] = 'ไม่พบพนักงานคนดังกล่าวในระบบ';
            $response->redirect('/admin/employees');
        }

        $db = Database::getConnection();
        $divisions = $db->query("SELECT * FROM division ORDER BY name ASC")->fetchAll();
        $departments = $db->query("SELECT * FROM department ORDER BY name ASC")->fetchAll();
        $positions = $db->query("SELECT * FROM position ORDER BY name ASC")->fetchAll();
        
        // Fetch assignment history
        $assignments = $this->employeeRepo->getAssignments($id);

        $error = $_SESSION['employee_error'] ?? null;
        unset($_SESSION['employee_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/employee/edit', [
            'employee' => $employee,
            'divisions' => $divisions,
            'departments' => $departments,
            'positions' => $positions,
            'assignments' => $assignments,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response, int $id) {
        $employee = $this->employeeRepo->find($id);
        if (!$employee) {
            $_SESSION['employee_error'] = 'ไม่พบพนักงานคนดังกล่าวในระบบ';
            $response->redirect('/admin/employees');
        }

        $body = $request->getBody();
        $code = trim($body['employee_code'] ?? '');
        $name = trim($body['full_name'] ?? '');
        $posId  = (int)($body['position_id'] ?? 0);
        $status = trim($body['status'] ?? 'Active');

        if (empty($code) || empty($name) || !$posId) {
            $_SESSION['employee_error'] = 'กรุณากรอกรหัสพนักงาน ชื่อ-นามสกุล และตำแหน่งงาน';
            $response->redirect("/admin/employees/edit/{$id}");
        }

        // Fetch division_id and department_id from the selected Position to ensure perfect consistency
        $divId = null;
        $deptId = null;
        $db = Database::getConnection();
        $stmtPos = $db->prepare("SELECT division_id, department_id FROM position WHERE id = :id");
        $stmtPos->execute(['id' => $posId]);
        $posInfo = $stmtPos->fetch();
        if ($posInfo) {
            $divId = $posInfo['division_id'] ? (int)$posInfo['division_id'] : null;
            $deptId = $posInfo['department_id'] ? (int)$posInfo['department_id'] : null;
        }

        try {
            // Check dynamic transfer
            $isTransferred = ($employee['division_id'] != $divId || $employee['department_id'] != $deptId || $employee['position_id'] != $posId);

            if ($isTransferred) {
                // Record new assignment in history (without overwriting old ones)
                $this->employeeRepo->assign($id, $divId, $deptId, $posId, date('Y-m-d'));
            }

            // Update main employee profile
            $this->employeeRepo->update($id, [
                'employee_code' => $code,
                'full_name' => $name,
                'division_id' => $divId,
                'department_id' => $deptId,
                'position_id' => $posId,
                'status' => $status
            ]);

            // Log activity
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'employee', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($employee),
                'new_value' => json_encode(['employee_code' => $code, 'full_name' => $name, 'status' => $status, 'transferred' => $isTransferred])
            ]);

            $_SESSION['employee_success'] = 'อัปเดตข้อมูลพนักงานและบันทึกประวัติการย้ายสำเร็จ';
            $response->redirect('/admin/employees');
        } catch (Exception $e) {
            $_SESSION['employee_error'] = 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $e->getMessage();
            $response->redirect("/admin/employees/edit/{$id}");
        }
    }
}
