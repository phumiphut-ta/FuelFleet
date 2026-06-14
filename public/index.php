<?php
// Start Session for Authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloading Composer dependencies and App classes
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// ==========================================
// 1. PUBLIC ROUTES (NO LOGIN REQUIRED)
// ==========================================
$router->get('/', [App\Controllers\Public\CalendarController::class, 'index']);
$router->get('/api/calendar/events', [App\Controllers\Public\CalendarController::class, 'getEvents']);
$router->get('/booking/new', [App\Controllers\Public\BookingController::class, 'new']);
$router->post('/booking/create', [App\Controllers\Public\BookingController::class, 'create']);
$router->get('/booking/edit/{id}', [App\Controllers\Public\BookingController::class, 'editPublic']);
$router->post('/booking/update/{id}', [App\Controllers\Public\BookingController::class, 'updatePublic']);
$router->post('/booking/cancel', [App\Controllers\Public\BookingController::class, 'cancel']);
$router->get('/heatmap', [App\Controllers\Public\CalendarController::class, 'heatmap']);
$router->get('/receipts/recent', [App\Controllers\Public\BookingController::class, 'recentReceipts']);
$router->get('/liff/quotas', [App\Controllers\Public\LiffController::class, 'quotas']);

// ==========================================
// 2. ADMIN AUTH ROUTES
// ==========================================
$router->get('/admin/login', [App\Controllers\Admin\AuthController::class, 'showLogin']);
$router->post('/admin/login', [App\Controllers\Admin\AuthController::class, 'login']);
$router->get('/admin/logout', [App\Controllers\Admin\AuthController::class, 'logout']);
$router->get('/admin/change-password', [App\Controllers\Admin\AuthController::class, 'showChangePassword']);
$router->post('/admin/change-password', [App\Controllers\Admin\AuthController::class, 'changePassword']);

// ==========================================
// 3. ADMIN MANAGEMENT ROUTES (PROTECTED)
// ==========================================
$router->get('/admin/dashboard', [App\Controllers\Admin\DashboardController::class, 'index']);

// 3.1 Employee & Master Data Management
$router->get('/admin/employees', [App\Controllers\Admin\EmployeeController::class, 'index']);
$router->get('/admin/employees/new', [App\Controllers\Admin\EmployeeController::class, 'new']);
$router->post('/admin/employees/create', [App\Controllers\Admin\EmployeeController::class, 'create']);
$router->get('/admin/employees/edit/{id}', [App\Controllers\Admin\EmployeeController::class, 'edit']);
$router->post('/admin/employees/update/{id}', [App\Controllers\Admin\EmployeeController::class, 'update']);

$router->get('/admin/master', [App\Controllers\Admin\MasterController::class, 'index']);
$router->post('/admin/master/division/create', [App\Controllers\Admin\MasterController::class, 'createDivision']);
$router->post('/admin/master/division/update/{id}', [App\Controllers\Admin\MasterController::class, 'updateDivision']);
$router->post('/admin/master/division/delete/{id}', [App\Controllers\Admin\MasterController::class, 'deleteDivision']);
$router->post('/admin/master/department/create', [App\Controllers\Admin\MasterController::class, 'createDepartment']);
$router->post('/admin/master/department/update/{id}', [App\Controllers\Admin\MasterController::class, 'updateDepartment']);
$router->post('/admin/master/department/delete/{id}', [App\Controllers\Admin\MasterController::class, 'deleteDepartment']);
$router->post('/admin/master/position/create', [App\Controllers\Admin\MasterController::class, 'createPosition']);
$router->post('/admin/master/position/update/{id}', [App\Controllers\Admin\MasterController::class, 'updatePosition']);
$router->post('/admin/master/position/delete/{id}', [App\Controllers\Admin\MasterController::class, 'deletePosition']);

// 3.2 Vehicle Management
$router->get('/admin/cars', [App\Controllers\Admin\CarController::class, 'index']);
$router->get('/admin/cars/new', [App\Controllers\Admin\CarController::class, 'new']);
$router->post('/admin/cars/create', [App\Controllers\Admin\CarController::class, 'create']);
$router->get('/admin/cars/edit/{id}', [App\Controllers\Admin\CarController::class, 'edit']);
$router->post('/admin/cars/update/{id}', [App\Controllers\Admin\CarController::class, 'update']);
$router->get('/admin/cars/history/{id}', [App\Controllers\Admin\CarController::class, 'history']);

// 3.3 Vehicle Suspension Management
$router->get('/admin/suspensions', [App\Controllers\Admin\SuspensionController::class, 'index']);
$router->get('/admin/suspensions/new', [App\Controllers\Admin\SuspensionController::class, 'new']);
$router->post('/admin/suspensions/create', [App\Controllers\Admin\SuspensionController::class, 'create']);
$router->post('/admin/suspensions/cancel/{id}', [App\Controllers\Admin\SuspensionController::class, 'cancel']);

// 3.4 Fuel Quota Management
$router->get('/admin/quotas', [App\Controllers\Admin\QuotaController::class, 'index']);
$router->post('/admin/quotas/update', [App\Controllers\Admin\QuotaController::class, 'update']);

// 3.5 Fuel Receipt Management
$router->get('/admin/receipts', [App\Controllers\Admin\ReceiptController::class, 'index']);
$router->get('/admin/receipts/export', [App\Controllers\Admin\ReceiptController::class, 'export']);
$router->get('/admin/receipts/new', [App\Controllers\Admin\ReceiptController::class, 'new']);
$router->post('/admin/receipts/create', [App\Controllers\Admin\ReceiptController::class, 'create']);
$router->post('/admin/receipts/verify/{id}', [App\Controllers\Admin\ReceiptController::class, 'verify']);
$router->post('/admin/receipts/cancel/{id}', [App\Controllers\Admin\ReceiptController::class, 'cancel']);
$router->get('/admin/receipts/edit/{id}', [App\Controllers\Admin\ReceiptController::class, 'edit']);
$router->post('/admin/receipts/update/{id}', [App\Controllers\Admin\ReceiptController::class, 'update']);

// 3.6 Reports Center
$router->get('/admin/reports', [App\Controllers\Admin\ReportController::class, 'index']);
$router->post('/admin/reports/generate', [App\Controllers\Admin\ReportController::class, 'generate']);

// 3.7 Audit Logs
$router->get('/admin/audit-logs', [App\Controllers\Admin\AuditLogController::class, 'index']);

// 3.8 Bookings Management
$router->get('/admin/bookings', [App\Controllers\Admin\BookingController::class, 'index']);
$router->get('/admin/bookings/export', [App\Controllers\Admin\BookingController::class, 'export']);
$router->get('/admin/bookings/edit/{id}', [App\Controllers\Admin\BookingController::class, 'edit']);
$router->post('/admin/bookings/update/{id}', [App\Controllers\Admin\BookingController::class, 'update']);
$router->post('/admin/bookings/approve/{id}', [App\Controllers\Admin\BookingController::class, 'approve']);
$router->post('/admin/bookings/cancel/{id}', [App\Controllers\Admin\BookingController::class, 'cancel']);

// 3.9 Admin Users Management
$router->get('/admin/users', [App\Controllers\Admin\AdminUserController::class, 'index']);
$router->get('/admin/users/new', [App\Controllers\Admin\AdminUserController::class, 'new']);
$router->post('/admin/users/create', [App\Controllers\Admin\AdminUserController::class, 'create']);
$router->get('/admin/users/edit/{id}', [App\Controllers\Admin\AdminUserController::class, 'edit']);
$router->post('/admin/users/update/{id}', [App\Controllers\Admin\AdminUserController::class, 'update']);
$router->post('/admin/users/delete/{id}', [App\Controllers\Admin\AdminUserController::class, 'delete']);

// 3.10 Booking Agreements Management
$router->get('/admin/agreements', [App\Controllers\Admin\AgreementController::class, 'index']);
$router->post('/admin/agreements/create', [App\Controllers\Admin\AgreementController::class, 'create']);
$router->post('/admin/agreements/update/{id}', [App\Controllers\Admin\AgreementController::class, 'update']);
$router->post('/admin/agreements/delete/{id}', [App\Controllers\Admin\AgreementController::class, 'delete']);
$router->post('/admin/agreements/reorder', [App\Controllers\Admin\AgreementController::class, 'reorder']);

// 3.11 Historical Data Import Management
$router->get('/admin/history-import', [App\Controllers\Admin\HistoryImportController::class, 'index']);
$router->post('/admin/history-import/fuel', [App\Controllers\Admin\HistoryImportController::class, 'saveFuel']);
$router->post('/admin/history-import/travel', [App\Controllers\Admin\HistoryImportController::class, 'saveTravel']);
$router->post('/admin/history-import/fuel/delete/{id}', [App\Controllers\Admin\HistoryImportController::class, 'deleteFuel']);
$router->post('/admin/history-import/travel/delete', [App\Controllers\Admin\HistoryImportController::class, 'deleteTravel']);

// 3.12 LINE Alert Helper
$router->get('/admin/line-helper', [App\Controllers\Admin\LineHelperController::class, 'index']);
$router->post('/admin/line-helper/save', [App\Controllers\Admin\LineHelperController::class, 'save']);

// Resolve router and render views
$router->resolve();
