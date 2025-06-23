<?php
if (!file_exists(__DIR__ . '/data.db')) {
    header('Location: install/index.php');
    exit;
}
session_start();
require_once __DIR__ . '/api/common.php';
$settings = get_settings();
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title><?php echo htmlspecialchars($settings['title'] ?? '视频放映厅'); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* 隐藏本地视频的进度条 */
        video::-webkit-media-controls-timeline {
            display: none !important;
        }
        video::-webkit-media-controls-current-time-display,
        video::-webkit-media-controls-time-remaining-display {
            display: none !important;
        }
        .auth-btns {
            position: absolute;
            top: 18px;
            right: 24px;
            z-index: 2;
        }
        .auth-btns button {
            background: #333;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            margin-left: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .auth-btns button:hover {
            background: #555;
        }
        .auth-modal-mask {
            position: fixed;
            z-index: 10001;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: popup-mask-fadein 0.3s;
        }
        .auth-modal {
            width: 340px;
            min-height: 320px;
            background: #232323;
            border-radius: 12px;
            box-shadow: 0 4px 32px #000a;
            position: relative;
            perspective: 800px;
            font-family: 'Segoe UI', Arial, sans-serif;
            /* 修正弹窗内容溢出导致的位移 */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-close {
            position: absolute;
            right: 18px;
            top: 12px;
            font-size: 1.5em;
            color: #aaa;
            cursor: pointer;
            font-weight: bold;
            z-index: 2;
        }
        .auth-flip {
            width: 100%;
            min-height: 320px;
            height: auto;
            transition: transform 0.5s cubic-bezier(.4,1.6,.6,1);
            transform-style: preserve-3d;
            position: relative;
        }
        .auth-modal.flipped .auth-flip {
            transform: rotateY(180deg);
        }
        .auth-form {
            position: absolute;
            width: 100%;
            min-height: 320px;
            height: auto;
            top: 0; left: 0;
            backface-visibility: hidden;
            padding: 36px 28px 24px 28px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-form input {
            background: #181a1b;
            color: #fff;
            font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 8px;
            font-size: 1em;
            margin-bottom: 14px;
        }
        .auth-form input:focus {
            border-color: #555;
            outline: none;
        }
        .auth-form button {
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 0;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 8px;
            font-family: 'Microsoft YaHei', '黑体', 'Arial', sans-serif;
            transition: background 0.2s;
        }
        .auth-form button:hover {
            background: #666;
        }
        .auth-form .auth-switch {
            background: none;
            color: #6cf;
            border: none;
            margin-top: 10px;
            cursor: pointer;
            font-size: 0.95em;
            text-decoration: underline;
        }
        .auth-form .auth-error {
            color: #ff6666;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        .auth-form.register {
            transform: rotateY(180deg);
        }
        /* 保证登录和注册表单高度一致，避免翻转时位移 */
        .auth-form input,
        .auth-form button,
        .auth-form .auth-switch,
        .auth-form .auth-error {
            min-width: 0;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="dark">
<div class="container">
    <header>
        <h1><?php echo htmlspecialchars($settings['title'] ?? '视频放映厅'); ?></h1>
        <?php if (!empty($settings['subtitle'])): ?>
            <h3 class="subtitle"><?php echo htmlspecialchars($settings['subtitle']); ?></h3>
        <?php endif; ?>
    </header>
    <main>
        <div class="video-area">
            <div class="video-wrapper">
                <?php
                $video = $settings['video'] ?? '';
                if (preg_match('/<iframe/i', $video)) {
                    if (preg_match('/youtube\.com|youtu\.be/i', $video)) {
                        $video = preg_replace('/(src="[^"]+)/i', '$1&autoplay=1&loop=1&playlist='.preg_replace('/.*(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', '$1', $video), $video);
                    } elseif (preg_match('/vimeo\.com/i', $video)) {
                        $video = preg_replace('/(src="[^"]+)/i', '$1&autoplay=1&loop=1', $video);
                    }
                    $video = preg_replace('/\s(width|height|allow|style|frameborder|referrerpolicy|title)="[^"]*"/i', '', $video);
                    $video = preg_replace('/<iframe/i', '<iframe allow="autoplay; fullscreen" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"', $video);
                    echo $video;
                } else {
                    echo '<video src="' . htmlspecialchars($video) . '" controls controlsList="nodownload" oncontextmenu="return false;" autoplay loop playsinline style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;"></video>';
                }
                ?>
                <div id="danmu-layer"></div>
            </div>
            <form id="danmu-form">
                <input type="text" id="danmu-input" maxlength="50" placeholder="发送弹幕...">
                <button type="submit">发送</button>
            </form>
        </div>
        <section class="comments-section" style="position:relative;">
            <h2 style="margin-right:120px;">讨论区</h2>
            <div class="auth-btns">
                <?php if ($user): ?>
                    <span style="color:#6cf;margin-right:10px;">欢迎，<?php echo htmlspecialchars($user['nick']); ?></span>
                    <button type="button" id="logout-btn">退出登录</button>
                <?php else: ?>
                    <button type="button" id="login-btn">登录</button>
                    <button type="button" id="register-btn">注册</button>
                <?php endif; ?>
            </div>
            <div id="comments-list"></div>
            <form id="comment-form">
                <input type="text" id="comment-input" maxlength="100" placeholder="发表评论...">
                <button type="submit">发送</button>
            </form>
        </section>
    </main>
    <footer>
        <small><?php echo htmlspecialchars($settings['footer'] ?? '© 视频放映厅'); ?></small>
    </footer>
</div>
<?php if (!empty($settings['popup'])): ?>
    <div class="popup-mask" id="popup-mask">
        <div class="popup-content" id="popup-content">
            <?php echo nl2br(htmlspecialchars($settings['popup'])); ?>
        </div>
    </div>
    <script>
        document.getElementById('popup-mask').onclick = function(e) {
            if (e.target === this) this.style.display = 'none';
        };
    </script>
<?php endif; ?>
<!-- 登录/注册弹窗 -->
<div id="auth-modal-mask" class="auth-modal-mask" style="display:none;">
    <div class="auth-modal" id="auth-modal">
        <span class="auth-close" id="auth-close">&times;</span>
        <div class="auth-flip" id="auth-flip">
            <!-- 登录表单 -->
            <form class="auth-form login" id="login-form" autocomplete="off">
                <div class="auth-error" id="login-error"></div>
                <input type="text" id="login-uid" maxlength="20" placeholder="账号(纯数字)">
                <input type="password" id="login-pwd" maxlength="32" placeholder="密码">
                <button type="submit">登录</button>
                <button type="button" class="auth-switch" id="to-register">没有账号？注册</button>
            </form>
            <!-- 注册表单 -->
            <form class="auth-form register" id="register-form" autocomplete="off">
                <div class="auth-error" id="register-error"></div>
                <input type="text" id="reg-nick" maxlength="16" placeholder="昵称(汉字/英文/数字)">
                <input type="text" id="reg-uid" maxlength="20" placeholder="账号(纯数字)">
                <input type="password" id="reg-pwd" maxlength="32" placeholder="密码(符号+英文大小写+数字)">
                <button type="submit">注册</button>
                <button type="button" class="auth-switch" id="to-login">已有账号？登录</button>
            </form>
        </div>
    </div>
</div>
<script src="assets/main.js"></script>
<script>
    <?php if ($user): ?>
    // 退出登录
    document.getElementById('logout-btn').onclick = function() {
        fetch('api/user.php', {
            method: 'POST',
            body: new URLSearchParams({action:'logout'})
        }).then(()=>location.reload());
    };
    <?php else: ?>
    // 登录/注册弹窗逻辑
    document.getElementById('login-btn').onclick = function(e) {
        e.preventDefault();
        document.getElementById('auth-modal-mask').style.display = 'flex';
        document.getElementById('auth-modal').classList.remove('flipped');
        document.getElementById('login-error').textContent = '';
    };
    document.getElementById('register-btn').onclick = function(e) {
        e.preventDefault();
        document.getElementById('auth-modal-mask').style.display = 'flex';
        document.getElementById('auth-modal').classList.add('flipped');
        document.getElementById('register-error').textContent = '';
    };
    document.getElementById('auth-close').onclick = function() {
        document.getElementById('auth-modal-mask').style.display = 'none';
    };
    document.getElementById('to-register').onclick = function() {
        document.getElementById('auth-modal').classList.add('flipped');
        document.getElementById('register-error').textContent = '';
    };
    document.getElementById('to-login').onclick = function() {
        document.getElementById('auth-modal').classList.remove('flipped');
        document.getElementById('login-error').textContent = '';
    };
    document.getElementById('auth-modal-mask').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };

    // 登录表单校验与提交
    document.getElementById('login-form').onsubmit = function(e) {
        e.preventDefault();
        const uid = document.getElementById('login-uid').value.trim();
        const pwd = document.getElementById('login-pwd').value;
        const err = document.getElementById('login-error');
        if (!/^\d+$/.test(uid)) {
            err.textContent = '账号必须为纯数字';
            return;
        }
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,32}$/.test(pwd)) {
            err.textContent = '密码需包含符号、英文大小写和数字，长度6-32';
            return;
        }
        fetch('api/user.php', {
            method: 'POST',
            body: new URLSearchParams({action:'login', uid, pwd})
        }).then(r=>r.json()).then(res=>{
            if(res.ok){
                err.textContent = '';
                document.getElementById('auth-modal-mask').style.display = 'none';
                location.reload();
            }else{
                err.textContent = res.msg || '账号或密码错误';
            }
        });
    };

    // 注册表单校验与提交
    document.getElementById('register-form').onsubmit = function(e) {
        e.preventDefault();
        const nick = document.getElementById('reg-nick').value.trim();
        const uid = document.getElementById('reg-uid').value.trim();
        const pwd = document.getElementById('reg-pwd').value;
        const err = document.getElementById('register-error');
        if (!/^[\u4e00-\u9fa5a-zA-Z0-9]{2,16}$/.test(nick)) {
            err.textContent = '昵称仅限汉字、英文、数字，2-16位';
            return;
        }
        if (!/^\d+$/.test(uid)) {
            err.textContent = '账号必须为纯数字';
            return;
        }
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,32}$/.test(pwd)) {
            err.textContent = '密码需包含符号、英文大小写和数字，长度6-32';
            return;
        }
        fetch('api/user.php', {
            method: 'POST',
            body: new URLSearchParams({action:'register', nick, uid, pwd})
        }).then(r=>r.json()).then(res=>{
            if(res.ok){
                err.textContent = '注册成功，请登录';
                setTimeout(()=>{
                    document.getElementById('auth-modal').classList.remove('flipped');
                    document.getElementById('login-error').textContent = '';
                }, 1000);
            }else{
                err.textContent = res.msg || '注册失败';
            }
        });
    };
    <?php endif; ?>
</script>
</body>
</html>
