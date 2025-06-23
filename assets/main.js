const commentForm = document.getElementById('comment-form');
const commentInput = document.getElementById('comment-input');
const commentsList = document.getElementById('comments-list');

let lastCommentId = 0;

// 获取当前登录用户信息（昵称）
let currentUser = null;
fetch('api/user.php?action=session')
    .then(res => res.json())
    .then(data => {
        if (data.ok) currentUser = data.user;
    });

function fetchComments() {
    fetch('api/comment.php?action=list&since=0')
        .then(res => res.json())
        .then(data => {
            // 判断用户是否在底部（允许1像素误差）
            const nearBottom = Math.abs(commentsList.scrollTop + commentsList.clientHeight - commentsList.scrollHeight) < 2;
            commentsList.innerHTML = '';
            // 显示全部评论
            data.forEach(c => {
                addComment(c);
                lastCommentId = Math.max(lastCommentId, c.id);
            });
            // 只有在用户本来就在底部时才自动滚动到底部
            if (nearBottom) {
                commentsList.scrollTop = commentsList.scrollHeight;
            }
        });
}
function addComment(c) {
    const el = document.createElement('div');
    el.className = 'comment comment-bubble';
    // 展示昵称和内容
    el.innerHTML = `<span style="color:#6cf;font-weight:bold;">${c.nick ? c.nick : '游客'}</span>：${c.text}`;
    commentsList.appendChild(el);
}

commentForm.addEventListener('submit', e => {
    e.preventDefault();
    const text = commentInput.value.trim();
    if (!text) return;
    // 未登录不允许评论
    if (!currentUser) {
        alert('请先登录后再发表评论');
        return;
    }
    fetch('api/comment.php', {
        method: 'POST',
        body: new URLSearchParams({text})
    }).then(() => {
        commentInput.value = '';
    });
});
setInterval(fetchComments, 2000);

window.onload = () => {
    fetchComments();
};
