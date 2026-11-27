(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('inputSearch');
        if (searchInput && !searchInput.placeholder) {
            searchInput.placeholder = 'Cari makanan, resto, atau lokasi pengambilan';
        }
    });
})();
