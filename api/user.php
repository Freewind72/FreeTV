<?php
require_once 'common.php';
session_start();
header('Content-Type: application/json');
$db = db();

// 初始化user表
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uid TEXT UNIQUE,
    pwd TEXT,
    nick TEXT,
    ip TEXT
)");

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'register') {
    $uid = trim($_POST['uid'] ?? '');
    $pwd = $_POST['pwd'] ?? '';
    $nick = trim($_POST['nick'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!preg_match('/^\d+$/', $uid)) {
        echo json_encode(['ok'=>0, 'msg'=>'账号必须为纯数字']); exit;
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,32}$/', $pwd)) {
        echo json_encode(['ok'=>0, 'msg'=>'密码需包含符号、英文大小写和数字，长度6-32']); exit;
    }
    if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]{2,16}$/u', $nick)) {
        echo json_encode(['ok'=>0, 'msg'=>'昵称仅限汉字、英文、数字，2-16位']); exit;
    }
    // 检查账号是否已存在
    $stmt = $db->prepare("SELECT 1 FROM users WHERE uid=?");
    $stmt->execute([$uid]);
    if ($stmt->fetch()) {
        echo json_encode(['ok'=>0, 'msg'=>'账号已存在']); exit;
    }
    // 密码加密
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (uid, pwd, nick, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$uid, $hash, $nick, $ip]);
    echo json_encode(['ok'=>1]);
    exit;
}
if ($action === 'login') {
    $uid = trim($_POST['uid'] ?? '');
    $pwd = $_POST['pwd'] ?? '';
    $stmt = $db->prepare("SELECT * FROM users WHERE uid=?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($pwd, $user['pwd'])) {
        $_SESSION['user'] = [
            'uid' => $user['uid'],
            'nick' => $user['nick']
        ];
        echo json_encode(['ok'=>1, 'nick'=>$user['nick']]);
    } else {
        echo json_encode(['ok'=>0, 'msg'=>'账号或密码错误']);
    }
    exit;
}
if ($action === 'logout') {
    $_SESSION['user'] = null;
    session_destroy();
    echo json_encode(['ok'=>1]);
    exit;
}
if ($action === 'session') {
    if (!empty($_SESSION['user'])) {
        echo json_encode(['ok'=>1, 'user'=>$_SESSION['user']]);
    } else {
        echo json_encode(['ok'=>0]);
    }
    exit;
}
echo json_encode(['ok'=>0, 'msg'=>'非法请求']);
