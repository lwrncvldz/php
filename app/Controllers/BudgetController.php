<?php
require_once dirname(__FILE__) . '/../Models/Budget.php';

class BudgetController {
    private $db;
    private $budgetModel;

    public function __construct($db) {
        $this->db = $db;
        $this->budgetModel = new Budget($db);
    }

    // Get all budgets
    public function getAll() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $budgets = $this->budgetModel->getAll($user_id);
        return $this->response(['success' => true, 'data' => $budgets]);
    }

    // Create budget
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->budgetModel->user_id = $_SESSION['user_id'] ?? 1;
        $this->budgetModel->category = $data['category'] ?? '';
        $this->budgetModel->limit = $data['limit'] ?? 0;

        $result = $this->budgetModel->add();
        return $this->response($result);
    }

    // Update budget
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->budgetModel->id = $id;
        $this->budgetModel->limit = $data['limit'] ?? 0;

        if ($this->budgetModel->update()) {
            return $this->response(['success' => true, 'message' => 'Budget updated']);
        }
        return $this->response(['success' => false, 'message' => 'Update failed'], 400);
    }

    // Delete budget
    public function delete($id) {
        if ($this->budgetModel->delete($id)) {
            return $this->response(['success' => true, 'message' => 'Budget deleted']);
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
