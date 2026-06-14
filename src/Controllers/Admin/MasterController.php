<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use Exception;

class MasterController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();

        // Divisions (กอง)
        $divisions = $db->query("SELECT * FROM division ORDER BY name ASC")->fetchAll();

        // Departments with their parent division name
        $departments = $db->query("
            SELECT d.*, dv.name AS division_name
            FROM department d
            LEFT JOIN division dv ON d.division_id = dv.id
            ORDER BY dv.name ASC, d.name ASC
        ")->fetchAll();

        // Positions with their parent department and division name
        $positions = $db->query("
            SELECT p.*, d.name AS department_name, dv.name AS division_name
            FROM position p
            LEFT JOIN department d ON p.department_id = d.id
            LEFT JOIN division dv ON p.division_id = dv.id
            ORDER BY COALESCE(dv.name, 'zzz') ASC, COALESCE(d.name, 'zzz') ASC, p.name ASC
        ")->fetchAll();

        $success = $_SESSION['master_success'] ?? null;
        $error   = $_SESSION['master_error']   ?? null;
        unset($_SESSION['master_success'], $_SESSION['master_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/master/index', [
            'divisions'   => $divisions,
            'departments' => $departments,
            'positions'   => $positions,
            'success'     => $success,
            'error'       => $error,
        ]);
    }

    public function createDivision(Request $request, Response $response) {
        $body = $request->getBody();
        $name = trim($body['name'] ?? '');

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อกอง/สำนัก';
            $response->redirect('/admin/master');
        }

        try {
            $db   = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO division (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);

            $id = $db->lastInsertId();
            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'division', :record_id, :new_value)
            ")->execute([
                'user_id'    => $_SESSION['admin_user']['id'],
                'username'   => $_SESSION['admin_user']['username'],
                'record_id'  => $id,
                'new_value'  => json_encode(['name' => $name]),
            ]);

            $_SESSION['master_success'] = 'เพิ่มกอง/สำนักเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function createDepartment(Request $request, Response $response) {
        $body        = $request->getBody();
        $name        = trim($body['name']        ?? '');
        $division_id = (int)($body['division_id'] ?? 0) ?: null;

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อฝ่าย/แผนก';
            $response->redirect('/admin/master');
        }

        try {
            $db   = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO department (division_id, name) VALUES (:division_id, :name)");
            $stmt->execute(['division_id' => $division_id, 'name' => $name]);

            $id = $db->lastInsertId();
            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'department', :record_id, :new_value)
            ")->execute([
                'user_id'   => $_SESSION['admin_user']['id'],
                'username'  => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'new_value' => json_encode(['division_id' => $division_id, 'name' => $name]),
            ]);

            $_SESSION['master_success'] = 'เพิ่มฝ่าย/แผนกเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function createPosition(Request $request, Response $response) {
        $body          = $request->getBody();
        $name          = trim($body['name']          ?? '');
        $division_id   = (int)($body['division_id']  ?? 0) ?: null;
        $department_id = (int)($body['department_id'] ?? 0) ?: null;

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อตำแหน่ง';
            $response->redirect('/admin/master');
        }

        try {
            $db = Database::getConnection();
            
            // If department is selected, automatically set division_id to the department's division
            if ($department_id) {
                $stmtDept = $db->prepare("SELECT division_id FROM department WHERE id = :id");
                $stmtDept->execute(['id' => $department_id]);
                $deptDiv = $stmtDept->fetchColumn();
                if ($deptDiv) {
                    $division_id = (int)$deptDiv;
                }
            }

            $stmt = $db->prepare("INSERT INTO position (division_id, department_id, name) VALUES (:division_id, :department_id, :name)");
            $stmt->execute(['division_id' => $division_id, 'department_id' => $department_id, 'name' => $name]);

            $id = $db->lastInsertId();
            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'position', :record_id, :new_value)
            ")->execute([
                'user_id'   => $_SESSION['admin_user']['id'],
                'username'  => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'new_value' => json_encode(['division_id' => $division_id, 'department_id' => $department_id, 'name' => $name]),
            ]);

            $_SESSION['master_success'] = 'เพิ่มตำแหน่งเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function updateDivision(Request $request, Response $response, $id) {
        $body = $request->getBody();
        $name = trim($body['name'] ?? '');
        $id = (int)$id;

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อกอง/สำนัก';
            $response->redirect('/admin/master');
        }

        try {
            $db = Database::getConnection();
            
            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM division WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("UPDATE division SET name = :name WHERE id = :id");
            $stmt->execute(['name' => $name, 'id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'division', :record_id, :previous_value, :new_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
                'new_value'      => json_encode(['name' => $name]),
            ]);

            $_SESSION['master_success'] = 'แก้ไขกอง/สำนักเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function deleteDivision(Request $request, Response $response, $id) {
        $id = (int)$id;
        try {
            $db = Database::getConnection();

            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM division WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("DELETE FROM division WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete', 'division', :record_id, :previous_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
            ]);

            $_SESSION['master_success'] = 'ลบกอง/สำนักเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีการเชื่อมโยงกับฝ่าย/แผนก หรือพนักงานในระบบ';
        }
        $response->redirect('/admin/master');
    }

    public function updateDepartment(Request $request, Response $response, $id) {
        $body        = $request->getBody();
        $name        = trim($body['name']        ?? '');
        $division_id = (int)($body['division_id'] ?? 0) ?: null;
        $id          = (int)$id;

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อฝ่าย/แผนก';
            $response->redirect('/admin/master');
        }

        try {
            $db = Database::getConnection();

            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM department WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("UPDATE department SET division_id = :division_id, name = :name WHERE id = :id");
            $stmt->execute(['division_id' => $division_id, 'name' => $name, 'id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'department', :record_id, :previous_value, :new_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
                'new_value'      => json_encode(['division_id' => $division_id, 'name' => $name]),
            ]);

            $_SESSION['master_success'] = 'แก้ไขฝ่าย/แผนกเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function deleteDepartment(Request $request, Response $response, $id) {
        $id = (int)$id;
        try {
            $db = Database::getConnection();

            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM department WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("DELETE FROM department WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete', 'department', :record_id, :previous_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
            ]);

            $_SESSION['master_success'] = 'ลบฝ่าย/แผนกเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีการเชื่อมโยงกับตำแหน่ง หรือพนักงานในระบบ';
        }
        $response->redirect('/admin/master');
    }

    public function updatePosition(Request $request, Response $response, $id) {
        $body          = $request->getBody();
        $name          = trim($body['name']          ?? '');
        $division_id   = (int)($body['division_id']  ?? 0) ?: null;
        $department_id = (int)($body['department_id'] ?? 0) ?: null;
        $id            = (int)$id;

        if (empty($name)) {
            $_SESSION['master_error'] = 'กรุณากรอกชื่อตำแหน่ง';
            $response->redirect('/admin/master');
        }

        try {
            $db = Database::getConnection();

            // If department is selected, automatically set division_id to the department's division
            if ($department_id) {
                $stmtDept = $db->prepare("SELECT division_id FROM department WHERE id = :id");
                $stmtDept->execute(['id' => $department_id]);
                $deptDiv = $stmtDept->fetchColumn();
                if ($deptDiv) {
                    $division_id = (int)$deptDiv;
                }
            }

            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM position WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("UPDATE position SET division_id = :division_id, department_id = :department_id, name = :name WHERE id = :id");
            $stmt->execute(['division_id' => $division_id, 'department_id' => $department_id, 'name' => $name, 'id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'position', :record_id, :previous_value, :new_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
                'new_value'      => json_encode(['division_id' => $division_id, 'department_id' => $department_id, 'name' => $name]),
            ]);

            $_SESSION['master_success'] = 'แก้ไขตำแหน่งเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'มีข้อมูลนี้ในระบบแล้วหรือเกิดข้อผิดพลาด';
        }
        $response->redirect('/admin/master');
    }

    public function deletePosition(Request $request, Response $response, $id) {
        $id = (int)$id;
        try {
            $db = Database::getConnection();

            // Get old value for audit logs
            $stmtOld = $db->prepare("SELECT * FROM position WHERE id = :id");
            $stmtOld->execute(['id' => $id]);
            $oldRow = $stmtOld->fetch();

            $stmt = $db->prepare("DELETE FROM position WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete', 'position', :record_id, :previous_value)
            ")->execute([
                'user_id'        => $_SESSION['admin_user']['id'],
                'username'       => $_SESSION['admin_user']['username'],
                'record_id'      => $id,
                'previous_value' => $oldRow ? json_encode($oldRow) : null,
            ]);

            $_SESSION['master_success'] = 'ลบตำแหน่งเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['master_error'] = 'ไม่สามารถลบข้อมูลได้ เนื่องจากตำแหน่งนี้มีพนักงานสังกัดอยู่';
        }
        $response->redirect('/admin/master');
    }
}
