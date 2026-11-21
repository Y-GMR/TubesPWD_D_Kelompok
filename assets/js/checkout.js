document.addEventListener('DOMContentLoaded', function () {
    var itemsDatabase = {
        'donat-oreo': {
            merchant: 'Dunkin Donuts Sudirman',
            title: 'Donat Oreo (Paket 3 Pcs)',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_donat_oreo.png',
            poskoName: 'Posko Bulaksumur',
            poskoAddr: 'Bulaksumur, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281',
            distance: '1,2 km',
            gps: '-7.7829, 110.3671'
        },
        'donat-cokelat': {
            merchant: 'Dunkin Donuts Sudirman',
            title: 'Donat Cokelat (Paket 3 Pcs)',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_donat_cokelat.png',
            poskoName: 'Posko Bulaksumur',
            poskoAddr: 'Bulaksumur, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281',
            distance: '1,2 km',
            gps: '-7.7829, 110.3671'
        },
        'donat-glaze': {
            merchant: 'Dunkin Donuts Sudirman',
            title: 'Donat Glaze (Paket 3 Pcs)',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_donat_glaze.png',
            poskoName: 'Posko Bulaksumur',
            poskoAddr: 'Bulaksumur, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281',
            distance: '1,2 km',
            gps: '-7.7829, 110.3671'
        },
        'fire-flosss': {
            merchant: 'BreadTalk Seturan',
            title: 'Fire Flosss (Paket 2 Pcs)',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_fire_flosss.png',
            poskoName: 'Posko Seturan',
            poskoAddr: 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '0,9 km',
            gps: '-7.7735, 110.4092'
        },
        'choco-bun': {
            merchant: 'BreadTalk Seturan',
            title: 'Choco Bun',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_choco_bun.png',
            poskoName: 'Posko Seturan',
            poskoAddr: 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '0,9 km',
            gps: '-7.7735, 110.4092'
        },
        'cheese-boat': {
            merchant: 'BreadTalk Seturan',
            title: 'Cheese Boat',
            category: 'Roti & Kue',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_cheese_boat.png',
            poskoName: 'Posko Seturan',
            poskoAddr: 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '0,9 km',
            gps: '-7.7735, 110.4092'
        },
        'pepperoni-slice': {
            merchant: 'Pizza Hut Gejayan',
            title: 'Pepperoni Slice',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_pepperoni.png',
            poskoName: 'Posko Gejayan',
            poskoAddr: 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,8 km',
            gps: '-7.7654, 110.3891'
        },
        'cheese-slice': {
            merchant: 'Pizza Hut Gejayan',
            title: 'Cheese Slice',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_cheese_slice.png',
            poskoName: 'Posko Gejayan',
            poskoAddr: 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,8 km',
            gps: '-7.7654, 110.3891'
        },
        'garlic-bread': {
            merchant: 'Pizza Hut Gejayan',
            title: 'Garlic Bread',
            category: 'Cepat Saji',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_garlic_bread.png',
            poskoName: 'Posko Gejayan',
            poskoAddr: 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,8 km',
            gps: '-7.7654, 110.3891'
        },
        'ayam-crispy': {
            merchant: 'KFC Babarsari',
            title: 'Ayam Crispy (Paket 1 Porsi)',
            category: 'Gorengan',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_ayam_crispy.png',
            poskoName: 'Posko Babarsari',
            poskoAddr: 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,4 km',
            gps: '-7.7788, 110.4123'
        },
        'ayam-original': {
            merchant: 'KFC Babarsari',
            title: 'Ayam Original (Paket 1 Porsi)',
            category: 'Gorengan',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_ayam_original.png',
            poskoName: 'Posko Babarsari',
            poskoAddr: 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,4 km',
            gps: '-7.7788, 110.4123'
        },
        'perkedel': {
            merchant: 'KFC Babarsari',
            title: 'Perkedel',
            category: 'Gorengan',
            expiry: '22 Nov 2026',
            img: 'assets/images/menu_perkedel.png',
            poskoName: 'Posko Babarsari',
            poskoAddr: 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281',
            distance: '1,4 km',
            gps: '-7.7788, 110.4123'
        }
    };

    var urlParams = new URLSearchParams(window.location.search);
    var itemKey = (urlParams.get('item') || '').toLowerCase();
    var isViews = window.location.pathname.indexOf('/views/') !== -1;

    if (itemKey && itemsDatabase[itemKey]) {
        var data = itemsDatabase[itemKey];

        var merchantEl = document.querySelector('.food-merchant-name');
        var titleEl = document.querySelector('.food-item-name');
        var metaEl = document.querySelector('.food-meta-info');
        var imgEl = document.querySelector('.food-thumb-img');
        var poskoNameEl = document.querySelector('.posko-name');
        var poskoAddrEl = document.querySelector('.posko-address');
        var poskoMetaEl = document.querySelector('.posko-meta');

        if (merchantEl) merchantEl.textContent = data.merchant;
        if (titleEl) titleEl.textContent = data.title;
        if (metaEl) metaEl.textContent = 'Kategori: ' + data.category + ' \u2022 Kedaluwarsa: ' + data.expiry;
        if (imgEl) {
            var fullImgPath = (isViews ? '../' : '') + data.img;
            imgEl.setAttribute('src', fullImgPath);
            imgEl.src = fullImgPath;
        }
        if (poskoNameEl) poskoNameEl.textContent = data.poskoName;
        if (poskoAddrEl) poskoAddrEl.textContent = data.poskoAddr;
        if (poskoMetaEl) {
            poskoMetaEl.innerHTML = 'Jarak: <span class="posko-meta-orange">' + data.distance + '</span> dari lokasi Anda &#8226; Koordinat GPS: <span class="posko-meta-orange">' + data.gps + '</span>';
        }
    }

    var btnConfirm = document.querySelector('.btn-confirm-claim');
    if (btnConfirm) {
        btnConfirm.addEventListener('click', function (e) {
            e.preventDefault();
            alert('Klaim pangan berhasil dikonfirmasi! Kode tiket klaim Anda telah dibuat. Silakan ambil makanan di posko sebelum batas waktu.');
        });
    }
});
