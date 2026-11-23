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
    var inputPortion = document.getElementById('inputPortion');
    var summaryPortion = document.getElementById('summaryPortion');
    var inputReadyTime = document.getElementById('inputReadyTime');
    var inputExpiryTime = document.getElementById('inputExpiryTime');
    var summaryTime = document.getElementById('summaryTime');
    var form = document.getElementById('formAddFood');

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

    if (inputPortion && summaryPortion) {
        inputPortion.addEventListener('input', function () {
            var val = inputPortion.value.trim();
            summaryPortion.textContent = (val ? val : '0') + ' Porsi';
        });
    }

    function updateSummaryTime() {
        if (!summaryTime) return;
        var readyVal = inputReadyTime ? inputReadyTime.value.trim() : '';
        var expVal = inputExpiryTime ? inputExpiryTime.value.trim() : '';
        if (readyVal && expVal) {
            var readyShort = readyVal.replace('Hari ini • ', '').replace(' WIB', '');
            var expShort = expVal.replace('Hari ini • ', '');
            summaryTime.textContent = readyShort + ' - ' + expShort;
        }
    }

    if (inputReadyTime) inputReadyTime.addEventListener('input', updateSummaryTime);
    if (inputExpiryTime) inputExpiryTime.addEventListener('input', updateSummaryTime);

    if (form) {
        form.addEventListener('submit', function (e) {
            try {
                var newFood = {
                    name: document.getElementById('inputFoodName').value,
                    portion: document.getElementById('inputPortion').value,
                    category: document.getElementById('selectCategory').value,
                    address: document.getElementById('inputAddress').value,
                    readyTime: inputReadyTime ? inputReadyTime.value : '',
                    expiryTime: inputExpiryTime ? inputExpiryTime.value : ''
                };
                var inventory = JSON.parse(localStorage.getItem('tubespwd_producer_inventory') || '[]');
                inventory.unshift(newFood);
                localStorage.setItem('tubespwd_producer_inventory', JSON.stringify(inventory));
            } catch (err) {}
        });
    }
})();
