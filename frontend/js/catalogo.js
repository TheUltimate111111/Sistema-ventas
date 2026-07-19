document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('catalogSearch');
    const searchButton = document.getElementById('catalogSearchButton');
    const productsBody = document.getElementById('catalogProducts');
    const countElement = document.getElementById('catalogCount');
    const noResultsRow = document.getElementById('catalogNoResults');
    const btnNewProduct = document.getElementById('btnNewProduct');
    const catalogModal = document.getElementById('catalogModal');
    const catalogForm = document.getElementById('catalogForm');
    const formAction = document.getElementById('formAction');
    const productIdInput = document.getElementById('productId');
    const productCode = document.getElementById('productCode');
    const productName = document.getElementById('productName');
    const productPrice = document.getElementById('productPrice');
    const productStock = document.getElementById('productStock');
    const modalLabel = document.getElementById('catalogModalLabel');
    const modalMessage = document.getElementById('productModalMessage');

    if (!catalogModal || !catalogForm) return;

    /* ---- Bootstrap Modal with fallback ---- */
    let bsModal = null;
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsModal = new bootstrap.Modal(catalogModal);
        }
    } catch (e) {
        bsModal = null;
    }

    const openModal = () => {
        if (bsModal) {
            bsModal.show();
        } else {
            catalogModal.classList.add('show');
            catalogModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    };

    const closeModal = () => {
        if (bsModal) {
            bsModal.hide();
        } else {
            catalogModal.classList.remove('show');
            catalogModal.style.display = '';
            document.body.style.overflow = '';
        }
    };

    /* ---- Close modal: backdrop click ---- */
    catalogModal.addEventListener('click', (e) => {
        if (e.target === catalogModal) closeModal();
    });

    /* ---- Close modal: buttons with data-bs-dismiss ---- */
    catalogModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', closeModal);
    });

    /* ---- Helpers ---- */
    const hideModalMessage = () => {
        if (modalMessage) {
            modalMessage.textContent = '';
            modalMessage.className = 'alert d-none';
        }
    };

    const resetForm = () => {
        catalogForm.reset();
        if (formAction) formAction.value = 'create';
        if (productIdInput) productIdInput.value = '0';
        if (modalLabel) modalLabel.textContent = 'Agregar producto';
        if (productCode) productCode.readOnly = false;
        if (productName) productName.readOnly = false;
        hideModalMessage();
    };

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

    /* ---- Button: Agregar producto ---- */
    if (btnNewProduct) {
        btnNewProduct.addEventListener('click', () => {
            resetForm();
            openModal();
        });
    }

    /* ---- Button: Buscar ---- */
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            filterTable();
        });
    }

    /* ---- Input: Buscar (Enter + live filter) ---- */
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterTable();
            }
        });
        searchInput.addEventListener('input', filterTable);
    }

    /* ---- Buttons: Editar / Eliminar (event delegation) ---- */
    if (productsBody) {
        productsBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.btn-edit-product');
            if (editBtn) {
                const row = editBtn.closest('tr[data-product-id]');
                if (!row) return;
                resetForm();
                if (formAction) formAction.value = 'update';
                if (productIdInput) productIdInput.value = row.dataset.productId;
                if (productCode) { productCode.value = row.dataset.productCode || ''; productCode.readOnly = true; }
                if (productName) { productName.value = row.dataset.productName || ''; productName.readOnly = true; }
                if (productPrice) productPrice.value = row.dataset.productPrice || '';
                if (productStock) productStock.value = row.dataset.productStock || '';
                if (modalLabel) modalLabel.textContent = 'Editar producto';
                openModal();
                return;
            }

            const deleteBtn = e.target.closest('.btn-delete-product');
            if (deleteBtn) {
                const row = deleteBtn.closest('tr[data-product-id]');
                if (!row) return;
                const name = row.dataset.productName || 'este producto';
                if (!confirm('¿Eliminar "' + name + '"? Esta acción no se puede deshacer.')) return;
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = 'catalogo.php';
                const a = document.createElement('input');
                a.type = 'hidden';
                a.name = 'action';
                a.value = 'delete';
                const d = document.createElement('input');
                d.type = 'hidden';
                d.name = 'delete_id';
                d.value = row.dataset.productId;
                f.appendChild(a);
                f.appendChild(d);
                document.body.appendChild(f);
                f.submit();
            }
        });
    }

    filterTable();
});
