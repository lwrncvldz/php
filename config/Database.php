<?php

class Database {
    // ==========================================
    // DEPLOYMENT SETTINGS
    // Change these to your live database details properly provided by your host (cPanel, Hostinger, etc.)
    // ==========================================
    private $host = 'localhost';          // Usually 'localhost', but sometimes an IP or URL
    private $dbName = 'expense_tracker';  // Your live database name
    private $dbUser = 'root';             // Your live database username
    private $dbPassword = '';             // Your live database password
    private $connection;

    public function connect() {
        $this->connection = null;

        try {
            $this->connection = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->dbName,
                $this->dbUser,
                $this->dbPassword
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->connection;
    }
}
