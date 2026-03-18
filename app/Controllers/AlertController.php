<?php
require_once dirname(__FILE__) . '/../Models/Alert.php';

class AlertController {
    private $db;
    private $alertModel;

    public function __construct($db) {
        $this->db = $db;
        $this->alertModel = new Alert($db);
    }

    // Get all alerts
    public function getAll() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $alerts = $this->alertModel->getAll($user_id);
        return $this->response(['success' => true, 'data' => $alerts]);
    }

    // Get unread alerts
    public function getUnread() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $alerts = $this->alertModel->getAll($user_id, true);
        return $this->response(['success' => true, 'data' => $alerts, 'count' => count($alerts)]);
    }

    // Mark alert as read
    public function markAsRead($id) {
        if ($this->alertModel->markAsRead($id)) {
            return $this->response(['success' => true, 'message' => 'Marked as read']);
        }
        return $this->response(['success' => false, 'message' => 'Failed'], 400);
    }

    // Mark all as read
    public function markAllAsRead() {
        $user_id = $_SESSION['user_id'] ?? 1;
        if ($this->alertModel->markAllAsRead($user_id)) {
            return $this->response(['success' => true, 'message' => 'All marked as read']);
        }
        return $this->response(['success' => false, 'message' => 'Failed'], 400);
    }

    // Check budgets
    public function checkBudgets() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $count = $this->alertModel->checkBudgets($user_id);
        return $this->response(['success' => true, 'alerts_created' => $count]);
    }

    // Delete alert
    public function delete($id) {
        if ($this->alertModel->delete($id)) {
            return $this->response(['success' => true, 'message' => 'Deleted']);
        }
        return $this->response(['success' => false, 'message' => 'Delete failed'], 400);
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
