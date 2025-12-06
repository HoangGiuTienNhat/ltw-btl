const CONFIG = {
    apiUrl: 'news_api.php',
    imageBase: 'news_uploads/',
    detailUrl: 'news_detail.php'
};

let currentKeyword = '';

function handleSearch(e) {
    if(e) e.preventDefault();
    const input = document.getElementById('search-keyword');
    currentKeyword = input.value.trim();
    loadNewsList(1);
}

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'}) + ' - ' + date.toLocaleDateString('vi-VN');
};


async function loadHomeNews() {
    const container = document.getElementById("home-news-container");
    const paginationContainer = document.getElementById('pagination');
    
    if (!container) return;

    container.innerHTML = '<div class="text-center w-100 py-5"><div class="spinner-border text-primary"></div></div>';

    try {
        const params = new URLSearchParams({
            action: 'list',
            limit: 4,
        });

        const res = await fetch(`${CONFIG.apiUrl}?${params.toString()}`);
        const result = await res.json();
        
        container.innerHTML = '';
        
        if (!result.data || result.data.length === 0) {
            container.innerHTML = '<div class="text-center w-100 py-5 text-muted">Không tìm thấy bài viết nào phù hợp.</div>';
            if(paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        result.data.forEach(item => {
            const imgUrl = item.image ? `${CONFIG.imageBase}${item.image}` : '';
            const link = `${CONFIG.detailUrl}?id=${item.id}`;

            const postDate = new Date(item.created_at);
            const now = new Date();
            const diffTime = Math.abs(now - postDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            let badgeHtml = '';
            if (diffDays <= 1) { 
               badgeHtml = '<span class="badge position-absolute top-0 end-0 m-2 shadow-sm badge-blink" style="z-index: 5;">Mới</span>'; 
            }
            const html = `
                <div class="col">
                    <div class="card transition-card news-home-card">
                        <a href="${link}" class="ratio ratio-16x9 rounded-top news-image">
                            ${badgeHtml}
                            <img src="${imgUrl}" class="img-fluid object-fit-cover" alt="${item.title}" loading="lazy">
                        </a>
                        <div class="card-body">
                            <small class="text-muted"><i class="bi bi-clock"></i> ${formatDate(item.created_at)}</small>
                            <div class="card-title mt-2 overflow-hidden">
                                <a href="${link}" class="text-decoration-none text-dark text-wrap">
                                    <h5 class="fs-6 fs-md-5 fw-semibold lh-sm text-truncate">${item.title}</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });

    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-danger text-center">Lỗi tải dữ liệu.</p>';
    }
}


async function loadNewsList(page = 1) {
    const container = document.getElementById("news-container");
    const paginationContainer = document.getElementById('pagination');
    
    if (!container) return;

    container.innerHTML = '<div class="text-center w-100 py-5"><div class="spinner-border text-primary"></div></div>';

    try {
        const params = new URLSearchParams({
            action: 'list',
            limit: 6,
            page: page,
            keyword: currentKeyword
        });

        const res = await fetch(`${CONFIG.apiUrl}?${params.toString()}`);
        const result = await res.json();
        container.innerHTML = '';
        
        if (!result.data || result.data.length === 0) {
            container.innerHTML = '<div class="text-center w-100 py-5 text-muted">Không tìm thấy bài viết nào.</div>';
            if(paginationContainer) paginationContainer.innerHTML = '';
            if (currentKeyword.trim() !== '') {
                document.getElementById('total-news').innerText = `Không tìm thấy bài viết nào với từ khóa "${currentKeyword}"`;
            } else {
                document.getElementById('total-news').innerText = 'Không tìm thấy bài viết nào.';
            }
            return;
        }
        if (currentKeyword.trim() !== '') {
            document.getElementById('total-news').innerText = `Tìm thấy ${result.pagination.total_records} bài viết với từ khóa "${currentKeyword}"`;
        } else {
            document.getElementById('total-news').innerText = `Tìm thấy ${result.pagination.total_records} bài viết`;
        }
        result.data.forEach(item => {
            const imgUrl = item.image ? `${CONFIG.imageBase}${item.image}` : `${CONFIG.imageBase}../pic/no-image.jpg`;
            const link = `${CONFIG.detailUrl}?id=${item.id}`;
            const postDate = new Date(item.created_at);
            const now = new Date();
            const diffTime = Math.abs(now - postDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            let badgeHtml = '';
            if (diffDays <= 1) { 
               badgeHtml = '<span class="badge position-absolute top-0 end-0 m-2 shadow-sm badge-blink" style="z-index: 5;">Mới</span>'; 
            }
            const html = `
                <div class="col">
                    <div class="card transition-card news-home-card">
                        <a href="${link}" class="ratio ratio-16x9 rounded-top news-image">
                            ${badgeHtml}
                            <img src="${imgUrl}" class="img-fluid object-fit-cover" alt="${item.title}" loading="lazy">
                        </a>
                        <div class="card-body">
                            <small class="text-muted"><i class="bi bi-clock"></i> ${formatDate(item.created_at)}</small>
                            <div class="card-title mt-2 overflow-hidden">
                                <a href="${link}" class="text-decoration-none text-dark text-wrap">
                                    <h5 class="fs-6 fs-md-5 fw-semibold lh-sm text-truncate">${item.title}</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });
        if (paginationContainer && result.pagination) {
            renderPagination(paginationContainer, result.pagination);
        }

    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-danger text-center">Lỗi tải dữ liệu.</p>';
    }
}

function renderPagination(container, pagination) {
    const { current_page, total_pages } = pagination;
    let html = '';

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    const prevDisabled = current_page === 1 ? 'disabled' : '';
    html += `
        <li class="page-item ${prevDisabled}">
            <button class="page-link" onclick="changePage(${current_page - 1})" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </button>
        </li>
    `;
    for (let i = 1; i <= total_pages; i++) {
        const active = i === current_page ? 'active' : '';
        html += `
            <li class="page-item ${active}">
                <button class="page-link" onclick="changePage(${i})">${i}</button>
            </li>
        `;
    }
    const nextDisabled = current_page === total_pages ? 'disabled' : '';
    html += `
        <li class="page-item ${nextDisabled}">
            <button class="page-link" onclick="changePage(${current_page + 1})" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </button>
        </li>
    `;

    container.innerHTML = html;
}

function changePage(page) {
    loadNewsList(page);
    document.getElementById('search-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}