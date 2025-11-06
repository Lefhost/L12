<?php

/**
 * SQLite Database Helper Class
 * Provides a consistent interface for SQLite database connections
 * 
 * @author Your Name
 * @version 1.1
 * @since 2025-10-21
 */

declare(strict_types=1);

ini_set("log_errors", "1");
ini_set("error_log", "error_log");

class SQLiteHelper 
{
    private string $databaseFile;
    private ?PDO $connection = null;
    
    /**
     * Constructor
     * 
     * @param string $databaseFile Path to SQLite database file
     * @throws InvalidArgumentException If database file doesn't exist
     */
    public function __construct(string $databaseFile) 
    {
        if (!file_exists($databaseFile)) {
            throw new InvalidArgumentException("Database file does not exist: {$databaseFile}");
        }
        
        if (!is_readable($databaseFile)) {
            throw new InvalidArgumentException("Database file is not readable: {$databaseFile}");
        }
        
        $this->databaseFile = $databaseFile;
    }
    
    /**
     * Establish connection to SQLite database
     * Uses PDO for consistent interface and better security
     * 
     * @param bool $readOnly Whether to open database in read-only mode (default: true)
     * @return PDO Database connection object
     * @throws RuntimeException If connection fails
     */
    public function connect(bool $readOnly = true): PDO 
    {
        try {
            if ($this->connection !== null) {
                return $this->connection;
            }
            
            if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
                throw new RuntimeException('PDO SQLite extension is not available');
            }
            
            $dsn = "sqlite:{$this->databaseFile}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, null, null, $options);
            
            // Set read-only mode if requested
            if ($readOnly) {
                $this->connection->exec('PRAGMA query_only = ON');
            }
            
            return $this->connection;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new RuntimeException("Failed to connect to database: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a prepared query with parameters
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for the query
     * @return PDOStatement
     * @throws RuntimeException If query execution fails
     */
    public function query(string $query, array $params = []): PDOStatement
    {
        try {
            $connection = $this->connect();
            $statement = $connection->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch all results from a query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $statement = $this->query($query, $params);
        return $statement->fetchAll();
    }
    
    /**
     * Fetch single row from a query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|false
     */
    public function fetchOne(string $query, array $params = [])
    {
        $statement = $this->query($query, $params);
        return $statement->fetch();
    }
    
    /**
     * Get database file path
     * 
     * @return string
     */
    public function getDatabaseFile(): string
    {
        return $this->databaseFile;
    }
    
    /**
     * Close database connection
     */
    public function close(): void
    {
        $this->connection = null;
    }
    
    /**
     * Destructor - ensures connection is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}