(function () {
    "use strict";

    function applyUserRole() {
        var params = new URLSearchParams(window.location.search);
        var role = params.get("role");
        if (!role) {
            try {
                role = localStorage.getItem("tubespwd_role") || sessionStorage.getItem("tubespwd_role");
            } catch (e) {}
        }

        var nameEl = document.querySelector(".profile-consumer-name");
        var roleEl = document.querySelector(".profile-consumer-role");
        var avatarEl = document.querySelector(".profile-consumer-avatar");
        var pillBtn = document.querySelector(".profile-consumer-pill");

        if (role === "konsumen") {
            try {
                localStorage.setItem("tubespwd_role", "konsumen");
                sessionStorage.setItem("tubespwd_role", "konsumen");
            } catch (e) {}
            if (nameEl) nameEl.textContent = "Iwang Petra";
            if (roleEl) roleEl.textContent = "Konsumen";
            if (avatarEl) {
                avatarEl.src = "assets/images/avatar_consumer.png";
                avatarEl.alt = "Avatar Iwang Petra";
            }
            if (pillBtn) pillBtn.title = "Profil Konsumen - Iwang Petra";
        } else {
            try {
                localStorage.setItem("tubespwd_role", "produsen");
                sessionStorage.setItem("tubespwd_role", "produsen");
            } catch (e) {}
            if (nameEl) nameEl.textContent = "Dapur Lestari";
            if (roleEl) roleEl.textContent = "Produsen";
            if (avatarEl) {
                avatarEl.src = "assets/images/producer/avatar_produsen.png";
                avatarEl.alt = "Avatar Dapur Lestari";
            }
            if (pillBtn) pillBtn.title = "Profil Produsen - Dapur Lestari";
        }
    }

    function initNavbarDropdown() {
        var dropdown = document.querySelector(".profile-consumer-dropdown");
        var btn = document.getElementById("profilePillBtn");
        if (!dropdown || !btn) return;

        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle("is-open");
        });

        document.addEventListener("click", function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove("is-open");
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" || e.keyCode === 27) {
                dropdown.classList.remove("is-open");
            }
        });
    }

    function initInventoryFilters() {
        var searchInput = document.getElementById("invSearchInput");
        if (searchInput && !searchInput.placeholder) {
            searchInput.placeholder = "Cari nama makanan atau kategori...";
        }
        var categorySelect = document.getElementById("invCategorySelect");
        var filterPills = document.querySelectorAll(".inv-pill");
        var cards = document.querySelectorAll(".inventory-card");
        var emptyState = document.getElementById("invEmptyState");

        var currentStatus = "all";
        var currentCategory = "all";
        var currentSearch = "";

        function runFilter() {
            var visibleCount = 0;
            var term = currentSearch.toLowerCase().trim();

            for (var i = 0; i < cards.length; i++) {
                var card = cards[i];
                var status = card.getAttribute("data-status");
                var cat = card.getAttribute("data-category");
                var text = card.textContent.toLowerCase();

                var statusOk = (currentStatus === "all" || status === currentStatus);
                var categoryOk = (currentCategory === "all" || cat.indexOf(currentCategory) !== -1);
                var searchOk = (!term || text.indexOf(term) !== -1);

                if (statusOk && categoryOk && searchOk) {
                    card.style.display = "flex";
                    visibleCount++;
                } else {
                    card.style.display = "none";
                }
            }

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? "block" : "none";
            }
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                currentSearch = this.value;
                runFilter();
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener("change", function () {
                currentCategory = this.value;
                runFilter();
            });
        }

        for (var i = 0; i < filterPills.length; i++) {
            filterPills[i].addEventListener("click", function () {
                for (var j = 0; j < filterPills.length; j++) {
                    filterPills[j].classList.remove("active");
                }
                this.classList.add("active");
                currentStatus = this.getAttribute("data-status-filter") || "all";
                runFilter();
            });
        }
    }

    function initStockModal() {
        var overlay = document.getElementById("stockModalOverlay");
        var modalTitle = document.getElementById("modalItemTitle");
        var stockInput = document.getElementById("inputStockVal");
        var btnMinus = document.getElementById("btnStepMinus");
        var btnPlus = document.getElementById("btnStepPlus");
        var btnCancel = document.getElementById("btnCancelModal");
        var btnSave = document.getElementById("btnSaveStock");

        var currentActiveBtn = null;

        document.addEventListener("click", function (e) {
            var target = e.target;
            if (target && target.classList.contains("btn-inv-manage")) {
                currentActiveBtn = target;
                var title = target.getAttribute("data-title") || "Makanan";
                var stock = parseInt(target.getAttribute("data-stock"), 10) || 0;

                if (modalTitle) modalTitle.textContent = "Kelola Stok — " + title;
                if (stockInput) stockInput.value = stock;
                if (overlay) overlay.classList.add("active");
            }
        });

        if (btnMinus && stockInput) {
            btnMinus.addEventListener("click", function () {
                var val = parseInt(stockInput.value, 10);
                if (isNaN(val)) val = 0;
                if (val > 0) stockInput.value = val - 1;
            });
        }

        if (btnPlus && stockInput) {
            btnPlus.addEventListener("click", function () {
                var val = parseInt(stockInput.value, 10);
                if (isNaN(val)) val = 0;
                if (val < 999) stockInput.value = val + 1;
            });
        }

        if (stockInput) {
            stockInput.addEventListener("input", function () {
                var cleanVal = this.value.replace(/[^0-9]/g, "");
                if (cleanVal !== "") {
                    var parsed = parseInt(cleanVal, 10);
                    if (parsed > 999) cleanVal = "999";
                }
                if (this.value !== cleanVal) {
                    this.value = cleanVal;
                }
            });
        }

        function closeModal() {
            if (overlay) overlay.classList.remove("active");
            currentActiveBtn = null;
        }

        if (btnCancel) {
            btnCancel.addEventListener("click", closeModal);
        }

        if (overlay) {
            overlay.addEventListener("click", function (e) {
                if (e.target === overlay) closeModal();
            });
        }

        if (btnSave) {
            btnSave.addEventListener("click", function () {
                if (!currentActiveBtn || !stockInput) return;
                var rawStock = stockInput.value.trim();
                var newStock = parseInt(rawStock, 10);
                if (!/^\d+$/.test(rawStock) || isNaN(newStock) || newStock < 0 || newStock > 999) {
                    alert("Jumlah stok harus berupa angka antara 0 sampai 999.");
                    stockInput.focus();
                    return;
                }
                currentActiveBtn.setAttribute("data-stock", newStock);

                var card = currentActiveBtn.closest(".inventory-card");
                if (card) {
                    var badge = card.querySelector(".stock-badge-val");
                    if (badge) {
                        badge.textContent = "Stok: " + newStock;
                        if (newStock <= 8) {
                            badge.classList.add("warning");
                            card.setAttribute("data-status", "low");
                            var banner = card.querySelector(".inv-card-banner");
                            if (banner && !banner.querySelector(".inv-low-stock-badge")) {
                                var lowBadge = document.createElement("span");
                                lowBadge.className = "inv-low-stock-badge";
                                lowBadge.textContent = "Stok Menipis";
                                banner.appendChild(lowBadge);
                            }
                        } else {
                            badge.classList.remove("warning");
                            card.setAttribute("data-status", "available");
                            var existingLow = card.querySelector(".inv-low-stock-badge");
                            if (existingLow) {
                                existingLow.parentNode.removeChild(existingLow);
                            }
                        }
                    }
                }
                recalculateMetrics();
                closeModal();
            });
        }
    }

    function initCloseDonation() {
        document.addEventListener("click", function (e) {
            var target = e.target;
            if (target && target.classList.contains("btn-inv-close")) {
                var card = target.closest(".inventory-card");
                if (!card) return;

                if (card.classList.contains("is-closed")) {
                    card.classList.remove("is-closed");
                    target.textContent = "Tutup Donasi";
                    target.classList.remove("closed");
                    var manageBtn = card.querySelector(".btn-inv-manage");
                    var curStock = manageBtn ? parseInt(manageBtn.getAttribute("data-stock"), 10) || 0 : 0;
                    card.setAttribute("data-status", curStock <= 8 ? "low" : "available");
                } else {
                    card.classList.add("is-closed");
                    target.textContent = "Buka Kembali";
                    target.classList.add("closed");
                    card.setAttribute("data-status", "closed");
                }
                recalculateMetrics();
            }
        });
    }

    function recalculateMetrics() {
        var cards = document.querySelectorAll(".inventory-card");
        var totalStock = 0;
        var lowCount = 0;

        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            if (!card.classList.contains("is-closed")) {
                var manageBtn = card.querySelector(".btn-inv-manage");
                var stock = manageBtn ? parseInt(manageBtn.getAttribute("data-stock"), 10) || 0 : 0;
                totalStock += stock;
                if (stock <= 8 && stock > 0) {
                    lowCount++;
                }
            }
        }

        var readyEl = document.getElementById("metricReadyVal");
        var lowEl = document.getElementById("metricLowVal");
        if (readyEl) readyEl.textContent = totalStock + " Porsi";
        if (lowEl) lowEl.textContent = lowCount + " Menu";
    }

    document.addEventListener("DOMContentLoaded", function () {
        applyUserRole();
        initNavbarDropdown();
        initInventoryFilters();
        initStockModal();
        initCloseDonation();
    });
})();
