<?php
require_once 'common.php';
session_start();
header('Content-Type: application/json');
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    $user = $_SESSION['user'] ?? null;
    // 修复：注册用户允许发言，nick允许为0或空字符串但uid存在
    $nick = $user['nick'] ?? '';
    $uid = $user['uid'] ?? '';
    if ($text && ($nick !== '' || $uid !== '')) {
        $stmt = $db->prepare("INSERT INTO comments (text, nick, created_at) VALUES (?, ?, ?)");
        $stmt->execute([$text, $nick, time()]);
    }
    echo json_encode(['ok'=>1]);
    exit;
}
if ($_GET['action'] === 'list') {
    $since = intval($_GET['since'] ?? 0);
    // 兼容老表结构
    $cols = $db->query("PRAGMA table_info(comments)")->fetchAll(PDO::FETCH_ASSOC);
    $hasNick = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'nick') $hasNick = true;
    }
    if ($hasNick) {
        $stmt = $db->prepare("SELECT id, text, nick FROM comments WHERE id > ? ORDER BY id ASC LIMIT 100");
    } else {
        $stmt = $db->prepare("SELECT id, text, '' as nick FROM comments WHERE id > ? ORDER BY id ASC LIMIT 100");
    }
    $stmt->execute([$since]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>1]);
    exit;
}
