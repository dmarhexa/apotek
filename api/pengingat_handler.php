<?php
require_once '../config.php';
// session_start(); // Already started in config.php

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreate($conn, $user_id);
        break;
    case 'get_active':
        handleGetActive($conn, $user_id);
        break;
    case 'mark_taken':
        handleMarkTaken($conn, $user_id);
        break;
    case 'get_history':
        handleGetHistory($conn, $user_id);
        break;
    case 'delete':
        handleDelete($conn, $user_id);
        break;
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

function handleCreate($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nama_obat = mysqli_real_escape_string($conn, $data['nama_obat']);
    $dosis = mysqli_real_escape_string($conn, $data['dosis']);
    $catatan = mysqli_real_escape_string($conn, $data['catatan']);
    $waktu_array = $data['waktu']; // Array of time strings "HH:MM"

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into pengingat
        $query = "INSERT INTO pengingat (id_user, nama_obat, dosis, catatan) VALUES ('$user_id', '$nama_obat', '$dosis', '$catatan')";
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error inserting reminder");
        }
        $pengingat_id = mysqli_insert_id($conn);

        // Insert times
        foreach ($waktu_array as $waktu) {
            $waktu = mysqli_real_escape_string($conn, $waktu);
            $query_waktu = "INSERT INTO pengingat_waktu (id_pengingat, waktu) VALUES ('$pengingat_id', '$waktu')";
            if (!mysqli_query($conn, $query_waktu)) {
                throw new Exception("Error inserting time");
            }
        }

        mysqli_commit($conn);
        echo json_encode(['status' => 'success', 'message' => 'Reminder created']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function handleGetActive($conn, $user_id) {
    // Get all active reminders and their times
    $query = "
        SELECT p.id, p.nama_obat, p.dosis, p.catatan, pw.waktu, pw.id as id_waktu
        FROM pengingat p
        JOIN pengingat_waktu pw ON p.id = pw.id_pengingat
        WHERE p.id_user = '$user_id' AND p.is_active = 1
        ORDER BY pw.waktu ASC
    ";
    
    $result = mysqli_query($conn, $query);
    $reminders = [];
    
    // Check which ones are already taken TODAY
    $today = date('Y-m-d');
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if taken today
        $check_taken = "SELECT id FROM riwayat_minum 
                        WHERE id_pengingat = '{$row['id']}' 
                        AND waktu_dijadwalkan = '{$row['waktu']}' 
                        AND DATE(waktu_diminum) = '$today'";
        $taken_res = mysqli_query($conn, $check_taken);
        $is_taken = mysqli_num_rows($taken_res) > 0;
        
        $row['is_taken'] = $is_taken;
        $reminders[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $reminders]);
}

function handleMarkTaken($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_pengingat = mysqli_real_escape_string($conn, $data['id_pengingat']);
    $waktu_dijadwalkan = mysqli_real_escape_string($conn, $data['waktu_dijadwalkan']); // "HH:MM:SS"

    // Verify ownership
    $check = mysqli_query($conn, "SELECT id FROM pengingat WHERE id = '$id_pengingat' AND id_user = '$user_id'");
    if (mysqli_num_rows($check) == 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        return;
    }

    $query = "INSERT INTO riwayat_minum (id_pengingat, waktu_dijadwalkan) VALUES ('$id_pengingat', '$waktu_dijadwalkan')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Marked as taken']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

function handleGetHistory($conn, $user_id) {
    $query = "
        SELECT r.waktu_diminum, p.nama_obat, p.dosis, r.waktu_dijadwalkan
        FROM riwayat_minum r
        JOIN pengingat p ON r.id_pengingat = p.id
        WHERE p.id_user = '$user_id'
        ORDER BY r.waktu_diminum DESC
        LIMIT 20
    ";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
}

function handleDelete($conn, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_pengingat = mysqli_real_escape_string($conn, $data['id_pengingat']);

     // Verify ownership
     $check = mysqli_query($conn, "SELECT id FROM pengingat WHERE id = '$id_pengingat' AND id_user = '$user_id'");
     if (mysqli_num_rows($check) == 0) {
         http_response_code(403);
         echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
         return;
     }

     $query = "UPDATE pengingat SET is_active = 0 WHERE id = '$id_pengingat'";
     if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Reminder deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>
