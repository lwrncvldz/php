<?php
require_once dirname(__FILE__) . '/../Models/Expense.php';

class ExpenseController {
    private $db;
    private $expenseModel;

    public function __construct($db) {
        $this->db = $db;
        $this->expenseModel = new Expense($db);
    }

    // Get all expenses
    public function getAll() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $expenses = $this->expenseModel->getAll($user_id);
        return $this->response(['success' => true, 'data' => $expenses]);
    }

    // Get single expense
    public function getById($id) {
        $expense = $this->expenseModel->getById($id);
        if ($expense) {
            return $this->response(['success' => true, 'data' => $expense]);
        }
        return $this->response(['success' => false, 'message' => 'Expense not found'], 404);
    }

    // Create expense
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->expenseModel->user_id = $_SESSION['user_id'] ?? 1;
        $this->expenseModel->amount = $data['amount'] ?? 0;
        $this->expenseModel->category = $data['category'] ?? '';
        $this->expenseModel->description = $data['description'] ?? '';
        $this->expenseModel->date = $data['date'] ?? date('Y-m-d');

        $result = $this->expenseModel->add();
        return $this->response($result);
    }

    // Update expense
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->expenseModel->id = $id;
        $this->expenseModel->amount = $data['amount'] ?? 0;
        $this->expenseModel->category = $data['category'] ?? '';
        $this->expenseModel->description = $data['description'] ?? '';
        $this->expenseModel->date = $data['date'] ?? date('Y-m-d');

        if ($this->expenseModel->update()) {
            return $this->response(['success' => true, 'message' => 'Expense updated']);
        }
        return $this->response(['success' => false, 'message' => 'Update failed'], 400);
    }

    // Delete expense
    public function delete($id) {
        if ($this->expenseModel->delete($id)) {
            return $this->response(['success' => true, 'message' => 'Expense deleted']);
        }
        return $this->response(['success' => false, 'message' => 'Delete failed'], 400);
    }

    // Get totals by category
    public function getTotalsByCategory() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $totals = $this->expenseModel->getTotalByCategory($user_id);
        return $this->response(['success' => true, 'data' => $totals]);
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
