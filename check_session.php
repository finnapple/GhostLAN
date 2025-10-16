<?php
session_start();

$response = array('valid' => true);

// Check if session exists and timestamp is valid
if (!isset($_SESSION['ghostlan_admin']) || !isset($_SESSION['login_time'])) {
    $response['valid'] = false;
} else {
    $current_timestamp = file_get_contents('session.txt');
    if ($_SESSION['login_time'] < $current_timestamp) {
        $response['valid'] = false;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
