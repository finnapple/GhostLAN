<?php
// Handles multiple file uploads from the chat UI
session_start();
header('Content-Type: application/json');

// Only allow logged-in admin
if (!isset($_SESSION['ghostlan_admin']) || $_SESSION['ghostlan_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = [
    'success' => false,
    'uploaded' => [],
    'errors' => []
];

if (!empty($_FILES['files']['name'][0])) {
    $total = count($_FILES['files']['name']);
    // Load or create uploads_meta.json
    $metaFile = __DIR__ . '/uploads_meta.json';
    $meta = [];
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
        if (!is_array($meta)) $meta = [];
    }
    // Use uploader from POST if available, else session username
    $uploader = isset($_POST['uploader']) && trim($_POST['uploader']) !== '' ? trim($_POST['uploader']) : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown');
    for ($i = 0; $i < $total; $i++) {
        $tmpName = $_FILES['files']['tmp_name'][$i];
        $name = basename($_FILES['files']['name'][$i]);
        $error = $_FILES['files']['error'][$i];
        $size = $_FILES['files']['size'][$i];
        
        if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
            // Prevent overwriting, add timestamp if file exists
            $target = $uploadDir . $name;
            if (file_exists($target)) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $base = pathinfo($name, PATHINFO_FILENAME);
                $name = $base . '_' . time() . ($ext ? ".{$ext}" : '');
                $target = $uploadDir . $name;
            }
            if (move_uploaded_file($tmpName, $target)) {
                $response['uploaded'][] = $name;
                // Save uploader info with microseconds
                $dt = new DateTime();
                $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                $meta[$name] = [
                    'uploader' => $uploader,
                    'time' => $dt->format('Y-m-d H:i:s.u'),
                ];
            } else {
                $response['errors'][] = "Failed to move $name.";
            }
        } else {
            $response['errors'][] = "Error uploading $name (error code $error).";
        }
    }
    // Save meta file
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
    $response['success'] = count($response['uploaded']) > 0;
} else {
    $response['errors'][] = 'No files uploaded.';
}

echo json_encode($response);
