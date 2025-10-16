<?php
$chatlog = "chatlog.txt";
$uploadsDir = __DIR__ . '/uploads/';
$uploadsMeta = __DIR__ . '/uploads_meta.json';

function getCombinedChatAndUploads($chatlog, $uploadsDir, $uploadsMeta) {
    $messages = [];

    // Load chat messages
    $chatLines = [];
    if (file_exists($chatlog)) {
        $lines = file($chatlog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $timestamp = extractTimestamp($line);
            $name = extractSenderName($line);
            $chatLines[] = ['line' => $line, 'timestamp' => $timestamp, 'name' => $name];
            $messages[] = ['type' => 'chat', 'content' => $line, 'timestamp' => $timestamp];
        }
    }

    // Load uploads metadata
    $meta = [];
    if (file_exists($uploadsMeta)) {
        $meta = json_decode(file_get_contents($uploadsMeta), true);
        if (!is_array($meta)) $meta = [];
    }

    // Load uploaded files
    if (is_dir($uploadsDir)) {
        $files = array_diff(scandir($uploadsDir), ['.', '..']);
        foreach ($files as $file) {
            $time = $meta[$file]['time'] ?? null;
            $timestamp = $time ? (
                DateTime::createFromFormat('Y-m-d H:i:s.u', $time) ? (float)DateTime::createFromFormat('Y-m-d H:i:s.u', $time)->format('U.u') : strtotime($time)
            ) : filemtime($uploadsDir . $file);

            // Use uploader from metadata if available
            $uploader = $meta[$file]['uploader'] ?? 'unknown';

            // If not in metadata, try to find from chatlog (as before)
            if ($uploader === 'unknown') {
                $latestTime = 0;
                $earliestAfterTime = PHP_INT_MAX;
                $earliestAfterName = '';
                foreach ($chatLines as $chat) {
                    if ($chat['timestamp'] <= $timestamp && $chat['timestamp'] > $latestTime) {
                        $latestTime = $chat['timestamp'];
                        $uploader = $chat['name'];
                    }
                    if ($chat['timestamp'] > $timestamp && $chat['timestamp'] < $earliestAfterTime) {
                        $earliestAfterTime = $chat['timestamp'];
                        $earliestAfterName = $chat['name'];
                    }
                }
                if ($uploader === 'unknown' && $earliestAfterName !== '') {
                    $uploader = $earliestAfterName;
                }
            }

            $messages[] = [
                'type' => 'upload',
                'file' => $file,
                'timestamp' => $timestamp,
                'uploader' => $uploader,
                'meta' => $meta[$file] ?? [],
            ];
        }
    }

    // Sort by time
    usort($messages, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

    // Debugging: Log sorted messages
    error_log("Sorted Messages:");
    foreach ($messages as $msg) {
        error_log("Type: " . $msg['type'] . ", Timestamp: " . $msg['timestamp']);
    }

    // Render messages
    $html = "<div class='combined-chat'>";
    foreach ($messages as $msg) {
        if ($msg['type'] === 'chat') {
            $html .= $msg['content'];
        } elseif ($msg['type'] === 'upload') {
            $file = $msg['file'];
            $safeFile = htmlspecialchars($file);
            $url = 'uploads/' . rawurlencode($file);
            $time = $msg['meta']['time'] ?? '';
            $uploader = htmlspecialchars($msg['uploader']);
            $timeDisplay = $time ? '[' . date('H:i', strtotime($time)) . ']' : '';

            $isImage = preg_match('/\.(png|jpe?g|gif)$/i', $file);
            $fileDisplay = $isImage
                ? "<a href='$url' target='_blank'><img src='$url' alt='$safeFile' style='max-width:120px;max-height:80px;vertical-align:middle;border:1px solid #ccc;margin-right:8px;'></a>"
                : "<a href='$url' target='_blank' style='font-family:inherit;color:#007700;text-decoration:underline;font-weight:bold;'>$safeFile</a>";

            $html .= "<div class='message-line'>
                        <span style='color:#000;font-family:inherit;font-weight:bold;'>$uploader </span>
                        <span style='color:#000;font-family:inherit;font-weight:normal;'>$timeDisplay</span>
                        <span style='color:#000;font-family:inherit;font-weight:bold;'>: </span>$fileDisplay
                      </div>";
        }
    }
    $html .= "</div>";

    return $html;
}

function extractTimestamp($line) {
    // Try to extract microsecond timestamp from hidden comment <!--ts:YYYY-MM-DD HH:MM:SS.u-->
    if (preg_match('/<!--ts:([0-9\-\s:.]+)-->/', $line, $m)) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s.u', $m[1]);
        if ($dt) return (float)$dt->format('U.u');
    }
    // Try to extract [HH:MM] anywhere in the line (fallback, no microseconds)
    if (preg_match('/\[(\d{2}):(\d{2})\]/', $line, $m)) {
        return strtotime(date('Y-m-d') . " {$m[1]}:{$m[2]}:00");
    }
    // Try to extract [HH:MM] even if there are extra brackets (e.g. [10 [10:08])
    if (preg_match('/\[(\d{2}):(\d{2})/', $line, $m)) {
        return strtotime(date('Y-m-d') . " {$m[1]}:{$m[2]}:00");
    }
    return 0;
}

function extractSenderName($line) {
    // 1. Try to extract from <strong> tag
    if (preg_match('/<strong>(.*?)<\/strong>/', $line, $m)) {
        return trim($m[1]);
    }
    // 2. Try to extract from HTML bold span (legacy)
    if (preg_match("/<span[^>]*font-weight:bold[^>]*>(.*?)<\/span>/", $line, $m)) {
        return trim(strip_tags($m[1]));
    }
    // 3. Try to extract from plain text: NAME [ or NAME:
    if (preg_match('/^([a-zA-Z0-9_\-]+)\s*\[/', $line, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/^([a-zA-Z0-9_\-]+)\s*:/', $line, $m)) {
        return trim($m[1]);
    }
    return 'unknown';
}

echo getCombinedChatAndUploads($chatlog, $uploadsDir, $uploadsMeta);
?>