(function () {
    'use strict';

    try {
        localStorage.setItem('tubespwd_role', 'produsen');
        sessionStorage.setItem('tubespwd_role', 'produsen');
    } catch (e) {}

    var fileInput = document.getElementById('foodPhotoInput');
    var dropzone = document.getElementById('dropzoneUpload');
    var btnBrowse = document.getElementById('btnBrowsePhoto');
    var btnChange = document.getElementById('btnChangePhoto');
    var inputFoodName = document.getElementById('inputFoodName');
    var selectCategory = document.getElementById('selectCategory');
    var inputPortion = document.getElementById('inputPortion');
    var summaryPortion = document.getElementById('summaryPortion');
    var summaryStore = document.getElementById('summaryStore');
    var inputReadyTime = document.getElementById('inputReadyTime');
    var inputExpiryTime = document.getElementById('inputExpiryTime');
    var textareaNotes = document.getElementById('textareaNotes');
    var inputAddress = document.getElementById('inputAddress');
    var inputLatitude = document.getElementById('inputLatitude');
    var inputLongitude = document.getElementById('inputLongitude');
    var previewWrap = document.getElementById('foodPhotoPreviewWrap');
    var chkConfirmHygienic = document.getElementById('chkConfirmHygienic') || document.querySelector('input[name="konfirmasi_higienis"]');
    var summaryTime = document.getElementById('summaryTime');
    var form = document.getElementById('formAddFood');

    var addFoodPlaceholders = [
        { el: inputFoodName, text: 'Contoh: Paket Roti & Kue Layak Konsumsi' },
        { el: inputPortion, text: '10' },
        { el: inputReadyTime, text: 'Hari ini \u2022 11:30 WIB' },
        { el: inputExpiryTime, text: 'Hari ini \u2022 15:30 WIB' },
        { el: textareaNotes, text: 'Tambahkan catatan bahan makanan, alergen, atau saran penyajian...' },
        { el: inputAddress, text: 'Contoh: Jl. Jend. Sudirman No. 54, Yogyakarta' },
        { el: inputLatitude, text: '-' },
        { el: inputLongitude, text: '-' }
    ];
    for (var p = 0; p < addFoodPlaceholders.length; p++) {
        if (addFoodPlaceholders[p].el && !addFoodPlaceholders[p].el.placeholder) {
            addFoodPlaceholders[p].el.placeholder = addFoodPlaceholders[p].text;
        }
    }

    function triggerFileInput(e) {
        if (e) e.preventDefault();
        if (fileInput) fileInput.click();
    }

    if (dropzone) {
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
                fileInput.files = e.dataTransfer.files;
                var changeEv = document.createEvent('HTMLEvents');
                changeEv.initEvent('change', true, false);
                fileInput.dispatchEvent(changeEv);
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

    if (fileInput && previewWrap) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) {
                previewWrap.style.display = 'none';
                return;
            }
            var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            var lowerName = (file.name || '').toLowerCase();
            var extOk = /\.(jpg|jpeg|png|webp)$/.test(lowerName);
            if ((allowedTypes.indexOf(file.type) !== -1 || extOk) && file.size <= 2 * 1024 * 1024) {
                previewWrap.style.display = 'block';
            } else {
                previewWrap.style.display = 'none';
            }
        });
    }

    if (inputAddress && summaryStore) {
        inputAddress.addEventListener('input', function () {
            var addrVal = inputAddress.value.trim();
            summaryStore.textContent = addrVal ? addrVal.split(',')[0].trim() : '-';
        });
    }

    if (inputPortion && summaryPortion) {
        inputPortion.addEventListener('input', function () {
            var cleaned = inputPortion.value.replace(/[^0-9]/g, '');
            if (inputPortion.value !== cleaned) {
                inputPortion.value = cleaned;
            }
            var val = cleaned.trim();
            summaryPortion.textContent = (val ? val : '0') + ' Porsi';
        });
    }

    function updateSummaryTime() {
        if (!summaryTime) return;
        var readyVal = inputReadyTime ? inputReadyTime.value.trim() : '';
        var expVal = inputExpiryTime ? inputExpiryTime.value.trim() : '';
        if (readyVal && expVal) {
            var readyShort = readyVal.replace('Hari ini \u2022 ', '').replace('Hari ini • ', '').replace(' WIB', '');
            var expShort = expVal.replace('Hari ini \u2022 ', '').replace('Hari ini • ', '');
            summaryTime.textContent = readyShort + ' - ' + expShort;
        } else {
            summaryTime.textContent = '-';
        }
    }

    if (inputReadyTime) inputReadyTime.addEventListener('input', updateSummaryTime);
    if (inputExpiryTime) inputExpiryTime.addEventListener('input', updateSummaryTime);

    if (form) {
        form.addEventListener('submit', function (e) {
            var foodNameVal = inputFoodName ? inputFoodName.value.trim() : '';
            var categoryVal = selectCategory ? selectCategory.value.trim() : '';
            var portionRaw = inputPortion ? inputPortion.value.trim() : '';
            var portionNum = parseInt(portionRaw, 10);
            var readyVal = inputReadyTime ? inputReadyTime.value.trim() : '';
            var expiryVal = inputExpiryTime ? inputExpiryTime.value.trim() : '';
            var addressVal = inputAddress ? inputAddress.value.trim() : '';
            var isConfirmed = chkConfirmHygienic ? chkConfirmHygienic.checked : true;

            var errors = [];
            if (!foodNameVal) {
                errors.push('Nama makanan wajib diisi.');
            }
            if (!categoryVal) {
                errors.push('Kategori makanan wajib dipilih.');
            }
            if (!/^\d+$/.test(portionRaw) || isNaN(portionNum) || portionNum < 1 || portionNum > 1000) {
                errors.push('Jumlah porsi harus berupa angka antara 1 sampai 1000.');
            }
            if (!readyVal) {
                errors.push('Waktu siap diambil wajib diisi.');
            }
            if (!expiryVal) {
                errors.push('Batas pengambilan wajib diisi.');
            }
            if (!addressVal) {
                errors.push('Alamat gerai wajib diisi.');
            }
            if (!isConfirmed) {
                errors.push('Harap centang pernyataan jaminan makanan higienis dan layak konsumsi.');
            }

            if (errors.length > 0) {
                alert(errors.join('\n'));
                e.preventDefault();
                return;
            }

            try {
                var newFood = {
                    name: foodNameVal,
                    portion: portionRaw,
                    category: categoryVal,
                    address: addressVal,
                    readyTime: readyVal,
                    expiryTime: expiryVal
                };
                var inventory = JSON.parse(localStorage.getItem('tubespwd_producer_inventory') || '[]');
                inventory.unshift(newFood);
                localStorage.setItem('tubespwd_producer_inventory', JSON.stringify(inventory));
            } catch (err) {}
        });
    }
})();
