<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\MySQL\AuditLogRepository;

class AuditLogController {
    protected AuditLogRepository $auditRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index(Request $request, Response $response) {
        $logs = $this->auditRepo->all();

        $router = new Router($request, $response);
        return $router->renderView('admin/audit_log/index', ['logs' => $logs]);
    }
}
