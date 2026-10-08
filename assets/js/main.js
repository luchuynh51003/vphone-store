document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const searchDropdown = document.getElementById("searchDropdown");
    const searchResultsList = document.getElementById("searchResultsList");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const query = this.value.trim().toLowerCase();
            if (query.length === 0) {
                searchDropdown.classList.add("d-none");
                searchResultsList.innerHTML = "";
                return;
            }
            searchDropdown.classList.remove("d-none");
            const allProducts = window.STORE_PRODUCTS || [];
            const matched = allProducts.filter(p => p.name.toLowerCase().includes(query)).slice(0, 6);

            if (matched.length === 0) {
                searchResultsList.innerHTML = `<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-question me-1 text-primary"></i> Không tìm thấy máy phù hợp.</div>`;
                return;
            }

            let html = "";
            matched.forEach(item => {
                const formattedPrice = item.price_formatted || (new Intl.NumberFormat('vi-VN').format(item.price) + ' đ');
                html += `
                    <a href="index.php?page=detail&id=${item.id}" class="search-item">
                        <img src="${item.image}" alt="${item.name}" class="search-thumb">
                        <div class="search-info">
                            <div class="search-title">${item.name}</div>
                            <div class="search-price">${formattedPrice}</div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small ms-2"></i>
                    </a>`;
            });
            searchResultsList.innerHTML = html;
        });

        document.addEventListener("click", function (e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.add("d-none");
            }
        });
    }
});
