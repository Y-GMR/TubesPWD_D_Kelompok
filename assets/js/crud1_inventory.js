(function () {
    'use strict';

    function getCardStatus(card) {
        if (card.classList.contains('inv-status-available')) return 'available';
        if (card.classList.contains('inv-status-low')) return 'low';
        if (card.classList.contains('inv-status-expired')) return 'expired';
        if (card.classList.contains('inv-status-closed')) return 'closed';
        return '';
    }

    function getPillFilter(pill) {
        if (pill.classList.contains('inv-filter-available')) return 'available';
        if (pill.classList.contains('inv-filter-low')) return 'low';
        if (pill.classList.contains('inv-filter-expired')) return 'expired';
        if (pill.classList.contains('inv-filter-closed')) return 'closed';
        return 'all';
    }

    function initInventoryFilters() {
        var searchInput = document.getElementById('invSearchInput');
        if (searchInput && !searchInput.placeholder) {
            searchInput.placeholder = 'Cari nama makanan atau kategori...';
        }

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
                var status = getCardStatus(card);
                var catEl = card.querySelector('.inv-cat-value');
                var cat = catEl ? catEl.textContent.trim() : '';
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
                currentStatus = getPillFilter(this);
                runFilter();
            });
        }
    }

    function initStockModal() {
        var overlay = document.getElementById('stockModalOverlay');
        var modalForm = document.getElementById('stockModalForm');
        var modalTitle = document.getElementById('modalItemTitle');
        var itemIdInput = document.getElementById('modalItemIdInput');
        var stockInput = document.getElementById('inputStockVal');
        var btnMinus = document.getElementById('btnStepMinus');
        var btnPlus = document.getElementById('btnStepPlus');
        var btnCancel = document.getElementById('btnCancelModal');

        document.addEventListener('click', function (e) {
            var target = e.target;
            if (target && target.classList.contains('btn-open-stock-modal')) {
                var card = target.closest('.inventory-card');
                if (!card) return;

                var id = card.id ? card.id.replace('inv-card-', '').trim() : '';
                var titleEl = card.querySelector('.inv-item-title');
                var stockEl = card.querySelector('.stock-badge-val');
                var title = titleEl ? titleEl.textContent.trim() : 'Makanan';
                var stockRaw = stockEl ? stockEl.textContent.replace(/[^0-9]/g, '') : '0';
                var stock = parseInt(stockRaw, 10);
                if (isNaN(stock) || stock < 0) stock = 0;

                if (itemIdInput) itemIdInput.value = id;
                if (modalTitle) modalTitle.textContent = 'Kelola Stok - ' + title;
                if (stockInput) stockInput.value = stock;
                if (overlay) overlay.classList.add('active');
            }
        });

        if (stockInput) {
            stockInput.addEventListener('input', function () {
                var cleaned = this.value.replace(/[^0-9]/g, '');
                if (cleaned !== '') {
                    var parsed = parseInt(cleaned, 10);
                    if (parsed > 9999) cleaned = '9999';
                }
                if (this.value !== cleaned) {
                    this.value = cleaned;
                }
            });
        }

        if (btnMinus && stockInput) {
            btnMinus.addEventListener('click', function () {
                var val = parseInt(stockInput.value, 10);
                if (isNaN(val)) val = 0;
                if (val > 0) stockInput.value = val - 1;
            });
        }

        if (btnPlus && stockInput) {
            btnPlus.addEventListener('click', function () {
                var val = parseInt(stockInput.value, 10);
                if (isNaN(val)) val = 0;
                if (val < 9999) stockInput.value = val + 1;
            });
        }

        if (modalForm) {
            modalForm.addEventListener('submit', function (e) {
                var raw = stockInput ? stockInput.value.trim() : '';
                var num = parseInt(raw, 10);
                if (!/^\d+$/.test(raw) || isNaN(num) || num < 0 || num > 9999) {
                    e.preventDefault();
                    window.alert('Nilai stok harus berupa bilangan bulat antara 0 hingga 9999.');
                    if (stockInput) stockInput.focus();
                }
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

    function getCardTitle(formEl) {
        var card = formEl.closest('.inventory-card');
        var titleEl = card ? card.querySelector('.inv-item-title') : null;
        return titleEl ? titleEl.textContent.trim() : 'makanan ini';
    }

    function initActionConfirmations() {
        var closeForms = document.querySelectorAll('.form-inv-close');
        closeForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var title = getCardTitle(form);
                if (!window.confirm('Tutup donasi untuk "' + title + '" (stok akan disetel menjadi 0)?')) {
                    e.preventDefault();
                }
            });
        });

        var deleteForms = document.querySelectorAll('.form-inv-delete');
        deleteForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var title = getCardTitle(form);
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
