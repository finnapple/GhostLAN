<?php
// delete.php
// Clear chatlog and delete all uploads and metadata
$chatlog = 'chatlog.txt';
$uploadsDir = __DIR__ . '/uploads/';
$uploadsMeta = __DIR__ . '/uploads_meta.json';

// Delete chatlog
if (file_exists($chatlog)) {
    file_put_contents($chatlog, '');
}

// Delete all files in uploads directory
if (is_dir($uploadsDir)) {
    $files = array_diff(scandir($uploadsDir), array('.', '..'));
    foreach ($files as $file) {
        $filePath = $uploadsDir . $file;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}

// Delete uploads metadata
if (file_exists($uploadsMeta)) {
    unlink($uploadsMeta);
}

echo 'OK';
?>
