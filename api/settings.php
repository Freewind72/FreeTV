<?php
require_once 'common.php';
header('Content-Type: application/json');
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'subtitle', 'video', 'footer', 'popup'] as $k) {
        if (isset($_POST[$k])) {
            $stmt = $db->prepare("REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$k, $_POST[$k]]);
        }
    }
    echo json_encode(['ok'=>1]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("SELECT key, value FROM settings");
    $arr = [];
    foreach ($stmt as $row) {
        $arr[$row['key']] = $row['value'];
    }
    echo json_encode($arr);
    exit;
}
