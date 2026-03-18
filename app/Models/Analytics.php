<?php

class Analytics {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get monthly summary
    public function getMonthlySummary($user_id, $year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');

        $monthStr = sprintf("%04d-%02d", $year, $month);

        $query = "SELECT 
                    DATE_FORMAT(date, '%Y-%m-%d') as date,
                    category,
                    SUM(amount) as daily_total,
                    COUNT(*) as transaction_count
                  FROM expenses
                  WHERE user_id = :user_id
                  AND DATE_FORMAT(date, '%Y-%m') = :month
                  GROUP BY date, category
                  ORDER BY date DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':month', $monthStr);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get category trends
    public function getCategoryTrends($user_id, $months = 6) {
        $query = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    category,
                    SUM(amount) as total,
                    COUNT(*) as count,
                    AVG(amount) as average
                  FROM expenses
                  WHERE user_id = :user_id
                  AND date >= DATE_SUB(NOW(), INTERVAL :months MONTH)
                  GROUP BY month, category
                  ORDER BY month DESC, total DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get comparison report (current month vs previous month)
    public function getMonthComparison($user_id) {
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));

        $query = "SELECT 
                    CASE 
                        WHEN DATE_FORMAT(date, '%Y-%m') = :current THEN 'Current'
                        WHEN DATE_FORMAT(date, '%Y-%m') = :previous THEN 'Previous'
                    END as period,
                    category,
                    SUM(amount) as total,
                    COUNT(*) as count
                  FROM expenses
                  WHERE user_id = :user_id
                  AND (DATE_FORMAT(date, '%Y-%m') = :current 
                       OR DATE_FORMAT(date, '%Y-%m') = :previous)
                  GROUP BY period, category
                  ORDER BY period DESC, total DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':current', $currentMonth);
        $stmt->bindParam(':previous', $previousMonth);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get yearly summary
    public function getYearlySummary($user_id, $year = null) {
        if (!$year) $year = date('Y');

        $query = "SELECT 
                    DATE_FORMAT(date, '%m') as month,
                    DATE_FORMAT(date, '%b') as month_name,
                    category,
                    SUM(amount) as total,
                    COUNT(*) as transactions
                  FROM expenses
                  WHERE user_id = :user_id
                  AND YEAR(date) = :year
                  GROUP BY MONTH(date), category
                  ORDER BY MONTH(date) ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get category statistics
    public function getCategoryStats($user_id) {
        $query = "SELECT 
                    category,
                    COUNT(*) as transactions,
                    SUM(amount) as total,
                    AVG(amount) as average,
                    MIN(amount) as minimum,
                    MAX(amount) as maximum,
                    DATE_FORMAT(MAX(date), '%Y-%m-%d') as last_expense
                  FROM expenses
                  WHERE user_id = :user_id
                  GROUP BY category
                  ORDER BY total DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get spending forecast
    public function getSpendingForecast($user_id) {
        // Calculate average daily spending
        $query = "SELECT 
                    ROUND(SUM(amount) / DATEDIFF(MAX(date), MIN(date)) + 1, 2) as daily_average,
                    DAY(LAST_DAY(NOW())) as days_in_month,
                    ROUND(SUM(amount) / DATEDIFF(MAX(date), MIN(date)) + 1 * DAY(LAST_DAY(NOW())), 2) as forecast
                  FROM expenses
                  WHERE user_id = :user_id
                  AND date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Export to CSV
    public function exportToCSV($user_id, $startDate, $endDate) {
        $query = "SELECT 
                    id,
                    amount,
                    category,
                    description,
                    date,
                    created_at
                  FROM expenses
                  WHERE user_id = :user_id
                  AND date BETWEEN :start AND :end
                  ORDER BY date DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':start', $startDate);
        $stmt->bindParam(':end', $endDate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
