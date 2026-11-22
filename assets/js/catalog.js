document.addEventListener('DOMContentLoaded', function () {
    var categoryPills = document.querySelectorAll('.category-pill');
    var merchantCards = document.querySelectorAll('.merchant-main-card');
    var searchInput = document.querySelector('.catalog-search-input');
    var searchForm = document.querySelector('.catalog-search-form');
    var emptyState = document.getElementById('catalogEmptyState');
    var isViews = window.location.pathname.indexOf('/views/') !== -1;

    var authAttr = document.body.getAttribute('data-auth');
    var hasProfilePill = document.querySelector('.profile-consumer-pill') !== null;
    var isAuthenticated = (authAttr === 'true') || (authAttr !== 'false' && hasProfilePill);

    var currentCategory = 'all';
    var currentQuery = '';

    var urlParams = new URLSearchParams(window.location.search);
    var urlCat = urlParams.get('cat');
    var urlQ = urlParams.get('q');

    if (urlCat) {
        currentCategory = urlCat.toLowerCase();
    }
    if (urlQ) {
        currentQuery = urlQ.toLowerCase();
        if (searchInput) {
            searchInput.value = urlQ;
        }
    }

    var itemNames = {
        'donat-cokelat': 'Donat Cokelat',
        'donat-glaze': 'Donat Glaze',
        'donat-oreo': 'Donat Oreo',
        'fire-flosss': 'Fire Flosss',
        'choco-bun': 'Choco Bun',
        'cheese-boat': 'Cheese Boat',
        'pepperoni-slice': 'Pepperoni Slice',
        'cheese-slice': 'Cheese Slice',
        'garlic-bread': 'Garlic Bread',
        'ayam-crispy': 'Ayam Crispy',
        'ayam-original': 'Ayam Original',
        'perkedel': 'Perkedel'
    };

    var memoryCart = null;

    function getCart() {
        try {
            if (window.localStorage) {
                var raw = localStorage.getItem('tubespwd_cart');
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        if (!parsed.items) parsed.items = {};
                        return parsed;
                    }
                }
            }
        } catch (err) {}
        if (memoryCart) return memoryCart;
        return { merchant: '', items: {} };
    }

    function saveCart(cart) {
        memoryCart = cart;
        try {
            if (window.localStorage) {
                localStorage.setItem('tubespwd_cart', JSON.stringify(cart));
            }
        } catch (err) {}
    }

    var toastTimeout = null;
    function showCartToast(msg, merchantKey) {
        var existingToast = document.getElementById('catalogCartToast');
        if (!existingToast) {
            existingToast = document.createElement('div');
            existingToast.id = 'catalogCartToast';
            existingToast.className = 'cart-toast-bar';
            document.body.appendChild(existingToast);
        }

        while (existingToast.firstChild) {
            existingToast.removeChild(existingToast.firstChild);
        }

        var checkoutUrl = 'checkout.xhtml?merchant=' + encodeURIComponent(merchantKey || 'pizzahut');

        var msgSpan = document.createElement('span');
        msgSpan.className = 'cart-toast-msg';
        msgSpan.textContent = msg;

        var btnLink = document.createElement('a');
        btnLink.href = checkoutUrl;
        btnLink.className = 'cart-toast-btn';
        btnLink.textContent = 'Klaim Pangan \u2192';

        existingToast.appendChild(msgSpan);
        existingToast.appendChild(btnLink);

        existingToast.classList.add('show');
        if (toastTimeout) {
            clearTimeout(toastTimeout);
        }
        toastTimeout = setTimeout(function () {
            existingToast.classList.remove('show');
        }, 3500);
    }

    function updateCartUI() {
        if (!isAuthenticated) return;

        var cart = getCart();
        var addButtons = document.querySelectorAll('.btn-add-mini-item');
        addButtons.forEach(function (btn) {
            var itemKey = btn.getAttribute('data-item');
            var merchantKey = btn.getAttribute('data-merchant');
            var badge = btn.querySelector('.mini-item-badge');
            var qty = 0;
            if (cart.merchant === merchantKey && cart.items && cart.items[itemKey]) {
                qty = cart.items[itemKey];
            }
            if (badge) {
                if (qty > 0) {
                    badge.textContent = qty;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });

        var claimButtons = document.querySelectorAll('.btn-merchant-claim');
        claimButtons.forEach(function (btn) {
            var merchantKey = btn.getAttribute('data-merchant');
            var totalMerchantItems = 0;
            if (cart.merchant === merchantKey && cart.items) {
                for (var k in cart.items) {
                    if (cart.items.hasOwnProperty(k)) {
                        totalMerchantItems += cart.items[k];
                    }
                }
            }
            if (totalMerchantItems > 0) {
                btn.textContent = 'Klaim Pangan (' + totalMerchantItems + ' item)';
            } else {
                btn.textContent = 'Klaim Pangan';
            }
        });
    }

    var addMiniButtons = document.querySelectorAll('.btn-add-mini-item');
    addMiniButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!isAuthenticated) {
                window.location.href = 'login.xhtml';
                return;
            }

            var itemKey = this.getAttribute('data-item');
            var merchantKey = this.getAttribute('data-merchant');
            if (!itemKey || !merchantKey) return;

            var cart = getCart();
            if (cart.merchant && cart.merchant !== merchantKey) {
                var hasOtherItems = false;
                for (var key in cart.items) {
                    if (cart.items.hasOwnProperty(key) && cart.items[key] > 0) {
                        hasOtherItems = true;
                        break;
                    }
                }
                if (hasOtherItems) {
                    cart.items = {};
                }
            }

            cart.merchant = merchantKey;
            cart.items[itemKey] = (cart.items[itemKey] || 0) + 1;
            saveCart(cart);
            updateCartUI();

            var displayName = itemNames[itemKey] || itemKey;
            showCartToast(displayName + ' ditambahkan ke keranjang!', merchantKey);
        });
    });

    var claimButtons = document.querySelectorAll('.btn-merchant-claim');
    claimButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!isAuthenticated) {
                window.location.href = 'login.xhtml';
                return;
            }
            var merchantKey = this.getAttribute('data-merchant') || 'dunkin';
            window.location.href = 'checkout.xhtml?merchant=' + encodeURIComponent(merchantKey);
        });
    });

    function applyFilter() {
        var visibleCount = 0;

        merchantCards.forEach(function (card) {
            var cardCategories = (card.getAttribute('data-category') || '').toLowerCase().split(' ');
            var cardText = card.textContent.toLowerCase();

            var categoryMatch = (currentCategory === 'all') || (cardCategories.indexOf(currentCategory) !== -1);
            var queryMatch = !currentQuery || (cardText.indexOf(currentQuery) !== -1);

            if (categoryMatch && queryMatch) {
                card.classList.remove('is-hidden');
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.classList.add('is-hidden');
                card.style.display = 'none';
            }
        });

        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }
        }

        categoryPills.forEach(function (pill) {
            var pillCat = pill.getAttribute('data-cat') || 'all';
            if (pillCat === currentCategory) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    categoryPills.forEach(function (pill) {
        pill.addEventListener('click', function (e) {
            e.preventDefault();
            var targetCat = this.getAttribute('data-cat') || 'all';
            currentCategory = targetCat;

            applyFilter();

            try {
                if (window.history && window.history.pushState) {
                    var newUrl = new URL(window.location.href);
                    if (currentCategory === 'all') {
                        newUrl.searchParams.delete('cat');
                    } else {
                        newUrl.searchParams.set('cat', currentCategory);
                    }
                    window.history.pushState({}, '', newUrl.toString());
                }
            } catch (err) {}
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentQuery = this.value.trim().toLowerCase();
            applyFilter();
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilter();
        });
    }

    var resetBtn = document.getElementById('btnResetFilter');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            currentCategory = 'all';
            currentQuery = '';
            if (searchInput) {
                searchInput.value = '';
            }
            applyFilter();

            try {
                if (window.history && window.history.pushState) {
                    var newUrl = new URL(window.location.href);
                    newUrl.searchParams.delete('cat');
                    newUrl.searchParams.delete('q');
                    window.history.pushState({}, '', newUrl.toString());
                }
            } catch (err) {}
        });
    }

    var profileDropdown = document.querySelector('.profile-consumer-dropdown');
    var profilePillBtn = document.querySelector('.profile-consumer-pill');
    if (profileDropdown && profilePillBtn) {
        profilePillBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = profileDropdown.classList.toggle('is-open');
            profilePillBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('is-open');
                profilePillBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                profileDropdown.classList.remove('is-open');
                profilePillBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    var logoutBtn = document.getElementById('btnLogout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            try {
                localStorage.removeItem('tubespwd_cart');
            } catch (err) {}
        });
    }

    var foodDatabase = {
        'donat-glaze': {
            name: 'Donat Glaze (Paket 3 Pcs)',
            merchant: "Dunkin' Donuts Sudirman",
            merchantLogo: 'assets/images/merchant_dunkin.png',
            category: 'Roti & Kue',
            stock: 'Stok: 5 Paket',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp21.000',
            desc: 'Donat surplus varian glaze madu segar produksi pagi hari, higienis dan siap dikonsumsi.',
            deadline: 'Hari ini, 21:00 WIB',
            location: "Gerai Dunkin' Sudirman (1,2 km)",
            img: 'assets/images/menu_donat_glaze.png',
            merchantKey: 'dunkin'
        },
        'donat-cokelat': {
            name: 'Donat Cokelat Tabur (3 Pcs)',
            merchant: "Dunkin' Donuts Sudirman",
            merchantLogo: 'assets/images/merchant_dunkin.png',
            category: 'Roti & Kue',
            stock: 'Stok: 5 Paket',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp21.000',
            desc: 'Donat cokelat klasik bertabur meises cokelat premium, dibuat dari bahan bermutu dan bersih.',
            deadline: 'Hari ini, 21:00 WIB',
            location: "Gerai Dunkin' Sudirman (1,2 km)",
            img: 'assets/images/menu_donat_cokelat.png',
            merchantKey: 'dunkin'
        },
        'donat-oreo': {
            name: 'Donat Oreo Delight (3 Pcs)',
            merchant: "Dunkin' Donuts Sudirman",
            merchantLogo: 'assets/images/merchant_dunkin.png',
            category: 'Roti & Kue',
            stock: 'Stok: 2 Paket',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp24.000',
            desc: 'Donat lembut berbalut krim vanila dan remahan biskuit oreo gurih yang lezat.',
            deadline: 'Hari ini, 21:00 WIB',
            location: "Gerai Dunkin' Sudirman (1,2 km)",
            img: 'assets/images/menu_donat_oreo.png',
            merchantKey: 'dunkin'
        },
        'fire-flosss': {
            name: 'Fire Flosss Abon Pedas',
            merchant: 'BreadTalk Seturan',
            merchantLogo: 'assets/images/merchant_breadtalk.png',
            category: 'Roti & Kue',
            stock: 'Stok: 6 Pcs',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp15.000',
            desc: 'Roti lembut berlumur saus spesial dan taburan abon ayam pedas gurih yang melimpah.',
            deadline: 'Hari ini, 20:30 WIB',
            location: 'Gerai BreadTalk Seturan (0,9 km)',
            img: 'assets/images/menu_fire_flosss.png',
            merchantKey: 'breadtalk'
        },
        'choco-bun': {
            name: 'Choco Bun Cokelat Lumer',
            merchant: 'BreadTalk Seturan',
            merchantLogo: 'assets/images/merchant_breadtalk.png',
            category: 'Roti & Kue',
            stock: 'Stok: 4 Pcs',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp12.000',
            desc: 'Roti manis dengan isian pasta cokelat pekat yang lumer dan nikmat saat disantap.',
            deadline: 'Hari ini, 20:30 WIB',
            location: 'Gerai BreadTalk Seturan (0,9 km)',
            img: 'assets/images/menu_choco_bun.png',
            merchantKey: 'breadtalk'
        },
        'cheese-boat': {
            name: 'Cheese Boat Gurih',
            merchant: 'BreadTalk Seturan',
            merchantLogo: 'assets/images/merchant_breadtalk.png',
            category: 'Roti & Kue',
            stock: 'Stok: 5 Pcs',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp14.000',
            desc: 'Roti berbentuk perahu bertabur keju cheddar gurih panggang yang harum dan renyah.',
            deadline: 'Hari ini, 20:30 WIB',
            location: 'Gerai BreadTalk Seturan (0,9 km)',
            img: 'assets/images/menu_cheese_boat.png',
            merchantKey: 'breadtalk'
        },
        'pepperoni-slice': {
            name: 'Pepperoni Slice Pan Pizza',
            merchant: 'Pizza Hut Gejayan',
            merchantLogo: 'assets/images/merchant_pizzahut.png',
            category: 'Cepat Saji',
            stock: 'Stok: 6 Slice',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp18.000',
            desc: 'Potongan pizza pan tebal dengan saus tomat khas, lelehan keju mozzarella dan irisan daging pepperoni sapi.',
            deadline: 'Hari ini, 17:00 WIB',
            location: 'Gerai Pizza Hut Gejayan (1,8 km)',
            img: 'assets/images/menu_pepperoni.png',
            merchantKey: 'pizzahut'
        },
        'cheese-slice': {
            name: 'Cheese Slice Pan Pizza',
            merchant: 'Pizza Hut Gejayan',
            merchantLogo: 'assets/images/merchant_pizzahut.png',
            category: 'Cepat Saji',
            stock: 'Stok: 4 Slice',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp16.000',
            desc: 'Pizza gurih kaya keju dengan pinggiran pan renyah, aman dan layak santap untuk keluarga.',
            deadline: 'Hari ini, 17:00 WIB',
            location: 'Gerai Pizza Hut Gejayan (1,8 km)',
            img: 'assets/images/menu_cheese_slice.png',
            merchantKey: 'pizzahut'
        },
        'garlic-bread': {
            name: 'Garlic Bread Panggang',
            merchant: 'Pizza Hut Gejayan',
            merchantLogo: 'assets/images/merchant_pizzahut.png',
            category: 'Cepat Saji',
            stock: 'Stok: 5 Porsi',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp15.000',
            desc: 'Roti panggang bawang putih mentega gurih renyah, pendamping sempurna santap pangan bergizi.',
            deadline: 'Hari ini, 17:00 WIB',
            location: 'Gerai Pizza Hut Gejayan (1,8 km)',
            img: 'assets/images/menu_garlic_bread.png',
            merchantKey: 'pizzahut'
        },
        'ayam-crispy': {
            name: 'Ayam Crispy Renyah (2 Pcs)',
            merchant: 'KFC Sudirman',
            merchantLogo: 'assets/images/merchant_kfc.png',
            category: 'Makanan Berat',
            stock: 'Stok: 8 Porsi',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp35.000',
            desc: 'Ayam goreng tepung renyah bumbu rahasia KFC, masih hangat dalam warmer dan terjaga kualitasnya.',
            deadline: 'Hari ini, 21:00 WIB',
            location: 'Gerai KFC Sudirman (1,1 km)',
            img: 'assets/images/menu_ayam_crispy.png',
            merchantKey: 'kfc'
        },
        'ayam-original': {
            name: 'Ayam Original Juicy (2 Pcs)',
            merchant: 'KFC Sudirman',
            merchantLogo: 'assets/images/merchant_kfc.png',
            category: 'Makanan Berat',
            stock: 'Stok: 6 Porsi',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp35.000',
            desc: 'Ayam goreng resep original gurih rempah meresap sampai ke tulang, bergizi tinggi untuk keluarga.',
            deadline: 'Hari ini, 21:00 WIB',
            location: 'Gerai KFC Sudirman (1,1 km)',
            img: 'assets/images/menu_ayam_original.png',
            merchantKey: 'kfc'
        },
        'perkedel': {
            name: 'Perkedel Kentang Lembut',
            merchant: 'KFC Sudirman',
            merchantLogo: 'assets/images/merchant_kfc.png',
            category: 'Makanan Berat',
            stock: 'Stok: 10 Pcs',
            price: 'Rp 0 (Gratis)',
            originalPrice: 'Harga Normal: Rp10.000',
            desc: 'Perkedel kentang tumbuk lembut berbumbu gurih sedap, dipanggang bersih dan higienis.',
            deadline: 'Hari ini, 21:00 WIB',
            location: 'Gerai KFC Sudirman (1,1 km)',
            img: 'assets/images/menu_perkedel.png',
            merchantKey: 'kfc'
        }
    };

    var modalBackdrop = document.createElement('div');
    modalBackdrop.className = 'food-modal-backdrop';
    modalBackdrop.id = 'foodDetailModalBackdrop';
    modalBackdrop.innerHTML = [
        '<div class="food-modal-card" id="foodDetailModalCard">',
        '    <div class="food-modal-header">',
        '        <div class="food-modal-merchant">',
        '            <img src="assets/images/merchant_dunkin.png" alt="Merchant" class="food-modal-merchant-logo" id="modalMerchantLogo" />',
        '            <div>',
        '                <div class="food-modal-merchant-name" id="modalMerchantName">Dunkin\' Donuts Sudirman</div>',
        '                <div class="food-modal-merchant-sub" id="modalMerchantSub">Gerai Resmi Donatur &bull; 1,2 km dari Anda</div>',
        '            </div>',
        '        </div>',
        '        <button type="button" class="food-modal-close-btn" id="modalCloseBtn" title="Tutup">&times;</button>',
        '    </div>',
        '    <div class="food-modal-img-wrap">',
        '        <img src="assets/images/menu_donat_glaze.png" alt="Makanan" class="food-modal-img" id="modalFoodImg" />',
        '        <div class="food-modal-badges">',
        '            <span class="food-modal-cat-badge" id="modalFoodCat">Roti &amp; Kue</span>',
        '            <span class="food-modal-stock-badge" id="modalFoodStock">Stok: 5 Paket</span>',
        '        </div>',
        '    </div>',
        '    <div class="food-modal-info-body">',
        '        <div class="food-modal-title-row">',
        '            <h3 class="food-modal-title" id="modalFoodTitle">Donat Glaze (Paket 3 Pcs)</h3>',
        '            <div class="food-modal-price-box">',
        '                <div class="food-modal-price" id="modalFoodPrice">Rp 0 (Gratis)</div>',
        '                <div class="food-modal-price-ori" id="modalFoodPriceOri">Harga Normal: Rp21.000</div>',
        '            </div>',
        '        </div>',
        '        <p class="food-modal-desc" id="modalFoodDesc">Donat surplus varian glaze madu segar produksi pagi hari, higienis dan siap dikonsumsi.</p>',
        '        <hr class="food-modal-divider" />',
        '        <div class="food-modal-quick-info">',
        '            <div class="food-modal-info-col">',
        '                <span class="food-modal-info-label">Batas Pengambilan di Gerai:</span>',
        '                <span class="food-modal-info-val amber" id="modalFoodDeadline">Hari ini, 21:00 WIB</span>',
        '            </div>',
        '            <div class="food-modal-info-col">',
        '                <span class="food-modal-info-label">Lokasi Toko Produsen:</span>',
        '                <span class="food-modal-info-val" id="modalFoodLocation">Gerai Dunkin\' Sudirman (1,2 km)</span>',
        '            </div>',
        '        </div>',
        '        <a href="#" class="food-modal-claim-btn" id="modalClaimBtn">Klaim Pangan Sekarang</a>',
        '        <a href="#" class="food-modal-cancel-link" id="modalCancelLink">Tutup Rincian</a>',
        '    </div>',
        '</div>'
    ].join('\n');
    document.body.appendChild(modalBackdrop);

    var modalCloseBtn = document.getElementById('modalCloseBtn');
    var modalCancelLink = document.getElementById('modalCancelLink');
    var modalMerchantLogo = document.getElementById('modalMerchantLogo');
    var modalMerchantName = document.getElementById('modalMerchantName');
    var modalMerchantSub = document.getElementById('modalMerchantSub');
    var modalFoodImg = document.getElementById('modalFoodImg');
    var modalFoodCat = document.getElementById('modalFoodCat');
    var modalFoodStock = document.getElementById('modalFoodStock');
    var modalFoodTitle = document.getElementById('modalFoodTitle');
    var modalFoodPrice = document.getElementById('modalFoodPrice');
    var modalFoodPriceOri = document.getElementById('modalFoodPriceOri');
    var modalFoodDesc = document.getElementById('modalFoodDesc');
    var modalFoodDeadline = document.getElementById('modalFoodDeadline');
    var modalFoodLocation = document.getElementById('modalFoodLocation');
    var modalClaimBtn = document.getElementById('modalClaimBtn');

    function openFoodModal(itemKey) {
        var data = foodDatabase[itemKey];
        if (!data) return;

        modalMerchantLogo.src = data.merchantLogo;
        modalMerchantName.textContent = data.merchant;
        modalMerchantSub.innerHTML = 'Gerai Resmi Donatur &bull; ' + data.location;
        modalFoodImg.src = data.img;
        modalFoodImg.alt = data.name;
        modalFoodCat.textContent = data.category;
        modalFoodStock.textContent = data.stock;
        modalFoodTitle.textContent = data.name;
        modalFoodPrice.textContent = data.price;
        modalFoodPriceOri.textContent = data.originalPrice;
        modalFoodDesc.textContent = data.desc;
        modalFoodDeadline.textContent = data.deadline;
        modalFoodLocation.textContent = data.location;

        if (isAuthenticated) {
            modalClaimBtn.href = 'checkout.xhtml?merchant=' + encodeURIComponent(data.merchantKey) + '&item=' + encodeURIComponent(itemKey);
            modalClaimBtn.textContent = 'Klaim Pangan Sekarang';
        } else {
            modalClaimBtn.href = 'login.xhtml';
            modalClaimBtn.textContent = 'Masuk Akun untuk Mengklaim';
        }

        modalBackdrop.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeFoodModal() {
        modalBackdrop.classList.remove('is-active');
        document.body.style.overflow = '';
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeFoodModal);
    }
    if (modalCancelLink) {
        modalCancelLink.addEventListener('click', function (e) {
            e.preventDefault();
            closeFoodModal();
        });
    }
    modalBackdrop.addEventListener('click', function (e) {
        if (e.target === modalBackdrop) {
            closeFoodModal();
        }
    });
    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && modalBackdrop.classList.contains('is-active')) {
            closeFoodModal();
        }
    });

    var miniCards = document.querySelectorAll('.mini-food-card');
    miniCards.forEach(function (card) {
        var addBtn = card.querySelector('.btn-add-mini-item');
        var itemKey = addBtn ? addBtn.getAttribute('data-item') : null;
        if (!itemKey) return;

        var imgWrap = card.querySelector('.mini-food-img-wrap');
        var titleEl = card.querySelector('.mini-food-title');

        if (imgWrap) {
            imgWrap.style.cursor = 'pointer';
            imgWrap.addEventListener('click', function (e) {
                e.preventDefault();
                openFoodModal(itemKey);
            });
        }
        if (titleEl) {
            titleEl.style.cursor = 'pointer';
            titleEl.addEventListener('click', function (e) {
                e.preventDefault();
                openFoodModal(itemKey);
            });
        }
    });

    applyFilter();
    updateCartUI();
});