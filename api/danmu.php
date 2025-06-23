<?php
require_once 'common.php';
header('Content-Type: application/json');
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    if ($text) {
        $stmt = $db->prepare("INSERT INTO danmu (text, created_at) VALUES (?, ?)");
        $stmt->execute([$text, time()]);
    }
    echo json_encode(['ok'=>1]);
    exit;
}
if ($_GET['action'] === 'list') {
    $since = intval($_GET['since'] ?? 0);
    $stmt = $db->prepare("SELECT id, text FROM danmu WHERE id > ? ORDER BY id ASC LIMIT 30");
    $stmt->execute([$since]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM danmu WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>1]);
    exit;
}
