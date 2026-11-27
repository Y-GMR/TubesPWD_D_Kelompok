(function () {
    var foodDatabase = {
        'donat-glaze': {
            name: 'Donat Glaze (Paket 3 Pcs)',
            merchant: "Dunkin' Donuts Sudirman",
            merchantLogo: 'assets/images/merchant_dunkin.png',
            category: 'Roti & Kue',
            stock: 'Stok: 5',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 5',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 2',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 6',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 4',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 5',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 6',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 4',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 5',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 8',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 6',
            price: 'Rp 0 (Gratis)',
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
            stock: 'Stok: 10',
            price: 'Rp 0 (Gratis)',
            deadline: 'Hari ini, 21:00 WIB',
            location: 'Gerai KFC Sudirman (1,1 km)',
            img: 'assets/images/menu_perkedel.png',
            merchantKey: 'kfc'
        }
    };

    function initFoodDetail() {
        var searchInput = document.querySelector('.catalog-search-input');
        if (searchInput && !searchInput.placeholder) {
            searchInput.placeholder = 'Cari makanan, resto, atau lokasi pengambilan';
        }
        var urlParams = new URLSearchParams(window.location.search);
        var itemKey = urlParams.get('item') || 'donat-glaze';
        var data = foodDatabase[itemKey] || foodDatabase['donat-glaze'];

        var modalMerchantLogo = document.getElementById('modalMerchantLogo');
        var modalMerchantName = document.getElementById('modalMerchantName');
        var modalMerchantSub = document.getElementById('modalMerchantSub');
        var modalFoodImg = document.getElementById('modalFoodImg');
        var modalFoodCat = document.getElementById('modalFoodCat');
        var modalFoodStock = document.getElementById('modalFoodStock');
        var modalFoodTitle = document.getElementById('modalFoodTitle');
        var modalFoodPrice = document.getElementById('modalFoodPrice');
        var modalFoodDeadline = document.getElementById('modalFoodDeadline');
        var modalFoodLocation = document.getElementById('modalFoodLocation');
        var modalAddToCartBtn = document.getElementById('modalAddToCartBtn');
        var modalCloseBtn = document.getElementById('modalCloseBtn');
        var modalCancelLink = document.getElementById('modalCancelLink');
        var modalBackdrop = document.getElementById('foodDetailModalBackdrop');

        if (modalMerchantLogo) modalMerchantLogo.src = data.merchantLogo;
        if (modalMerchantName) modalMerchantName.textContent = data.merchant;
        if (modalMerchantSub) modalMerchantSub.textContent = 'Gerai Resmi Donatur \u2022 ' + data.location;
        if (modalFoodImg) {
            modalFoodImg.src = data.img;
            modalFoodImg.alt = data.name;
        }
        if (modalFoodCat) modalFoodCat.textContent = data.category;
        if (modalFoodStock) modalFoodStock.textContent = data.stock;
        if (modalFoodTitle) modalFoodTitle.textContent = data.name;
        if (modalFoodPrice) modalFoodPrice.textContent = data.price;
        if (modalFoodDeadline) modalFoodDeadline.textContent = data.deadline;
        if (modalFoodLocation) modalFoodLocation.textContent = data.location;

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

        var isGuest = (document.referrer.indexOf('index.xhtml') !== -1) ||
                      (window.location.search.indexOf('guest=true') !== -1) ||
                      (localStorage.getItem('is_authenticated') === 'false');

        if (isGuest) {
            document.body.setAttribute('data-auth', 'false');
            var navCtaWrap = document.querySelector('.nav-cta-wrap');
            if (navCtaWrap) {
                while (navCtaWrap.firstChild) {
                    navCtaWrap.removeChild(navCtaWrap.firstChild);
                }
                var shareLink = document.createElement('a');
                shareLink.href = 'register.xhtml?role=donor';
                shareLink.className = 'btn-share-header';
                shareLink.textContent = 'Saya ingin berbagi';
                navCtaWrap.appendChild(shareLink);
            }
        }

        var isAuthenticated = !isGuest && (document.body.getAttribute('data-auth') === 'true' || localStorage.getItem('is_authenticated') === 'true');

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
            return { merchant: '', items: {} };
        }

        function saveCart(cart) {
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

            var checkoutUrl = 'checkout.xhtml?merchant=' + encodeURIComponent(merchantKey || 'dunkin');

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

        function updateButtonText() {
            if (!modalAddToCartBtn) return;
            if (!isAuthenticated) {
                modalAddToCartBtn.textContent = 'Masuk Akun untuk Menambahkan';
                return;
            }
            var cart = getCart();
            var count = 0;
            if (cart.merchant === data.merchantKey && cart.items && cart.items[itemKey]) {
                count = cart.items[itemKey];
            }
            if (count > 0) {
                modalAddToCartBtn.textContent = 'Tambah ke Keranjang (' + count + ' di Keranjang)';
            } else {
                modalAddToCartBtn.textContent = 'Tambah ke Keranjang';
            }
        }

        if (modalAddToCartBtn) {
            updateButtonText();
            modalAddToCartBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!isAuthenticated) {
                    window.location.href = 'login.xhtml';
                    return;
                }

                var cart = getCart();
                if (cart.merchant && cart.merchant !== data.merchantKey) {
                    var hasOtherItems = false;
                    for (var k in cart.items) {
                        if (cart.items.hasOwnProperty(k) && cart.items[k] > 0) {
                            hasOtherItems = true;
                            break;
                        }
                    }
                    if (hasOtherItems) {
                        cart.items = {};
                    }
                }

                cart.merchant = data.merchantKey;
                cart.items[itemKey] = (cart.items[itemKey] || 0) + 1;
                saveCart(cart);

                updateButtonText();
                showCartToast(data.name + ' ditambahkan ke keranjang!', data.merchantKey);
            });
        }

        function goBack(e) {
            if (e) e.preventDefault();
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = isAuthenticated ? 'food_catalogue.xhtml' : 'index.xhtml';
            }
        }

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', goBack);
        }
        if (modalCancelLink) {
            modalCancelLink.addEventListener('click', goBack);
        }
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (e) {
                if (e.target === modalBackdrop) {
                    goBack(e);
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                goBack(e);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFoodDetail);
    } else {
        initFoodDetail();
    }
})();
