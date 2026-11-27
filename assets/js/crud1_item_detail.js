(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('formClaimFood');
        var qtyInput = document.getElementById('inputClaimQty');
        var maxInput = document.getElementById('maxClaimQty');
        var maxQty = maxInput ? (parseInt(maxInput.value, 10) || 1) : 1;

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                var cleaned = this.value.replace(/[^0-9]/g, '');
                if (cleaned !== '') {
                    var parsed = parseInt(cleaned, 10);
                    if (parsed > maxQty) cleaned = String(maxQty);
                }
                if (this.value !== cleaned) {
                    this.value = cleaned;
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                var raw = qtyInput ? qtyInput.value.trim() : '';
                var num = parseInt(raw, 10);
                if (!/^\d+$/.test(raw) || isNaN(num) || num < 1 || num > maxQty) {
                    e.preventDefault();
                    window.alert('Jumlah porsi diklaim harus antara 1 hingga ' + maxQty + '.');
                    if (qtyInput) qtyInput.focus();
                }
            });
        }
    });
})();
