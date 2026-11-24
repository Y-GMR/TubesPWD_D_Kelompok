(function () {
    'use strict';

    function initInventoryFilters() {
        var searchInput = document.getElementById('invSearchInput');
        var categorySelect = document.getElementById('invCategorySelect');
        var filterPills = document.querySelectorAll('.inv-pill');
        var cards = document.querySelectorAll('.inventory-card');
        var emptyState = document.getElementById('invEmptyState');

        if (!cards.length) {
            return;
        }

        var currentStatus = 'all';
        var currentCategory = 'all';
        var currentSearch = '';

        function runFilter() {
            var visibleCount = 0;
            var term = currentSearch.toLowerCase().trim();

            for (var i = 0; i < cards.length; i++) {
                var card = cards[i];
                var status = card.getAttribute('data-status') || '';
                var cat = card.getAttribute('data-category') || '';
                var text = (card.textContent || '').toLowerCase();

                var statusOk = (currentStatus === 'all' || status === currentStatus);
                var categoryOk = (currentCategory === 'all' || cat === currentCategory);
                var searchOk = (!term || text.indexOf(term) !== -1);

                if (statusOk && categoryOk && searchOk) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            }

            if (emptyState) {
                emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentSearch = this.value;
                runFilter();
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                currentCategory = this.value;
                runFilter();
            });
        }

        for (var i = 0; i < filterPills.length; i++) {
            filterPills[i].addEventListener('click', function () {
                for (var j = 0; j < filterPills.length; j++) {
                    filterPills[j].classList.remove('active');
                }
                this.classList.add('active');
                currentStatus = this.getAttribute('data-status-filter') || 'all';
                runFilter();
            });
        }
    }

    function initStockModal() {
        var overlay = document.getElementById('stockModalOverlay');
        var modalTitle = document.getElementById('modalItemTitle');
        var itemIdInput = document.getElementById('modalItemIdInput');
        var stockInput = document.getElementById('inputStockVal');
        var btnMinus = document.getElementById('btnStepMinus');
        var btnPlus = document.getElementById('btnStepPlus');
        var btnCancel = document.getElementById('btnCancelModal');

        document.addEventListener('click', function (e) {
            var target = e.target;
            if (target && target.classList.contains('btn-open-stock-modal')) {
                var id = target.getAttribute('data-id') || '';
                var title = target.getAttribute('data-title') || 'Makanan';
                var stock = parseInt(target.getAttribute('data-stock'), 10);
                if (isNaN(stock) || stock < 0) stock = 0;

                if (itemIdInput) itemIdInput.value = id;
                if (modalTitle) modalTitle.textContent = 'Kelola Stok - ' + title;
                if (stockInput) stockInput.value = stock;
                if (overlay) overlay.classList.add('active');
            }
        });

        if (btnMinus && stockInput) {
            btnMinus.addEventListener('click', function () {
                var val = parseInt(stockInput.value, 10) || 0;
                if (val > 0) stockInput.value = val - 1;
            });
        }

        if (btnPlus && stockInput) {
            btnPlus.addEventListener('click', function () {
                var val = parseInt(stockInput.value, 10) || 0;
                if (val < 9999) stockInput.value = val + 1;
            });
        }

        function closeModal() {
            if (overlay) overlay.classList.remove('active');
        }

        if (btnCancel) {
            btnCancel.addEventListener('click', closeModal);
        }

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeModal();
            }
        });
    }

    function initActionConfirmations() {
        var closeForms = document.querySelectorAll('.form-inv-close');
        closeForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var title = form.getAttribute('data-title') || 'makanan ini';
                if (!window.confirm('Tutup donasi untuk "' + title + '" (stok akan disetel menjadi 0)?')) {
                    e.preventDefault();
                }
            });
        });

        var deleteForms = document.querySelectorAll('.form-inv-delete');
        deleteForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var title = form.getAttribute('data-title') || 'makanan ini';
                if (!window.confirm('Apakah Anda yakin ingin menghapus "' + title + '" secara permanen?')) {
                    e.preventDefault();
                }
            });
        });

        var lockedButtons = document.querySelectorAll('.btn-inv-delete-locked');
        lockedButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.alert('Produk tidak dapat dihapus karena tercatat dalam riwayat klaim transaksi. Gunakan "Tutup Donasi" untuk menonaktifkan.');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initInventoryFilters();
        initStockModal();
        initActionConfirmations();
    });
})();
