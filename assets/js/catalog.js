document.addEventListener('DOMContentLoaded', function () {
    var categoryPills = document.querySelectorAll('.category-pill');
    var merchantCards = document.querySelectorAll('.merchant-main-card');
    var searchInput = document.querySelector('.catalog-search-input');
    var searchForm = document.querySelector('.catalog-search-form');
    var emptyState = document.getElementById('catalogEmptyState');
    var isViews = window.location.pathname.indexOf('/views/') !== -1;

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

    function escapeXml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&(?!(amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

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

    function syncBackendCart(action, itemKey, merchantKey, qty) {
        if (!window.fetch || window.location.protocol === 'file:') return;
        try {
            var endpoint = (isViews ? '../' : '') + 'actions/cart_action.php?action=' + encodeURIComponent(action);
            if (itemKey) endpoint += '&item=' + encodeURIComponent(itemKey);
            if (merchantKey) endpoint += '&merchant=' + encodeURIComponent(merchantKey);
            if (qty) endpoint += '&qty=' + encodeURIComponent(qty);
            fetch(endpoint, { method: 'POST' }).catch(function () {});
        } catch (e) {}
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
            syncBackendCart('add', itemKey, merchantKey, 1);
            updateCartUI();

            var displayName = itemNames[itemKey] || itemKey;
            showCartToast(displayName + ' ditambahkan ke keranjang!', merchantKey);
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

    applyFilter();
    updateCartUI();
});
