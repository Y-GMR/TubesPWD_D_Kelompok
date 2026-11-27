(function () {
    'use strict';

    function initProfilePage() {
        var profilePlaceholders = [
            { id: 'inputNamaLengkap', text: 'Nama Lengkap' },
            { id: 'inputUsername', text: 'Nama Pengguna (Username)' },
            { id: 'inputEmail', text: 'Alamat Email' },
            { id: 'inputPasswordBaru', text: 'Kosongkan jika tidak ingin mengubah sandi' },
            { id: 'inputKonfirmasiSandi', text: 'Ulangi kata sandi baru' }
        ];
        for (var i = 0; i < profilePlaceholders.length; i++) {
            var el = document.getElementById(profilePlaceholders[i].id);
            if (el && !el.placeholder) {
                el.placeholder = profilePlaceholders[i].text;
            }
        }

        var docInput = document.getElementById('inputDokumenKelayakan');
        var docNameEl = document.getElementById('profileDocFileName');
        var docPreviewWrap = document.getElementById('profileDocPreviewWrap');
        var docPreviewImg = document.getElementById('profileDocPreviewImg');
        var sktmBox = document.querySelector('.profile-sktm-verified-box');
        var sktmStatusVal = document.getElementById('profileSktmStatusVal');

        function resetDocPreview() {
            if (docNameEl) docNameEl.textContent = 'Belum ada berkas baru dipilih (PDF, JPG, atau PNG maks. 2MB)';
            if (docPreviewWrap) docPreviewWrap.style.display = 'none';
            if (docPreviewImg) docPreviewImg.style.display = 'none';
            if (sktmStatusVal) sktmStatusVal.textContent = '-';
            if (sktmBox) sktmBox.textContent = 'Status verifikasi dokumen SKTM akan tampil di sini.';
        }

        if (docInput) {
            docInput.addEventListener('change', function () {
                var file = docInput.files && docInput.files[0];
                if (!file) {
                    resetDocPreview();
                    return;
                }

                var allowedExt = ['.pdf', '.jpg', '.jpeg', '.png'];
                var lowerName = (file.name || '').toLowerCase();
                var validExt = false;
                for (var k = 0; k < allowedExt.length; k++) {
                    if (lowerName.indexOf(allowedExt[k], lowerName.length - allowedExt[k].length) !== -1) {
                        validExt = true;
                        break;
                    }
                }

                if (!validExt) {
                    alert('Format dokumen kelayakan harus berupa PDF, JPG, atau PNG.');
                    docInput.value = '';
                    resetDocPreview();
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran dokumen kelayakan maksimal adalah 2MB.');
                    docInput.value = '';
                    resetDocPreview();
                    return;
                }

                var sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                if (docNameEl) {
                    docNameEl.textContent = file.name + ' (' + sizeMb + ' MB)';
                }

                var isPdf = lowerName.indexOf('.pdf', lowerName.length - 4) !== -1;
                if (isPdf) {
                    if (docPreviewWrap) docPreviewWrap.style.display = 'none';
                    if (docPreviewImg) docPreviewImg.style.display = 'none';
                } else {
                    if (docPreviewWrap) docPreviewWrap.style.display = 'flex';
                    if (docPreviewImg && window.FileReader) {
                        var reader = new FileReader();
                        reader.onload = function (ev) {
                            docPreviewImg.src = ev.target.result;
                            docPreviewImg.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                }

                if (sktmStatusVal) {
                    sktmStatusVal.textContent = 'Menunggu Simpan';
                }
                if (sktmBox) {
                    sktmBox.textContent = 'Berkas "' + file.name + '" dipilih dan siap disimpan untuk verifikasi.';
                }
            });
        }

        var profileForm = document.getElementById('profileEditForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function (e) {
                var namaInput = document.getElementById('inputNamaLengkap');
                var userInput = document.getElementById('inputUsername');
                var emailInput = document.getElementById('inputEmail');
                var passInput = document.getElementById('inputPasswordBaru');
                var confirmInput = document.getElementById('inputKonfirmasiSandi');

                var namaVal = namaInput ? namaInput.value.trim() : '';
                var userVal = userInput ? userInput.value.trim() : '';
                var emailVal = emailInput ? emailInput.value.trim() : '';
                var passVal = passInput ? passInput.value : '';
                var confirmVal = confirmInput ? confirmInput.value : '';

                var errors = [];
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!namaVal) {
                    errors.push('Nama lengkap wajib diisi.');
                }
                if (userVal.length < 4 || userVal.length > 30) {
                    errors.push('Username harus terdiri dari 4 sampai 30 karakter.');
                }
                if (!emailVal || !emailPattern.test(emailVal)) {
                    errors.push('Alamat email wajib diisi dengan format yang valid.');
                }
                if (passVal !== '') {
                    if (passVal.length < 8) {
                        errors.push('Kata sandi baru minimal harus 8 karakter.');
                    } else if (!/[a-zA-Z]/.test(passVal) || !/[0-9]/.test(passVal)) {
                        errors.push('Kata sandi baru harus mengandung kombinasi huruf dan angka.');
                    }
                    if (passVal !== confirmVal) {
                        errors.push('Konfirmasi kata sandi baru tidak cocok.');
                    }
                }

                if (errors.length > 0) {
                    alert(errors.join('\n'));
                    e.preventDefault();
                    return;
                }

                var actionAttr = profileForm.getAttribute('action') || '';
                if (actionAttr.indexOf('.php') !== -1 && window.location.pathname.indexOf('.xhtml') !== -1) {
                    e.preventDefault();
                    alert('Perubahan profil berhasil divalidasi (mode pratinjau XHTML).');
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfilePage);
    } else {
        initProfilePage();
    }
})();
