<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__FILE__) . '/../config/Database.php';
require_once dirname(__FILE__) . '/../app/Controllers/UserController.php';
require_once dirname(__FILE__) . '/../app/Controllers/ExpenseController.php';
require_once dirname(__FILE__) . '/../app/Controllers/BudgetController.php';
require_once dirname(__FILE__) . '/../app/Controllers/RecurringExpenseController.php';
require_once dirname(__FILE__) . '/../app/Controllers/AlertController.php';
require_once dirname(__FILE__) . '/../app/Controllers/AnalyticsController.php';

$database = new Database();
$db = $database->connect();

$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = explode('/', trim($_SERVER['PATH_INFO'] ?? '/', '/'));

$resource = $request_path[0] ?? null;
$action = $request_path[1] ?? null;
$id = $request_path[2] ?? null;

try {
    switch ($resource) {
        // User authentication
        case 'auth':
            $controller = new UserController($db);
            if ($action === 'register' && $request_method === 'POST') {
                $controller->register();
            } elseif ($action === 'login' && $request_method === 'POST') {
                $controller->login();
            } elseif ($action === 'verify' && $request_method === 'GET') {
                $controller->verifyEmail();
            } elseif ($action === 'resend-verification' && $request_method === 'POST') {
                $controller->resendVerification();
            } elseif ($action === 'admin-verify' && $request_method === 'POST') {
                $controller->adminVerifyUser();
            } elseif ($action === 'logout' && $request_method === 'POST') {
                $controller->logout();
            } elseif ($action === 'current' && $request_method === 'GET') {
                $controller->getCurrentUser();
            } elseif ($action === 'profile' && $request_method === 'PUT') {
                $controller->updateProfile();
            }
            break;

        // Expenses
        case 'expenses':
            $controller = new ExpenseController($db);
            if ($request_method === 'GET') {
                if ($id) {
                    $controller->getById($id);
                } else {
                    $controller->getAll();
                }
            } elseif ($request_method === 'POST') {
                $controller->create();
            } elseif ($request_method === 'PUT' && $id) {
                $controller->update($id);
            } elseif ($request_method === 'DELETE' && $id) {
                $controller->delete($id);
            }
            break;

        case 'expenses-by-category':
            $controller = new ExpenseController($db);
            $controller->getTotalsByCategory();
            break;

        // Recurring Expenses
        case 'recurring-expenses':
            $controller = new RecurringExpenseController($db);
            if ($request_method === 'GET') {
                $controller->getAll();
            } elseif ($request_method === 'POST') {
                if ($action === 'process') {
                    $controller->process();
                } else {
                    $controller->create();
                }
            } elseif ($request_method === 'PUT' && $id) {
                $controller->update($id);
            } elseif ($request_method === 'DELETE' && $id) {
                $controller->delete($id);
            }
            break;

        // Budgets
        case 'budgets':
            $controller = new BudgetController($db);
            if ($request_method === 'GET') {
                $controller->getAll();
            } elseif ($request_method === 'POST') {
                $controller->create();
            } elseif ($request_method === 'PUT' && $id) {
                $controller->update($id);
            } elseif ($request_method === 'DELETE' && $id) {
                $controller->delete($id);
            }
            break;

        // Alerts
        case 'alerts':
            $controller = new AlertController($db);
            if ($request_method === 'GET') {
                if ($action === 'unread') {
                    $controller->getUnread();
                } elseif ($action === 'check' || ($request_method === 'POST' && $action === 'check')) {
                    $controller->checkBudgets();
                } else {
                    $controller->getAll();
                }
            } elseif ($request_method === 'PUT' && $id) {
                if ($action === 'read') {
                    $controller->markAsRead($id);
                } else {
                    $controller->markAsRead($id);
                }
            } elseif ($request_method === 'POST' && $action === 'read-all') {
                $controller->markAllAsRead();
            } elseif ($request_method === 'DELETE' && $id) {
                $controller->delete($id);
            }
            break;

        // Analytics
        case 'analytics':
            $controller = new AnalyticsController($db);
            switch ($action) {
                case 'monthly':
                    $controller->getMonthlySummary();
                    break;
                case 'trends':
                    $controller->getCategoryTrends();
                    break;
                case 'comparison':
                    $controller->getMonthComparison();
                    break;
                case 'yearly':
                    $controller->getYearlySummary();
                    break;
                case 'category-stats':
                    $controller->getCategoryStats();
                    break;
                case 'forecast':
                    $controller->getSpendingForecast();
                    break;
                case 'export':
                    $controller->exportCSV();
                    break;
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

