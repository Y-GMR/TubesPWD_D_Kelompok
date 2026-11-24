(function () {
    'use strict';

    function initAddFoodForm() {
        var fileInput = document.getElementById('foodPhotoInput');
        var dropzone = document.getElementById('dropzoneUpload');
        var btnBrowse = document.getElementById('btnBrowsePhoto');
        var btnChange = document.getElementById('btnChangePhoto');
        var inputPortion = document.getElementById('inputPortion');
        var summaryPortion = document.getElementById('summaryPortion');
        var inputPrice = document.getElementById('inputPrice');
        var summaryPrice = document.getElementById('summaryPrice');
        var inputExpiry = document.getElementById('inputExpiryTime');
        var summaryTime = document.getElementById('summaryTime');

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
                var val = inputPortion.value.trim();
                summaryPortion.textContent = (val !== '' ? val : '0') + ' Porsi';
            });
        }

        if (inputPrice && summaryPrice) {
            inputPrice.addEventListener('input', function () {
                var num = parseInt(inputPrice.value, 10);
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
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAddFoodForm();
    });
})();
