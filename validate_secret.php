<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredKey = $_POST['secretKey'] ?? '';

    // Read the secret key from the file
    $secretKey = trim(file_get_contents('secret.txt'));

    // Validate the entered key
    if ($enteredKey === $secretKey) {
        echo json_encode(['valid' => true]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit;
}
?>