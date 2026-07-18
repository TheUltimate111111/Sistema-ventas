document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('catalogSearch');
    const productsBody = document.getElementById('catalogProducts');
    const countElement = document.getElementById('catalogCount');
    const noResultsRow = document.getElementById('catalogNoResults');

    const filterTable = () => {
        if (!productsBody || !searchInput || !countElement) return;
        const query = searchInput.value.trim().toLowerCase();
        const rows = productsBody.querySelectorAll('tr[data-product-id]');
        let visibleCount = 0;

        rows.forEach((row) => {
            const code = String(row.dataset.productCode || '').toLowerCase();
            const name = String(row.dataset.productName || '').toLowerCase();
            const show = query === '' || code.includes(query) || name.includes(query);
            row.style.display = show ? '' : 'none';
            if (show) visibleCount += 1;
        });

        if (noResultsRow) {
            noResultsRow.classList.toggle('d-none', visibleCount !== 0);
        }
        countElement.textContent = String(visibleCount);
    };

    searchInput?.addEventListener('input', filterTable);
    filterTable();
});
