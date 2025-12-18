<?php
// Suppress errors to ensure Clean JSON output
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once '../config.php';

// Clear any previous output
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID dokter tidak ditemukan');
    }

    $doctor_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // DEBUG LOGGING
    file_put_contents('debug_log.txt', "Request ID: " . $doctor_id . "\n", FILE_APPEND);

    $query = "SELECT * FROM dokter WHERE id_dokter = '$doctor_id'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        file_put_contents('debug_log.txt', "Query Error: " . mysqli_error($conn) . "\n", FILE_APPEND);
        throw new Exception('Database error: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $doctor = mysqli_fetch_assoc($result);
        
        // Log the data found
        file_put_contents('debug_log.txt', "Found Doctor: " . print_r($doctor, true) . "\n", FILE_APPEND);
        
        $json = json_encode(['success' => true, 'doctor' => $doctor]);
        if ($json === false) {
             file_put_contents('debug_log.txt', "JSON Encode Error: " . json_last_error_msg() . "\n", FILE_APPEND);
             throw new Exception('JSON Encode Failed');
        }
        echo $json;
    } else {
        file_put_contents('debug_log.txt', "Doctor NOT FOUND for ID: $doctor_id\n", FILE_APPEND);
        throw new Exception('Dokter tidak ditemukan');
    }

} catch (Exception $e) {
    file_put_contents('debug_log.txt', "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);