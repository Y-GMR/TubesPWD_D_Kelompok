(function () {
    'use strict';

    function initAddFoodForm() {
        var form = document.getElementById('formAddFood');
        var actionInput = form ? form.querySelector('input[name="action"]') : null;
        var isEditMode = !!(actionInput && actionInput.value === 'update');

        var fileInput = document.getElementById('foodPhotoInput');
        var previewImg = document.getElementById('foodImagePreview');
        var dropzone = document.getElementById('dropzoneUpload');
        var btnBrowse = document.getElementById('btnBrowsePhoto');
        var btnChange = document.getElementById('btnChangePhoto');

        var inputFoodName = document.getElementById('inputFoodName');
        var selectCategory = document.getElementById('selectCategory');
        var inputPortion = document.getElementById('inputPortion');
        var summaryPortion = document.getElementById('summaryPortion');
        var inputPrice = document.getElementById('inputPrice');
        var summaryPrice = document.getElementById('summaryPrice');
        var inputExpiry = document.getElementById('inputExpiryTime');
        var summaryTime = document.getElementById('summaryTime');
        var textareaNotes = document.getElementById('textareaNotes');
        var inputAddress = document.getElementById('inputAddress');
        var chkConfirmHygienic = document.getElementById('chkConfirmHygienic');

        var placeholders = [
            { el: inputFoodName, text: 'Contoh: Paket Nasi Ayam Crispy' },
            { el: inputPortion, text: '10' },
            { el: inputPrice, text: '0' },
            { el: inputExpiry, text: 'YYYY-MM-DD HH:MM (contoh: 2026-11-26 21:00)' },
            { el: textareaNotes, text: 'Tuliskan komposisi makanan, alergen, atau catatan kemasan...' },
            { el: inputAddress, text: 'Contoh: Jl. Jend. Sudirman No. 54, Yogyakarta' }
        ];
        for (var i = 0; i < placeholders.length; i++) {
            if (placeholders[i].el && !placeholders[i].el.placeholder) {
                placeholders[i].el.placeholder = placeholders[i].text;
            }
        }

        if (fileInput && previewImg && window.ImagePreview && typeof window.ImagePreview.bind === 'function') {
            window.ImagePreview.bind(fileInput, previewImg);
        }

        function triggerFileInput(e) {
            if (e) e.preventDefault();
            if (fileInput) fileInput.click();
        }

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', triggerFileInput);
            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.style.borderColor = '#135b60';
                dropzone.style.backgroundColor = '#e8efe9';
            });
            dropzone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                dropzone.style.borderColor = '#0d4347';
                dropzone.style.backgroundColor = '#f9fafb';
            });
            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.style.borderColor = '#0d4347';
                dropzone.style.backgroundColor = '#f9fafb';
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    try {
                        fileInput.files = e.dataTransfer.files;
                        var ev = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(ev);
                    } catch (err) {}
                }
            });
        }

        if (btnBrowse) {
            btnBrowse.addEventListener('click', function (e) {
                e.stopPropagation();
                triggerFileInput(e);
            });
        }

        if (btnChange) {
            btnChange.addEventListener('click', triggerFileInput);
        }

        if (inputPortion && summaryPortion) {
            inputPortion.addEventListener('input', function () {
                var cleaned = inputPortion.value.replace(/[^0-9]/g, '');
                if (inputPortion.value !== cleaned) {
                    inputPortion.value = cleaned;
                }
                summaryPortion.textContent = (cleaned !== '' ? cleaned : '0') + ' Porsi';
            });
        }

        if (inputPrice && summaryPrice) {
            inputPrice.addEventListener('input', function () {
                var cleaned = inputPrice.value.replace(/[^0-9]/g, '');
                if (inputPrice.value !== cleaned) {
                    inputPrice.value = cleaned;
                }
                var num = parseInt(cleaned, 10);
                if (isNaN(num) || num <= 0) {
                    summaryPrice.textContent = 'Gratis';
                } else {
                    summaryPrice.textContent = 'Rp ' + num.toLocaleString('id-ID');
                }
            });
        }

        if (inputExpiry && summaryTime) {
            inputExpiry.addEventListener('input', function () {
                var val = inputExpiry.value.trim();
                summaryTime.textContent = val !== '' ? val.replace('T', ' ') : 'Tidak dibatasi';
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                var errors = [];
                var nama = inputFoodName ? inputFoodName.value.trim() : '';
                var katId = selectCategory ? parseInt(selectCategory.value, 10) : 0;
                var stokRaw = inputPortion ? inputPortion.value.trim() : '';
                var stokNum = parseInt(stokRaw, 10);
                var minStok = isEditMode ? 0 : 1;
                var hargaRaw = inputPrice ? inputPrice.value.trim() : '0';
                var hargaNum = hargaRaw === '' ? 0 : parseInt(hargaRaw, 10);
                var expiryRaw = inputExpiry ? inputExpiry.value.trim() : '';
                var alamat = inputAddress ? inputAddress.value.trim() : '';

                if (nama.length < 2 || nama.length > 150) {
                    errors.push('Nama makanan wajib diisi antara 2 hingga 150 karakter.');
                }
                if (isNaN(katId) || katId <= 0) {
                    errors.push('Kategori makanan wajib dipilih.');
                }
                if (!/^\d+$/.test(stokRaw) || isNaN(stokNum) || stokNum < minStok || stokNum > 9999) {
                    errors.push('Jumlah porsi stok harus berupa angka antara ' + minStok + ' hingga 9999.');
                }
                if (hargaRaw !== '' && (!/^\d+$/.test(hargaRaw) || isNaN(hargaNum) || hargaNum < 0 || hargaNum > 100000000)) {
                    errors.push('Harga per porsi harus berupa angka bulat 0 atau lebih.');
                }
                if (expiryRaw !== '' && !/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/.test(expiryRaw)) {
                    errors.push('Format batas kedaluwarsa harus YYYY-MM-DD HH:MM.');
                }
                if (alamat.length < 3 || alamat.length > 500) {
                    errors.push('Alamat gerai wajib diisi antara 3 hingga 500 karakter.');
                }
                if (!isEditMode && chkConfirmHygienic && !chkConfirmHygienic.checked) {
                    errors.push('Anda wajib menyetujui pernyataan makanan higienis dan layak konsumsi.');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    window.alert(errors.join('\n'));
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAddFoodForm();
    });
})();
