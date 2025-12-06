document.addEventListener('DOMContentLoaded', () => {
    loadNewsDetail();
});

const CONFIG = {
    apiUrl: 'news_api.php',
    imageBase: 'news_uploads/',
    detailUrl: 'news_detail.php'
};

const AVATAR_COLORS = [
    '#F44336', 
    '#E91E63',
    '#9C27B0',
    '#673AB7', 
    '#3F51B5',
    '#2196F3',
    '#009688', 
    '#4CAF50', 
    '#FF9800', 
    '#FF5722', 
    '#795548', 
    '#607D8B'
];

function getColorByName(name) {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash % AVATAR_COLORS.length);
    return AVATAR_COLORS[index];
}

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'}) + ' - ' + date.toLocaleDateString('vi-VN');
};

async function loadNewsDetail() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) {
        document.getElementById('news-content').innerHTML = '<div class="alert alert-warning">Không tìm thấy bài viết</div>';
        return;
    }
    try {
        const res = await fetch(`${CONFIG.apiUrl}?action=detail&id=${id}`);
        const result = await res.json();

        if (result.status === 'success') {
            const data = result.data;
            document.title = data.title;
            const imgEl = document.getElementById('viewImage');
            if (data.image) {
                imgEl.src = CONFIG.imageBase + data.image;
                imgEl.style.display = 'block';
            } else {
                imgEl.style.display = 'none';
            }
            document.getElementById('news-title').innerText = data.title;
            document.getElementById('news-date').innerText = formatDate(data.created_at);
            document.getElementById('news-body').innerHTML = data.content.replace(
                /<table(?![^>]*table-bordered)([^>]*)>/gi,
                '<table class="table table-bordered table-hover"$1>'
            );
            loadComments(id);
        } else {
            document.getElementById('news-content').innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (err) {
        alert('Lỗi: ' + err.message);
        console.error(err);
    }
}

async function loadComments(newsId, page = 1) {
    const list = document.getElementById('comment-list');
    const count = document.getElementById('comment-count');
    const paginationContainer = document.getElementById('comment-pagination');

    try {
        // Gọi API kèm tham số page
        const res = await fetch(`${CONFIG.apiUrl}?action=get_comments&id=${newsId}&page=${page}`);
        const result = await res.json();
    
        // Cập nhật tổng số bình luận
        if (result.pagination) {
            count.innerText = result.pagination.total_records;
        }

        list.innerHTML = '';

        if (!result.data || result.data.length === 0) {
            list.innerHTML = '<p class="text-muted fst-italic">Chưa có bình luận nào.</p>';
            paginationContainer.innerHTML = ''; // Xóa phân trang nếu không có data
            return;
        }

        // Render danh sách comment
        result.data.forEach(cmt => {
            const avatarColor = getColorByName(cmt.author);
            const html = `
                <div class="d-flex gap-3 mb-3 border-bottom pb-3">
                    <div 
                        class="text-white rounded-circle d-flex justify-content-center align-items-center flex-shrink-0" 
                        style="
                            width: calc(2rem + 0.5vw);
                            height: calc(2rem + 0.5vw);
                            font-size: calc(1rem + 0.2vw);
                            background-color: ${avatarColor};
                        "
                    >
                        ${cmt.author.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="text-wrap fw-bold">
                            ${cmt.author} - <span class="text-muted small">Lúc: ${formatDate(cmt.created_at)}</span>
                        </div>
                        <div class="mt-1">${cmt.content.replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
            list.innerHTML += html;
        });

        // Render nút phân trang
        if (result.pagination) {
            renderCommentPagination(newsId, result.pagination);
        }

    } catch (err) {
        alert('Lỗi: ' + err.message);
        console.error(err);
    }
}

function renderCommentPagination(newsId, pagination) {
    const container = document.getElementById('comment-pagination');
    const { current_page, total_pages } = pagination;
    let html = '';

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    const prevDisabled = current_page === 1 ? 'disabled' : '';
    html += `
        <li class="page-item ${prevDisabled}">
            <button class="page-link" onclick="changeCommentPage(${newsId}, ${current_page - 1})" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </button>
        </li>
    `;

    for (let i = 1; i <= total_pages; i++) {
        const active = i === current_page ? 'active' : '';
        html += `
            <li class="page-item ${active}">
                <button class="page-link" onclick="changeCommentPage(${newsId}, ${i})">${i}</button>
            </li>
        `;
    }

    const nextDisabled = current_page === total_pages ? 'disabled' : '';
    html += `
        <li class="page-item ${nextDisabled}">
            <button class="page-link" onclick="changeCommentPage(${newsId}, ${current_page + 1})" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </button>
        </li>
    `;

    container.innerHTML = html;
}

// Hàm xử lý khi bấm chuyển trang
function changeCommentPage(newsId, page) {
    loadComments(newsId, page);
    document.getElementById('comments-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function submitComment(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const textarea = e.target.querySelector('textarea');
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Đang gửi...';

    try {
        const formData = new FormData();
        formData.append('news_id', id);
        formData.append('content', textarea.value);
        const res = await fetch('post_comments.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        
        if (result.status === 'success') {
            textarea.value = '';
            const toast = new bootstrap.Toast(document.getElementById('liveToast'));
            toast.show();
        } else {
            alert(result.message);
            if(result.message.includes('đăng nhập')) {
                window.location.href = 'login.php';
            }
        }
    } catch (err) {
        alert('Lỗi: ' + err.message);
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
}