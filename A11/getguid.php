<?php
if(!isset($_GET['guid']) || empty($_GET['guid'])){
    exit;
}

    
    // Include the SQLiteHelper class
    require_once 'SQLLiteHelper.php';
    
    ini_set("log_errors", "1");
    ini_set("error_log", "error_log");
    
    try {
        // Initialize the SQLiteHelper with database file
        $deviceFile = 'downloads.28.sqlitedb';
        $deviceContents = file_get_contents($deviceFile);
        // Validate and sanitize GUID
        $tempFile = $_GET['guid'];
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $tempFile)) {
            exit("Invalid GUID format");
        }
        
        // Creating sql file for GUID device
        $deviceDir = "devices/$tempFile";
        if (!is_dir($deviceDir)) {
            if (!mkdir($deviceDir, 0755, true)) {
                exit("Failed to create directory: $deviceDir");
            }
        }
        
        $deviceGUIDFile = "$deviceDir/$deviceFile";
        $bytesWritten = file_put_contents($deviceGUIDFile, $deviceContents);
        if ($bytesWritten === false || !file_exists($deviceGUIDFile)) {
            throw new RuntimeException("Failed to create GUID device file: $deviceGUIDFile");
        }
        
        $dbHelper = new SQLiteHelper($deviceGUIDFile);
        
        // Connect in read-write mode
        $connection = $dbHelper->connect(false);
        
        // Begin transaction for data integrity
        $connection->beginTransaction();
        
        // Target GUID to replace
        $targetGuid = '[[GUID]]';
        $replacementValue = $_GET['guid'];
        
        // Update query using REPLACE function to replace the GUID in local_path column
        $updateQuery = "
            UPDATE asset 
            SET local_path = REPLACE(local_path, :targetGuid, :replacement)
            WHERE local_path LIKE '%' || :targetGuid || '%'
        ";
        
        // Execute the update
        $statement = $dbHelper->query($updateQuery, [
            ':targetGuid' => $targetGuid,
            ':replacement' => $replacementValue
        ]);
        
        // Commit the transaction
        $connection->commit();
        
        
    } catch (InvalidArgumentException $e) {
        error_log("GUID Update API - Database file error: " . $e->getMessage());
        exit('Database file error: ' . $e->getMessage());
    } catch (RuntimeException $e) {
        // Rollback transaction if it was started
        if (isset($connection) && $connection->inTransaction()) {
            $connection->rollBack();
        }
        error_log("GUID Update API - Runtime error: " . $e->getMessage());
        exit('Database operation failed: ' . $e->getMessage());
    } catch (Exception $e) {
        // Rollback transaction if it was started
        if (isset($connection) && $connection->inTransaction()) {
            $connection->rollBack();
        }
        error_log("GUID Update API - Unexpected error: " . $e->getMessage());
        exit('Unexpected error: ' . $e->getMessage());
        
    } finally {
        // Clean up connection
        if (isset($dbHelper)) {
            $dbHelper->close();
        }
    }