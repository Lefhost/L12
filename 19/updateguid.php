<?php

declare(strict_types=1);

// Include the SQLiteHelper class
require_once 'SQLLiteHelper.php';

// Set content type for API response
header('Content-Type: application/json');

// Response array
$response = [
    'success' => false,
    'message' => '',
    'updated_rows' => 0
];

try {
    // Initialize the SQLiteHelper with database file
    $dbHelper = new SQLiteHelper('downloads.28.sqlitedb');
    
    // Connect in read-write mode
    $connection = $dbHelper->connect(false);
    
    // Begin transaction for data integrity
    $connection->beginTransaction();
    
    // Target GUID to replace
    $targetGuid = 'DF0129BD-7E63-4AB5-B8E2-DF961F5EE08A';
    $replacementValue = '[[GUID]]';
    
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
    
    // Get number of affected rows
    $updatedRows = $statement->rowCount();
    
    // Commit the transaction
    $connection->commit();
    
    // Set success response
    $response['success'] = true;
    $response['message'] = "Successfully updated GUID in local_path column";
    $response['updated_rows'] = $updatedRows;
    
} catch (InvalidArgumentException $e) {
    $response['message'] = 'Database file error: ' . $e->getMessage();
    error_log("GUID Update API - Database file error: " . $e->getMessage());
    
} catch (RuntimeException $e) {
    // Rollback transaction if it was started
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    $response['message'] = 'Database operation failed: ' . $e->getMessage();
    error_log("GUID Update API - Runtime error: " . $e->getMessage());
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    $response['message'] = 'Unexpected error: ' . $e->getMessage();
    error_log("GUID Update API - Unexpected error: " . $e->getMessage());
    
} finally {
    // Clean up connection
    if (isset($dbHelper)) {
        $dbHelper->close();
    }
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
exit;