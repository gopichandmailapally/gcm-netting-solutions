<?php
/**
 * GCM Netting Solutions - Database Connection Handler
 * Singleton pattern with prepared statements support
 */

// Prevent direct access
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class Database {
    private static $instance = null;
    private $connection;
    private $stmt;
    
    // Private constructor (Singleton pattern)
    private function __construct() {
        $this->connect();
    }
    
    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Establish database connection
    private function connect() {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            // Set charset
            $this->connection->set_charset(DB_CHARSET);
            
            // Set timezone
            $this->connection->query("SET time_zone = '+05:30'");
            
            if (DEBUG_MODE) {
                error_log("Database connected successfully");
            }
            
        } catch (mysqli_sql_exception $e) {
            // Fallback to SQLite for local testing
            error_log("MySQL connection failed, using SQLite fallback: " . $e->getMessage());
            if (file_exists(__DIR__ . '/database-local.php')) {
                require_once __DIR__ . '/database-local.php';
                // Use DatabaseLocal and create alias
                self::$instance = DatabaseLocal::getInstance();
                return;
            }
            
            if (DEBUG_MODE) {
                die("Database Connection Failed: " . $e->getMessage());
            } else {
                error_log("Database Connection Error: " . $e->getMessage());
                die("We're experiencing technical difficulties. Please try again later.");
            }
        }
    }
    
    // Get connection object
    public function getConnection() {
        return $this->connection;
    }
    
    // Prepare statement
    public function prepare($query) {
        $this->stmt = $this->connection->prepare($query);
        return $this->stmt;
    }
    
    // Execute query and return result
    public function query($sql) {
        try {
            $result = $this->connection->query($sql);
            return $result;
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    // Fetch single row
    public function fetchOne($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row;
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e);
            return null;
        }
    }
    
    // Fetch all rows
    public function fetchAll($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $rows;
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    // Execute insert/update/delete
    public function execute($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $insertId = $stmt->insert_id;
            $stmt->close();
            
            return [
                'success' => $success,
                'affected_rows' => $affected,
                'insert_id' => $insertId
            ];
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e);
            return [
                'success' => false,
                'affected_rows' => 0,
                'insert_id' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    // Get affected rows
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    // Escape string
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Handle errors
    private function handleError($e) {
        $error_msg = "Database Error: " . $e->getMessage();
        
        if (DEBUG_MODE) {
            error_log($error_msg);
            echo "<div style='background:#ff0000;color:#fff;padding:10px;'>";
            echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        } else {
            error_log($error_msg);
        }
    }
    
    // Close connection
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Destructor
    public function __destruct() {
        // Connection will be closed automatically
    }
}

// Create global database instance
$db = Database::getInstance()->getConnection();

// Helper function for quick access
function db() {
    return Database::getInstance();
}

// Helper function for prepared statements
function db_query($query, $params = [], $types = '') {
    return db()->fetchAll($query, $params, $types);
}

function db_query_one($query, $params = [], $types = '') {
    return db()->fetchOne($query, $params, $types);
}

function db_execute($query, $params = [], $types = '') {
    return db()->execute($query, $params, $types);
}
