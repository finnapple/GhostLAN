<?php
if (isset($_POST['name']) && isset($_POST['message'])) {
    $name = strip_tags($_POST['name']);
    $message = strip_tags($_POST['message']);
    $dt = new DateTime();
    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    $fullTime = $dt->format('Y-m-d H:i:s.u');
    $displayTime = $dt->format('H:i');
    $entry = "<p><strong>$name</strong> [$displayTime]: $message<!--ts:$fullTime--></p>\n";

    $file = "chatlog.txt";
    file_put_contents($file, $entry, FILE_APPEND);

    // Trim to last 100 lines
    $lines = file($file);
    if (count($lines) > 100) {
        $lines = array_slice($lines, -100);
        file_put_contents($file, implode("", $lines));
    }
}

header("Location: index.php");
exit;
?>
