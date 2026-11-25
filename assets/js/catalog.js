document.addEventListener('DOMContentLoaded', function () {
    var categoryPills = document.querySelectorAll('.category-pill');
    var merchantCards = document.querySelectorAll('.merchant-main-card');
    var searchInput = document.querySelector('.catalog-search-input');
    if (searchInput && !searchInput.placeholder) {
        searchInput.placeholder = 'Cari makanan, resto, atau lokasi pengambilan';
    }
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

    var activeRole = urlParams.get('role');
    if (!activeRole) {
        try {
            activeRole = localStorage.getItem('tubespwd_role');
            if (!activeRole) {
                activeRole = sessionStorage.getItem('tubespwd_role');
            }
        } catch (err) {}
    }
    if (!activeRole) {
        if (document.referrer) {
            if (document.referrer.indexOf('producer') !== -1) {
                activeRole = 'produsen';
            }
        }
    }

    if (window.location.pathname.indexOf('producer_dashboard.xhtml') !== -1) {
        activeRole = 'produsen';
        try {
            localStorage.setItem('tubespwd_role', 'produsen');
            sessionStorage.setItem('tubespwd_role', 'produsen');
        } catch (err) {}
    }

    if (activeRole === 'produsen') {
        try {
            localStorage.setItem('tubespwd_role', 'produsen');
            sessionStorage.setItem('tubespwd_role', 'produsen');
        } catch (err) {}

        var avatarImg = document.querySelector('.profile-consumer-avatar');
        var nameSpan = document.querySelector('.profile-consumer-name');
        var roleSpan = document.querySelector('.profile-consumer-role');
        var pillBtn = document.querySelector('.profile-consumer-pill');
        var dropdownMenu = document.getElementById('profileDropdownMenu');

        if (avatarImg) {
            avatarImg.src = 'assets/images/producer/avatar_produsen.png';
            avatarImg.alt = 'Avatar KFC Sudirman';
        }
        if (nameSpan) {
            nameSpan.textContent = 'KFC Sudirman';
        }
        if (roleSpan) {
            roleSpan.textContent = 'Produsen';
        }
        if (pillBtn) {
            pillBtn.setAttribute('title', 'Profil Produsen - KFC Sudirman');
        }

        if (dropdownMenu) {
            while (dropdownMenu.firstChild) {
                dropdownMenu.removeChild(dropdownMenu.firstChild);
            }

            var itemDashboard = document.createElement('a');
            itemDashboard.href = 'producer_dashboard.xhtml';
            itemDashboard.className = 'profile-dropdown-item';
            itemDashboard.textContent = 'Dashboard Produsen';
            dropdownMenu.appendChild(itemDashboard);

            var itemProfile = document.createElement('a');
            itemProfile.href = 'profile.xhtml';
            itemProfile.className = 'profile-dropdown-item';
            itemProfile.textContent = 'Pengaturan Profil';
            dropdownMenu.appendChild(itemProfile);

            var itemClaim = document.createElement('a');
            itemClaim.href = 'claim.xhtml';
            itemClaim.className = 'profile-dropdown-item';
            itemClaim.textContent = 'Kelola Klaim';
            dropdownMenu.appendChild(itemClaim);

            var itemInventory = document.createElement('a');
            itemInventory.href = 'inventory.xhtml';
            itemInventory.className = 'profile-dropdown-item';
            itemInventory.textContent = 'Kelola Inventaris';
            dropdownMenu.appendChild(itemInventory);

            var divider = document.createElement('div');
            divider.className = 'profile-dropdown-divider';
            dropdownMenu.appendChild(divider);

            var itemLogout = document.createElement('a');
            itemLogout.href = 'login.xhtml';
            itemLogout.className = 'profile-dropdown-item profile-dropdown-logout';
            itemLogout.id = 'btnLogout';
            itemLogout.textContent = 'Keluar';
            dropdownMenu.appendChild(itemLogout);
        }

        var navMenuList = document.querySelector('.nav-menu-list');
        if (navMenuList && window.location.pathname.indexOf('food_catalogue.xhtml') !== -1) {
            var existingDashboardLink = navMenuList.querySelector('.nav-link-producer-dashboard');
            if (!existingDashboardLink) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = 'producer_dashboard.xhtml';
                a.className = 'nav-link-producer-dashboard';
                a.textContent = 'Dashboard Produsen';
                li.appendChild(a);
                navMenuList.insertBefore(li, navMenuList.firstChild);
            }
        }

        var navLogo = document.querySelector('.nav-logo-link');
        if (navLogo && window.location.pathname.indexOf('food_catalogue.xhtml') !== -1) {
            navLogo.href = 'food_catalogue.xhtml?role=produsen';
        }

        if (searchForm && window.location.pathname.indexOf('food_catalogue.xhtml') !== -1) {
            var hiddenRole = searchForm.querySelector('input[name="role"]');
            if (!hiddenRole) {
                hiddenRole = document.createElement('input');
                hiddenRole.type = 'hidden';
                hiddenRole.name = 'role';
                hiddenRole.value = 'produsen';
                searchForm.appendChild(hiddenRole);
            }
        }

        var catPills = document.querySelectorAll('.category-pill');
        catPills.forEach(function (cp) {
            var href = cp.getAttribute('href');
            if (href && href.indexOf('role=produsen') === -1) {
                cp.setAttribute('href', href + (href.indexOf('?') !== -1 ? '&role=produsen' : '?role=produsen'));
            }
        });
    } else {
        if (activeRole === 'konsumen') {
            try {
                localStorage.setItem('tubespwd_role', 'konsumen');
                sessionStorage.setItem('tubespwd_role', 'konsumen');
            } catch (err) {}
        }
    }

    var logoutBtn = document.getElementById('btnLogout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            try {
                localStorage.removeItem('tubespwd_role');
                sessionStorage.removeItem('tubespwd_role');
                localStorage.removeItem('tubespwd_cart');
            } catch (err) {}
        });
    }

    var miniCards = document.querySelectorAll('.mini-food-card');
    miniCards.forEach(function (card) {
        var addBtn = card.querySelector('.btn-add-mini-item');
        var itemKey = addBtn ? addBtn.getAttribute('data-item') : null;
        if (!itemKey) return;

        card.style.cursor = 'pointer';
        card.addEventListener('click', function (e) {
            if (e.target && e.target.closest && e.target.closest('.btn-add-mini-item')) {
                return;
            }
            window.location.href = 'food_detail.xhtml?item=' + encodeURIComponent(itemKey);
        });
    });

    applyFilter();
    updateCartUI();
});