document.addEventListener('DOMContentLoaded', function () {
    var isViews = window.location.pathname.indexOf('/views/') !== -1;

    var merchantsData = {
        'pizzahut': {
            name: 'Pizza Hut Gejayan',
            logo: 'assets/images/merchant_pizzahut.png',
            storeName: 'Gerai Pizza Hut Gejayan',
            poskoName: 'Gerai Pizza Hut Gejayan',
            poskoAddr: 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,8 km',
            gps: '-7.7654, 110.3891',
            deadline: '17:00 WIB',
            defaultItems: ['pepperoni-slice', 'cheese-slice']
        },
        'dunkin': {
            name: 'Dunkin Donuts Sudirman',
            logo: 'assets/images/merchant_dunkin.png',
            storeName: 'Gerai Dunkin Donuts Sudirman',
            poskoName: 'Gerai Dunkin Donuts Sudirman',
            poskoAddr: 'Jl. Jend. Sudirman No. 54, Gowongan, Kec. Jetis, Kota Yogyakarta, DIY 55233',
            distance: '1,2 km',
            gps: '-7.7829, 110.3671',
            deadline: '15:00 WIB',
            defaultItems: ['donat-glaze', 'donat-cokelat']
        },
        'breadtalk': {
            name: 'BreadTalk Seturan',
            logo: 'assets/images/merchant_breadtalk.png',
            storeName: 'Gerai BreadTalk Seturan',
            poskoName: 'Gerai BreadTalk Seturan',
            poskoAddr: 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '0,9 km',
            gps: '-7.7735, 110.4092',
            deadline: '16:00 WIB',
            defaultItems: ['fire-flosss', 'choco-bun']
        },
        'kfc': {
            name: 'KFC Babarsari',
            logo: 'assets/images/merchant_kfc.png',
            storeName: 'Gerai KFC Babarsari',
            poskoName: 'Gerai KFC Babarsari',
            poskoAddr: 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,4 km',
            gps: '-7.7788, 110.4123',
            deadline: '18:00 WIB',
            defaultItems: ['ayam-crispy', 'ayam-original']
        }
    };

    var itemsDatabase = {
        'donat-cokelat': {
            name: 'Donat Cokelat',
            merchant: 'dunkin',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp7.000',
            img: 'assets/images/menu_donat_cokelat.png'
        },
        'donat-glaze': {
            name: 'Donat Glaze (Paket 3 Pcs)',
            merchant: 'dunkin',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp7.000',
            img: 'assets/images/menu_donat_glaze.png'
        },
        'donat-oreo': {
            name: 'Donat Oreo',
            merchant: 'dunkin',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp6.000',
            img: 'assets/images/menu_donat_oreo.png'
        },
        'fire-flosss': {
            name: 'Fire Flosss (Paket 2 Pcs)',
            merchant: 'breadtalk',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp8.000',
            img: 'assets/images/menu_fire_flosss.png'
        },
        'choco-bun': {
            name: 'Choco Bun',
            merchant: 'breadtalk',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp7.500',
            img: 'assets/images/menu_choco_bun.png'
        },
        'cheese-boat': {
            name: 'Cheese Boat',
            merchant: 'breadtalk',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            price: 'Rp9.000',
            img: 'assets/images/menu_cheese_boat.png'
        },
        'pepperoni-slice': {
            name: 'Pepperoni Slice',
            merchant: 'pizzahut',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp12.000',
            img: 'assets/images/menu_pepperoni.png'
        },
        'cheese-slice': {
            name: 'Cheese Slice',
            merchant: 'pizzahut',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp11.000',
            img: 'assets/images/menu_cheese_slice.png'
        },
        'garlic-bread': {
            name: 'Garlic Bread',
            merchant: 'pizzahut',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp9.000',
            img: 'assets/images/menu_garlic_bread.png'
        },
        'ayam-crispy': {
            name: 'Ayam Crispy (Paket 1 Porsi)',
            merchant: 'kfc',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp11.000',
            img: 'assets/images/menu_ayam_crispy.png'
        },
        'ayam-original': {
            name: 'Ayam Original (Paket 1 Porsi)',
            merchant: 'kfc',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp11.000',
            img: 'assets/images/menu_ayam_original.png'
        },
        'perkedel': {
            name: 'Perkedel',
            merchant: 'kfc',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            price: 'Rp5.000',
            img: 'assets/images/menu_perkedel.png'
        }
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
        } catch (e) {}
        if (memoryCart) return memoryCart;
        return { merchant: '', items: {} };
    }

    function saveCart(cart) {
        memoryCart = cart;
        try {
            if (window.localStorage) {
                localStorage.setItem('tubespwd_cart', JSON.stringify(cart));
            }
        } catch (e) {}
    }

    var urlParams = new URLSearchParams(window.location.search);
    var paramMerchant = (urlParams.get('merchant') || '').toLowerCase();
    var paramItem = (urlParams.get('item') || '').toLowerCase();

    var cart = getCart();

    if (paramItem && itemsDatabase[paramItem]) {
        var itemDef = itemsDatabase[paramItem];
        var itemMerchant = itemDef.merchant;
        if (cart.merchant !== itemMerchant) {
            cart.merchant = itemMerchant;
            cart.items = {};
        }
        cart.items[paramItem] = (cart.items[paramItem] || 0) + 1;
        saveCart(cart);
    } else if (paramMerchant && merchantsData[paramMerchant]) {
        if (cart.merchant && cart.merchant !== paramMerchant) {
            var hasOtherItems = false;
            for (var checkK in cart.items) {
                if (Object.prototype.hasOwnProperty.call(cart.items, checkK) && cart.items[checkK] > 0) {
                    hasOtherItems = true;
                    break;
                }
            }
            if (hasOtherItems) {
                cart.items = {};
            }
        }
        cart.merchant = paramMerchant;
    }

    var currentMerchantKey = cart.merchant || paramMerchant || 'pizzahut';
    if (!merchantsData[currentMerchantKey]) {
        currentMerchantKey = 'pizzahut';
    }
    cart.merchant = currentMerchantKey;

    var hasItems = false;
    for (var k in cart.items) {
        if (Object.prototype.hasOwnProperty.call(cart.items, k) && cart.items[k] > 0) {
            hasItems = true;
            break;
        }
    }

    if (!hasItems) {
        var defItems = merchantsData[currentMerchantKey].defaultItems;
        for (var i = 0; i < defItems.length; i++) {
            cart.items[defItems[i]] = 1;
        }
        saveCart(cart);
    }

    var merchantInfo = merchantsData[currentMerchantKey];

    var logoEl = document.getElementById('merchantLogoImg');
    var mNameEl = document.getElementById('checkoutMerchantName');
    var mPoskoEl = document.getElementById('checkoutMerchantPosko');
    var mDeadlineEl = document.getElementById('checkoutDeadlineTime');

    if (logoEl) {
        var logoPath = (isViews ? '../' : '') + merchantInfo.logo;
        logoEl.setAttribute('src', logoPath);
        logoEl.src = logoPath;
    }
    if (mNameEl) mNameEl.textContent = merchantInfo.name;
    if (mPoskoEl) mPoskoEl.textContent = merchantInfo.storeName + ' \u2022 ' + merchantInfo.distance;
    if (mDeadlineEl) mDeadlineEl.textContent = merchantInfo.deadline;

    var pNameEl = document.getElementById('poskoNameText');
    var pAddrEl = document.getElementById('poskoAddressText');
    var pMetaEl = document.getElementById('poskoMetaText');

    if (pNameEl) pNameEl.textContent = merchantInfo.storeName;
    if (pAddrEl) pAddrEl.textContent = merchantInfo.poskoAddr;
    if (pMetaEl) {
        while (pMetaEl.firstChild) {
            pMetaEl.removeChild(pMetaEl.firstChild);
        }
        pMetaEl.appendChild(document.createTextNode('Jarak: '));
        var spanDist = document.createElement('span');
        spanDist.className = 'posko-meta-orange';
        spanDist.textContent = merchantInfo.distance;
        pMetaEl.appendChild(spanDist);
        pMetaEl.appendChild(document.createTextNode(' dari lokasi Anda \u2022 Koordinat GPS: '));
        var spanGps = document.createElement('span');
        spanGps.className = 'posko-meta-orange';
        spanGps.textContent = merchantInfo.gps;
        pMetaEl.appendChild(spanGps);
    }

    var itemsListContainer = document.getElementById('checkoutItemsList');
    var totalPortionsEl = document.getElementById('checkoutTotalPortions');
    var summaryItemsEl = document.getElementById('summaryTotalItems');
    var summaryQuotaEl = document.getElementById('summaryQuotaStatus');
    var confirmClaimBtn = document.querySelector('.btn-confirm-claim');

    function createEl(tag, className, text) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined && text !== null) el.textContent = text;
        return el;
    }

    function renderCheckoutList() {
        if (!itemsListContainer) return;

        while (itemsListContainer.firstChild) {
            itemsListContainer.removeChild(itemsListContainer.firstChild);
        }

        var totalItemsCount = 0;
        var totalPortionsCount = 0;

        for (var itemKey in cart.items) {
            if (!Object.prototype.hasOwnProperty.call(cart.items, itemKey)) continue;
            var qty = cart.items[itemKey];
            if (qty <= 0) continue;

            var data = itemsDatabase[itemKey];
            if (!data) continue;

            totalItemsCount++;
            totalPortionsCount += qty;

            var itemRow = createEl('div', 'checkout-item-row');
            itemRow.setAttribute('data-key', itemKey);

            var thumbDiv = createEl('div', 'checkout-item-thumb');
            var imgEl = createEl('img');
            imgEl.setAttribute('src', (isViews ? '../' : '') + data.img);
            imgEl.setAttribute('alt', data.name);
            thumbDiv.appendChild(imgEl);

            var infoDiv = createEl('div', 'checkout-item-info');
            var titleEl = createEl('h3', 'checkout-item-name', data.name);

            var cleanCategory = (data.category || '').replace(/&amp;/g, '&');
            var metaEl = createEl('p', 'checkout-item-meta', 'Kategori: ' + cleanCategory + ' \u2022 Kedaluwarsa: ' + data.expiry);

            var priceEl = createEl('p', 'checkout-item-price-tag', 'Rp 0 (Gratis Pangan) ');
            var origPriceSpan = createEl('span', 'checkout-item-price-original', data.price);
            priceEl.appendChild(origPriceSpan);

            infoDiv.appendChild(titleEl);
            infoDiv.appendChild(metaEl);
            infoDiv.appendChild(priceEl);

            var stepperWrap = createEl('div', 'checkout-stepper-wrap');

            var minusBtn = createEl('button', 'btn-step btn-step-minus', '-');
            minusBtn.setAttribute('type', 'button');
            minusBtn.setAttribute('data-key', itemKey);
            minusBtn.setAttribute('title', 'Kurangi');

            var qtyVal = createEl('span', 'step-qty-val', String(qty));

            var plusBtn = createEl('button', 'btn-step btn-step-plus', '+');
            plusBtn.setAttribute('type', 'button');
            plusBtn.setAttribute('data-key', itemKey);
            plusBtn.setAttribute('title', 'Tambah');

            stepperWrap.appendChild(minusBtn);
            stepperWrap.appendChild(qtyVal);
            stepperWrap.appendChild(plusBtn);

            itemRow.appendChild(thumbDiv);
            itemRow.appendChild(infoDiv);
            itemRow.appendChild(stepperWrap);

            itemsListContainer.appendChild(itemRow);
        }

        if (totalItemsCount === 0) {
            var emptyDiv = createEl('div', 'empty-checkout-state');
            var emptyP = createEl('p', 'empty-checkout-text', 'Keranjang klaim pangan Anda sedang kosong.');
            var emptyLink = createEl('a', 'btn-empty-return', 'Kembali ke Katalog Pangan');
            emptyLink.setAttribute('href', 'food_catalogue.xhtml');
            emptyDiv.appendChild(emptyP);
            emptyDiv.appendChild(emptyLink);
            itemsListContainer.appendChild(emptyDiv);

            if (confirmClaimBtn) {
                confirmClaimBtn.style.opacity = '0.5';
                confirmClaimBtn.style.pointerEvents = 'none';
            }
        } else {
            if (confirmClaimBtn) {
                confirmClaimBtn.style.opacity = '1';
                confirmClaimBtn.style.pointerEvents = 'auto';
            }
        }

        if (totalPortionsEl) {
            totalPortionsEl.textContent = 'Total Klaim: ' + totalPortionsCount + ' Porsi Pangan';
        }
        if (summaryItemsEl) {
            summaryItemsEl.textContent = totalItemsCount + ' Menu Pangan (' + totalPortionsCount + ' porsi)';
        }
        if (summaryQuotaEl) {
            summaryQuotaEl.textContent = '1 dari 2 porsi';
        }

        bindStepperEvents();
    }

    function bindStepperEvents() {
        var minusButtons = document.querySelectorAll('.btn-step-minus');
        minusButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var key = this.getAttribute('data-key');
                if (key && cart.items[key]) {
                    cart.items[key] -= 1;
                    if (cart.items[key] <= 0) {
                        delete cart.items[key];
                    }
                    saveCart(cart);
                    renderCheckoutList();
                }
            });
        });

        var plusButtons = document.querySelectorAll('.btn-step-plus');
        plusButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var key = this.getAttribute('data-key');
                if (key) {
                    cart.items[key] = (cart.items[key] || 0) + 1;
                    saveCart(cart);
                    renderCheckoutList();
                }
            });
        });
    }

    renderCheckoutList();

    if (confirmClaimBtn) {
        confirmClaimBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var ticketCode = 'TRX-' + Math.floor(1000 + Math.random() * 9000);
            var successMsg = 'Klaim pangan berhasil dikonfirmasi!\n\n' +
                'Merchant Produsen: ' + merchantInfo.name + '\n' +
                'Lokasi Pengambilan: ' + merchantInfo.storeName + '\n' +
                'Alamat: ' + merchantInfo.poskoAddr + '\n' +
                'Batas Waktu Pengambilan: ' + merchantInfo.deadline + '\n\n' +
                'Kode Transaksi: ' + ticketCode + '\n' +
                'Tunjukkan kode ini kepada kasir atau staf di tempat pengambilan.';

            alert(successMsg);
            cart.items = {};
            saveCart(cart);
            window.location.href = isViews ? 'food_catalogue.xhtml' : 'food_catalogue.xhtml';
        });
    }
});