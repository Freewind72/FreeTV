<?php
if (!file_exists(dirname(__DIR__) . '/data.db')) {
    header('Location: ../install/index.php');
    exit;
}
session_start();
require_once '../api/common.php';
$db = db();

// 自动同步第一个注册用户为管理员
$adminUser = $db->query("SELECT uid, pwd FROM users ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($adminUser) {
    define('ADMIN_USER', $adminUser['uid']);
    define('ADMIN_PASS_HASH', $adminUser['pwd']);
} else {
    // 默认管理员账号密码
    define('ADMIN_USER', 'admin');
    define('ADMIN_PASS_HASH', password_hash('admin123!@#', PASSWORD_DEFAULT));
}

// 登录处理
if (isset($_POST['admin_user']) && isset($_POST['admin_pass'])) {
    if ($_POST['admin_user'] === ADMIN_USER && password_verify($_POST['admin_pass'], ADMIN_PASS_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $login_error = '账号或密码错误';
    }
}

// 登出处理
if (isset($_GET['logout'])) {
    $_SESSION['admin_logged_in'] = false;
    session_destroy();
    header('Location: index.php');
    exit;
}

// 用户删除处理
if (isset($_GET['del_user'])) {
    $id = intval($_GET['del_user']);
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    header('Location: index.php');
    exit;
}

// 未登录显示登录界面
if (empty($_SESSION['admin_logged_in'])):
?>
    <!DOCTYPE html>
    <html lang="zh">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
        <title>后台登录</title>
        <link rel="stylesheet" href="../assets/style.css">
        <style>
            body.dark {
                background: #181818;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                box-sizing: border-box;
            }
            .admin-login-box {
                width: 340px;  /* Slightly wider */
                margin: 0 auto;
                background: #232323;
                border-radius: 16px;
                box-shadow: 0 8px 32px #000a;
                padding: 40px;
                color: #fff;
                border: 1px solid #333;
                box-sizing: border-box;
            }
            .admin-login-box h2 {
                text-align: center;
                margin: 0 0 28px 0;
                font-weight: 500;
                font-size: 1.6em;
                color: #f0f0f0;
            }
            .admin-login-box input[type="text"],
            .admin-login-box input[type="password"] {
                width: 100%;  /* Full width of container */
                background: #111;
                color: #fff;
                border: 1px solid #333;
                border-radius: 8px;
                padding: 12px 16px;
                font-size: 1em;
                margin-bottom: 20px;
                font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
                transition: border-color 0.3s;
                box-sizing: border-box;  /* Include padding in width */
            }
            .admin-login-box input[type="text"]:focus,
            .admin-login-box input[type="password"]:focus {
                border-color: #666;
                outline: none;
            }
            .admin-login-box button {
                width: 100%;
                background: #3a3a3a;
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 12px 0;
                font-size: 1.1em;
                cursor: pointer;
                font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
                transition: all 0.3s;
                font-weight: 500;
                margin-top: 10px;
            }
            .admin-login-box button:hover {
                background: #4a4a4a;
            }
            .login-error {
                color: #ff6363;
                margin: -10px 0 15px 0;
                text-align: center;
                font-size: 0.95em;
                padding: 8px;
                background: rgba(255, 99, 99, 0.1);
                border-radius: 6px;
            }
            .admin-note {
                color: #888;
                font-size: 0.92em;
                margin-top: 20px;
                text-align: center;
                line-height: 1.5;
            }
            .login-form-group {
                margin-bottom: 5px;
            }
            .form-label {
                display: block;
                margin-bottom: 8px;
                font-size: 0.95em;
                color: #aaa;
            }
        </style>
    </head>
    <body class="dark">
    <div class="admin-login-box">
        <h2>后台登录</h2>
        <?php if (!empty($login_error)): ?>
            <div class="login-error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="login-form-group">
                <label class="form-label">管理员账号</label>
                <input type="text" name="admin_user" placeholder="输入管理员账号" required>
            </div>
            <div class="login-form-group">
                <label class="form-label">管理员密码</label>
                <input type="password" name="admin_pass" placeholder="输入管理员密码" required>
            </div>
            <button type="submit">登 录</button>

            <div class="admin-note">
                <?php if ($adminUser): ?>
                    <div style="margin-bottom: 8px;">管理员账号为第一个注册用户账号</div>
                    <div style="color: #6a6;"><strong><?php echo htmlspecialchars($adminUser['uid']); ?></strong></div>
                <?php else: ?>
                    <div>默认账号: <strong>admin</strong></div>
                    <div>默认密码: <strong>admin123!@#</strong></div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    </body>
    </html>

<?php
exit;
endif;

// 确保comments表有nick字段（自动升级表结构）
try {
    $db->exec("ALTER TABLE comments ADD COLUMN nick TEXT");
} catch (PDOException $e) {}

// 删除处理删除评论
if (isset($_GET['del_comment'])) {
    $id = intval($_GET['del_comment']);
    $db->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'subtitle', 'video', 'footer', 'popup'] as $k) {
        if (isset($_POST[$k])) {
            $stmt = $db->prepare("REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$k, $_POST[$k]]);
        }
    }
    header('Location: index.php?ok=1');
    exit;
}
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title>后台管理</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-table { width:100%; background:#222; color:#fff; border-radius:8px; }
        .admin-table th, .admin-table td { padding:8px; border-bottom:1px solid #333; }
        .admin-table th { background:#111; }
        .admin-table tr:last-child td { border-bottom:none; }
        .del-btn { color:#f44; cursor:pointer; }
        .admin-label { display:block; margin:12px 0 4px 0; font-weight:bold; }
        .admin-form input[type="text"],
        .admin-form textarea {
            background: #181a1b;
            color: #fff;
            font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 8px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 8px;
            transition: border-color 0.2s;
        }
        .admin-form input[type="text"]:focus,
        .admin-form textarea:focus {
            border-color: #555;
            outline: none;
        }
        .admin-form textarea[name="video"]::-webkit-scrollbar {
            display: none;
        }
        .admin-form textarea[name="video"] {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            resize: vertical;
        }
        .admin-form button {
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 28px;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 8px;
            font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
            transition: background 0.2s;
        }
        .admin-form button:hover {
            background: #666;
        }
        .admin-form {
            background: #232323;
            border-radius: 10px;
            padding: 24px 24px 12px 24px;
            margin-bottom: 32px;
            box-shadow: 0 2px 12px #0003;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .admin-form label {
            color: #bbb;
            font-weight: bold;
            margin-bottom: 4px;
            display: block;
        }
        @media (max-width: 600px) {
            .admin-pwd-col {
                display: none !important;
            }
        }
    </style>
</head>
<body class="dark">
    <div class="container">
        <div style="text-align:right;margin-bottom:10px;">
            <a href="?logout=1" style="color:#6cf;text-decoration:underline;">退出后台</a>
        </div>
        <h1>后台管理</h1>
        <form method="post" class="admin-form">
            <label class="admin-label">标题：
                <input type="text" name="title" value="<?php echo htmlspecialchars($settings['title']??''); ?>">
            </label>
            <label class="admin-label">副标题：
                <input type="text" name="subtitle" value="<?php echo htmlspecialchars($settings['subtitle']??''); ?>">
            </label>
            <label class="admin-label">视频代码或URL：
                <textarea name="video" rows="3"><?php echo htmlspecialchars($settings['video']??''); ?></textarea>
            </label>
            <label class="admin-label">页脚：
                <input type="text" name="footer" value="<?php echo htmlspecialchars($settings['footer']??''); ?>">
            </label>
            <label class="admin-label">弹窗内容（支持换行）：</label>
            <textarea name="popup" rows="4"><?php echo htmlspecialchars($settings['popup']??''); ?></textarea>
            <button type="submit">保存</button>
        </form>
        <h2>评论管理</h2>
        <table class="admin-table">
            <tr><th>ID</th><th>昵称</th><th>内容</th><th>操作</th></tr>
            <?php
            // 检查comments表是否有nick字段
            $cols = $db->query("PRAGMA table_info(comments)")->fetchAll(PDO::FETCH_ASSOC);
            $hasNick = false;
            foreach ($cols as $col) {
                if ($col['name'] === 'nick') $hasNick = true;
            }
            try {
                if ($hasNick) {
                    $stmt = $db->query("SELECT id, nick, text FROM comments ORDER BY id DESC LIMIT 30");
                    foreach($stmt as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nick']); ?></td>
                            <td><?php echo htmlspecialchars($row['text']); ?></td>
                            <td><a class="del-btn" href="?del_comment=<?php echo $row['id']; ?>" onclick="return confirm('确定删除?')">删除</a></td>
                        </tr>
                    <?php endforeach;
                } else {
                    foreach($db->query("SELECT id, text FROM comments ORDER BY id DESC LIMIT 30") as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>-</td>
                            <td><?php echo htmlspecialchars($row['text']); ?></td>
                            <td><a class="del-btn" href="?del_comment=<?php echo $row['id']; ?>" onclick="return confirm('确定删除?')">删除</a></td>
                        </tr>
                    <?php endforeach;
                }
            } catch (PDOException $e) {
                echo '<tr><td colspan="4" style="color:#f44">评论表不存在，已自动修复，请刷新页面。</td></tr>';
            }
            ?>
        </table>
        <h2>用户管理</h2>
        <table class="admin-table">
            <tr>
                <th>ID</th>
                <th>账号</th>
                <th>昵称</th>
                <th class="admin-pwd-col">密码</th>
                <th>注册IP</th>
                <th>操作</th>
            </tr>
            <?php
            // 用户面板
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uid TEXT UNIQUE,
                    pwd TEXT,
                    nick TEXT,
                    ip TEXT
                )");
                foreach($db->query("SELECT id, uid, nick, pwd, ip FROM users ORDER BY id DESC") as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['uid']); ?></td>
                        <td><?php echo htmlspecialchars($row['nick']); ?></td>
                        <td class="admin-pwd-col" style="font-family:monospace;"><?php echo htmlspecialchars($row['pwd']); ?></td>
                        <td><?php echo htmlspecialchars($row['ip']); ?></td>
                        <td>
                            <a class="del-btn" href="?del_user=<?php echo $row['id']; ?>" onclick="return confirm('确定删除该用户?')">删除</a>
                        </td>
                    </tr>
                <?php endforeach;
            } catch (PDOException $e) {
                echo '<tr><td colspan="6" style="color:#f44">用户表不存在。</td></tr>';
            }
            ?>
        </table>
    </div>
</body>
</html>
