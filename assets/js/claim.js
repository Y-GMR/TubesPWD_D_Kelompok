document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var profileDropdown = document.querySelector('.profile-consumer-dropdown');
    var profilePillBtn = document.getElementById('profilePillBtn');

    if (profileDropdown && profilePillBtn) {
        profilePillBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('is-open');
        });

        document.addEventListener('click', function (e) {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                profileDropdown.classList.remove('is-open');
            }
        });
    }

    var filterPills = document.querySelectorAll('.claim-pill');
    var searchInput = document.getElementById('claimSearchInput');
    if (searchInput && !searchInput.placeholder) {
        searchInput.placeholder = 'Cari nomor klaim atau nama...';
    }
    var tableBody = document.getElementById('claimsTableBody');
    var emptyRow = document.getElementById('claimEmptyRow');

    var currentFilter = 'all';

    var metricPreparedVal = document.getElementById('metricPreparedVal');
    var metricReadyVal = document.getElementById('metricReadyVal');
    var metricDistributedVal = document.getElementById('metricDistributedVal');

    var proofModalOverlay = document.getElementById('proofModalOverlay');
    var btnCloseProofModal = document.getElementById('btnCloseProofModal');
    var proofTrxCode = document.getElementById('proofTrxCode');
    var proofConsumer = document.getElementById('proofConsumer');
    var proofPortion = document.getElementById('proofPortion');
    var proofTime = document.getElementById('proofTime');

    var voucherCodeInput = document.getElementById('voucherCodeInput');
    if (voucherCodeInput && !voucherCodeInput.placeholder) {
        voucherCodeInput.placeholder = 'Masukkan kode voucher konsumen (contoh: #TRX-2026-104 atau TRX-8821)...';
    }
    var btnVerifyVoucher = document.getElementById('btnVerifyVoucher');

    function updateMetrics() {
        if (!tableBody) return;
        var rows = tableBody.querySelectorAll('tr[data-status]');
        var countAll = rows.length;
        var countWaiting = 0;
        var countReady = 0;
        var countCompleted = 0;
        var totalPortionsDistributed = 0;

        for (var i = 0; i < rows.length; i++) {
            var st = rows[i].getAttribute('data-status');
            if (st === 'waiting') {
                countWaiting++;
            } else if (st === 'ready') {
                countReady++;
            } else if (st === 'completed') {
                countCompleted++;
                var portionElem = rows[i].querySelector('.portion-count');
                if (portionElem) {
                    var pNum = parseInt(portionElem.getAttribute('data-portions'), 10);
                    if (!isNaN(pNum)) {
                        totalPortionsDistributed += pNum;
                    }
                }
            }
        }

        if (metricPreparedVal) {
            metricPreparedVal.textContent = countWaiting + ' Pesanan';
        }
        if (metricReadyVal) {
            metricReadyVal.textContent = countReady + ' Pesanan';
        }
        if (metricDistributedVal) {
            metricDistributedVal.textContent = (20 + totalPortionsDistributed) + ' Porsi';
        }

        for (var j = 0; j < filterPills.length; j++) {
            var pill = filterPills[j];
            var f = pill.getAttribute('data-claim-filter');
            if (f === 'all') {
                pill.textContent = 'Semua Klaim (' + countAll + ')';
            } else if (f === 'waiting') {
                pill.textContent = 'Disiapkan (' + countWaiting + ')';
            } else if (f === 'ready') {
                pill.textContent = 'Siap Diambil (' + countReady + ')';
            } else if (f === 'completed') {
                pill.textContent = 'Disalurkan (' + countCompleted + ')';
            }
        }
    }

    function applyFilters() {
        if (!tableBody) return;
        var rows = tableBody.querySelectorAll('tr[data-status]');
        var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var visibleCount = 0;

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var status = row.getAttribute('data-status');
            var statusMatch = (currentFilter === 'all') || (status === currentFilter);

            var textContent = row.textContent.toLowerCase();
            var searchMatch = !query || (textContent.indexOf(query) !== -1);

            if (statusMatch && searchMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }

        if (emptyRow) {
            if (visibleCount === 0) {
                emptyRow.style.display = 'table-row';
            } else {
                emptyRow.style.display = 'none';
            }
        }
    }

    for (var k = 0; k < filterPills.length; k++) {
        (function (pill) {
            pill.addEventListener('click', function () {
                for (var m = 0; m < filterPills.length; m++) {
                    filterPills[m].classList.remove('active');
                }
                pill.classList.add('active');
                currentFilter = pill.getAttribute('data-claim-filter');
                applyFilters();
            });
        })(filterPills[k]);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applyFilters();
        });
    }

    function handleVoucherVerification() {
        if (!voucherCodeInput || !tableBody) return;
        var rawInput = voucherCodeInput.value.trim();
        if (!rawInput) {
            alert('Silakan masukkan nomor atau kode voucher yang ingin diperiksa.');
            voucherCodeInput.focus();
            return;
        }

        var cleanInput = rawInput.replace(/^#/, '').toLowerCase();
        var rows = tableBody.querySelectorAll('tr[data-status]');
        var matchedRow = null;

        for (var i = 0; i < rows.length; i++) {
            var trxCode = rows[i].getAttribute('data-trx') || '';
            var cleanTrx = trxCode.replace(/^#/, '').toLowerCase();
            if (cleanTrx === cleanInput || cleanTrx.indexOf(cleanInput) !== -1) {
                matchedRow = rows[i];
                break;
            }
        }

        if (matchedRow) {
            currentFilter = 'all';
            for (var p = 0; p < filterPills.length; p++) {
                if (filterPills[p].getAttribute('data-claim-filter') === 'all') {
                    filterPills[p].classList.add('active');
                } else {
                    filterPills[p].classList.remove('active');
                }
            }
            if (searchInput) {
                searchInput.value = '';
            }
            applyFilters();

            matchedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            matchedRow.classList.remove('voucher-highlighted');
            void matchedRow.offsetWidth;
            matchedRow.classList.add('voucher-highlighted');

            setTimeout(function () {
                matchedRow.classList.remove('voucher-highlighted');
            }, 2500);
        } else {
            alert('Kode voucher "' + rawInput + '" tidak ditemukan dalam daftar penyaluran pangan saat ini.');
        }
    }

    if (btnVerifyVoucher) {
        btnVerifyVoucher.addEventListener('click', handleVoucherVerification);
    }

    if (voucherCodeInput) {
        voucherCodeInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleVoucherVerification();
            }
        });
    }

    function setCellBadge(cell, className, text) {
        if (!cell) return;
        while (cell.firstChild) cell.removeChild(cell.firstChild);
        var span = document.createElement('span');
        span.className = className;
        span.textContent = text;
        cell.appendChild(span);
    }

    function createClaimButton(className, text, attrs) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = className;
        btn.textContent = text;
        for (var k in attrs) {
            if (Object.prototype.hasOwnProperty.call(attrs, k)) {
                btn.setAttribute(k, attrs[k]);
            }
        }
        return btn;
    }

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            var target = e.target;

            if (target.classList.contains('btn-action-prepare')) {
                var rowPrepare = target.closest('tr');
                if (!rowPrepare) return;
                var trxPrepare = target.getAttribute('data-trx') || '';

                rowPrepare.setAttribute('data-status', 'ready');
                setCellBadge(rowPrepare.cells[5], 'badge-claim-status badge-ready', 'Siap Diambil');

                var actionsCell = target.closest('.claim-actions-cell');
                if (actionsCell) {
                    while (actionsCell.firstChild) actionsCell.removeChild(actionsCell.firstChild);
                    actionsCell.appendChild(createClaimButton('btn-action-complete', 'Tandai Selesai', { 'data-trx': trxPrepare }));
                    actionsCell.appendChild(document.createTextNode(' '));
                    actionsCell.appendChild(createClaimButton('btn-action-cancel', 'Batalkan', { 'data-trx': trxPrepare }));
                }

                updateMetrics();
                applyFilters();
            } else if (target.classList.contains('btn-action-complete')) {
                var rowComplete = target.closest('tr');
                if (!rowComplete) return;
                var trxComplete = target.getAttribute('data-trx') || '';

                var consumerNameElem = rowComplete.querySelector('.consumer-name');
                var portionElem = rowComplete.querySelector('.portion-count');
                var consumerName = consumerNameElem ? consumerNameElem.textContent.trim() : 'Penerima Pangan';
                var portionText = portionElem ? portionElem.textContent.trim() : '1 Porsi';

                rowComplete.setAttribute('data-status', 'completed');
                setCellBadge(rowComplete.cells[5], 'badge-claim-status badge-completed', 'Selesai');

                var actionsCompleteCell = target.closest('.claim-actions-cell');
                if (actionsCompleteCell) {
                    var now = new Date();
                    var formattedTime = 'Hari ini - ' + ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ' WIB';
                    while (actionsCompleteCell.firstChild) actionsCompleteCell.removeChild(actionsCompleteCell.firstChild);
                    actionsCompleteCell.appendChild(createClaimButton('btn-action-proof', 'Lihat Bukti', {
                        'data-trx': trxComplete,
                        'data-consumer': consumerName,
                        'data-portion': portionText,
                        'data-time': formattedTime
                    }));
                }

                updateMetrics();
                applyFilters();
            } else if (target.classList.contains('btn-action-cancel')) {
                var rowCancel = target.closest('tr');
                if (!rowCancel) return;
                var trxCancel = target.getAttribute('data-trx') || '';

                var isConfirmed = window.confirm('Apakah Anda yakin ingin membatalkan klaim pesanan ' + trxCancel + '?');
                if (isConfirmed) {
                    rowCancel.setAttribute('data-status', 'cancelled');
                    setCellBadge(rowCancel.cells[5], 'badge-claim-status badge-cancelled', 'Dibatalkan');

                    var actionsCancelCell = target.closest('.claim-actions-cell');
                    if (actionsCancelCell) {
                        setCellBadge(actionsCancelCell, 'consumer-role-desc', 'Pesanan Dibatalkan');
                    }

                    updateMetrics();
                    applyFilters();
                }
            } else if (target.classList.contains('btn-action-proof')) {
                var trxProof = target.getAttribute('data-trx') || '-';
                var consumerProof = target.getAttribute('data-consumer') || '-';
                var portionProof = target.getAttribute('data-portion') || '-';
                var timeProof = target.getAttribute('data-time') || '-';

                if (proofTrxCode) proofTrxCode.textContent = trxProof;
                if (proofConsumer) proofConsumer.textContent = consumerProof;
                if (proofPortion) proofPortion.textContent = portionProof;
                if (proofTime) proofTime.textContent = timeProof;

                if (proofModalOverlay) {
                    proofModalOverlay.classList.add('active');
                }
            }
        });
    }

    if (btnCloseProofModal && proofModalOverlay) {
        btnCloseProofModal.addEventListener('click', function () {
            proofModalOverlay.classList.remove('active');
        });

        proofModalOverlay.addEventListener('click', function (e) {
            if (e.target === proofModalOverlay) {
                proofModalOverlay.classList.remove('active');
            }
        });
    }

    updateMetrics();
});
