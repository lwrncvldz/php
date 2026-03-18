<?php
require_once dirname(__FILE__) . '/../Models/RecurringExpense.php';

class RecurringExpenseController {
    private $db;
    private $recurringModel;

    public function __construct($db) {
        $this->db = $db;
        $this->recurringModel = new RecurringExpense($db);
    }

    // Get all recurring expenses
    public function getAll() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $expenses = $this->recurringModel->getAll($user_id);
        return $this->response(['success' => true, 'data' => $expenses]);
    }

    // Create recurring expense
    public function create() {
        $user_id = $_SESSION['user_id'] ?? 1;
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->recurringModel->user_id = $user_id;
        $this->recurringModel->amount = $data['amount'] ?? 0;
        $this->recurringModel->category = $data['category'] ?? '';
        $this->recurringModel->description = $data['description'] ?? '';
        $this->recurringModel->frequency = $data['frequency'] ?? 'monthly';
        $this->recurringModel->start_date = $data['start_date'] ?? date('Y-m-d');
        $this->recurringModel->end_date = $data['end_date'] ?? null;
        $this->recurringModel->active = 1;

        $result = $this->recurringModel->add();
        return $this->response($result);
    }

    // Update recurring expense
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->recurringModel->id = $id;
        $this->recurringModel->amount = $data['amount'] ?? 0;
        $this->recurringModel->category = $data['category'] ?? '';
        $this->recurringModel->description = $data['description'] ?? '';
        $this->recurringModel->frequency = $data['frequency'] ?? 'monthly';
        $this->recurringModel->end_date = $data['end_date'] ?? null;
        $this->recurringModel->active = $data['active'] ?? 1;

        if ($this->recurringModel->update()) {
            return $this->response(['success' => true, 'message' => 'Updated']);
        }
        return $this->response(['success' => false, 'message' => 'Update failed'], 400);
    }

    // Delete recurring expense
    public function delete($id) {
        if ($this->recurringModel->delete($id)) {
            return $this->response(['success' => true, 'message' => 'Deleted']);
        }
        return $this->response(['success' => false, 'message' => 'Delete failed'], 400);
    }

    // Process all recurring expenses
    public function process() {
        $count = $this->recurringModel->processRecurring();
        return $this->response(['success' => true, 'message' => "Processed $count recurring expenses"]);
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
