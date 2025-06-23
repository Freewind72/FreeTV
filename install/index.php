<?php
$dbfile = dirname(__DIR__) . '/data.db';

// 检查数据库是否已存在
if (file_exists($dbfile)) {
    header('Location: ../index.php');
    exit;
}

// 未安装则自动安装并跳转
$db = new PDO('sqlite:' . $dbfile);
$db->exec("CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)");
$db->exec("CREATE TABLE comments (id INTEGER PRIMARY KEY AUTOINCREMENT, text TEXT, nick TEXT, created_at INTEGER)");
$db->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uid TEXT UNIQUE,
    pwd TEXT,
    nick TEXT,
    ip TEXT
)");
$db->exec("INSERT INTO settings (key, value) VALUES 
    ('title', '视频放映厅'), 
    ('subtitle', ''), 
    ('video', ''), 
    ('footer', '© 视频放映厅'),
    ('popup', '')
");
header('Location: ../index.php');
exit;
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title>安装数据库</title>
</head>
<body>
<!-- 页面不会显示，自动跳转 -->
</body>
</html>
