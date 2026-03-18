<?php
require_once dirname(__FILE__) . '/../Models/Analytics.php';

class AnalyticsController {
    private $db;
    private $analyticsModel;

    public function __construct($db) {
        $this->db = $db;
        $this->analyticsModel = new Analytics($db);
    }

    // Get monthly summary
    public function getMonthlySummary() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $year = $_GET['year'] ?? null;
        $month = $_GET['month'] ?? null;

        $data = $this->analyticsModel->getMonthlySummary($user_id, $year, $month);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Get category trends
    public function getCategoryTrends() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $months = (int)($_GET['months'] ?? 6);

        $data = $this->analyticsModel->getCategoryTrends($user_id, $months);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Get month comparison
    public function getMonthComparison() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $data = $this->analyticsModel->getMonthComparison($user_id);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Get yearly summary
    public function getYearlySummary() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $year = (int)($_GET['year'] ?? date('Y'));

        $data = $this->analyticsModel->getYearlySummary($user_id, $year);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Get category statistics
    public function getCategoryStats() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $data = $this->analyticsModel->getCategoryStats($user_id);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Get spending forecast
    public function getSpendingForecast() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $data = $this->analyticsModel->getSpendingForecast($user_id);
        return $this->response(['success' => true, 'data' => $data]);
    }

    // Export to CSV
    public function exportCSV() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $data = $this->analyticsModel->exportToCSV($user_id, $startDate, $endDate);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, ['ID', 'Amount', 'Category', 'Description', 'Date', 'Created At']);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
