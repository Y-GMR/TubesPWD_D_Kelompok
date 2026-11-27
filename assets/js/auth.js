document.addEventListener('DOMContentLoaded', function () {
    var authPlaceholders = [
        { id: 'loginUsername', text: 'Username' },
        { id: 'loginEmail', text: 'Email' },
        { id: 'loginPassword', text: '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022' },
        { id: 'regFullname', text: 'Nama Lengkap' },
        { id: 'regUsername', text: 'Username' },
        { id: 'regEmail', text: 'nama@email.com' },
        { id: 'regPassword', text: '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022' },
        { id: 'regConfirmPassword', text: '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022' }
    ];
    for (var phIdx = 0; phIdx < authPlaceholders.length; phIdx++) {
        var phEl = document.getElementById(authPlaceholders[phIdx].id);
        if (phEl && !phEl.placeholder) {
            phEl.placeholder = authPlaceholders[phIdx].text;
        }
    }

    var btnRoleConsumer = document.getElementById('btnRoleConsumer');
    var btnRoleDonor = document.getElementById('btnRoleDonor');

    var urlParams = new URLSearchParams(window.location.search);
    var roleParam = (urlParams.get('role') || '').toLowerCase();

    function setRole(role) {
        var regRole = document.getElementById('regRole');
        var sktmUploadGroup = document.getElementById('sktmUploadGroup');
        var regDocument = document.getElementById('regDocument');
        var regDocumentName = document.getElementById('regDocumentName');
        if (role === 'donor' || role === 'produsen') {
            if (btnRoleDonor && btnRoleConsumer) {
                btnRoleDonor.classList.add('active');
                btnRoleConsumer.classList.remove('active');
            }
            if (regRole) {
                regRole.value = 'produsen';
            }
            if (sktmUploadGroup) {
                sktmUploadGroup.style.display = 'none';
            }
            if (regDocument) {
                regDocument.value = '';
            }
            if (regDocumentName) {
                regDocumentName.textContent = 'Format: PDF, JPG, atau PNG (Maks. 2MB)';
            }
        } else {
            if (btnRoleConsumer && btnRoleDonor) {
                btnRoleConsumer.classList.add('active');
                btnRoleDonor.classList.remove('active');
            }
            if (regRole) {
                regRole.value = 'konsumen';
            }
            if (sktmUploadGroup) {
                sktmUploadGroup.style.display = 'flex';
            }
        }
    }

    if (roleParam === 'donor' || roleParam === 'produsen') {
        setRole('donor');
    }

    if (btnRoleConsumer && btnRoleDonor) {
        btnRoleConsumer.addEventListener('click', function () {
            setRole('consumer');
        });

        btnRoleDonor.addEventListener('click', function () {
            setRole('donor');
        });
    }

    var btnTogglePassword = document.getElementById('btnTogglePassword');
    var loginPassword = document.getElementById('loginPassword');

    if (btnTogglePassword && loginPassword) {
        btnTogglePassword.addEventListener('click', function () {
            if (loginPassword.type === 'password') {
                loginPassword.type = 'text';
            } else {
                loginPassword.type = 'password';
            }
        });
    }

    var regUsername = document.getElementById('regUsername');
    var usernameFeedback = document.getElementById('usernameFeedback');

    if (regUsername && usernameFeedback && !window.AjaxValidator) {
        regUsername.addEventListener('input', function () {
            if (window.AjaxValidator) {
                return;
            }
            var val = this.value.trim();
            if (/^[a-zA-Z0-9_]{4,30}$/.test(val)) {
                regUsername.classList.add('is-valid');
                usernameFeedback.style.display = 'block';
            } else {
                regUsername.classList.remove('is-valid');
                usernameFeedback.style.display = 'none';
            }
        });
    }

    var emailcheck = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            var emailInput = document.getElementById('loginEmail');
            var usernameInput = document.getElementById('loginUsername');
            var passwordInput = document.getElementById('loginPassword');

            var valUser = (usernameInput && usernameInput.value) ? usernameInput.value.trim() : '';
            var valEmail = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
            var valPass = (passwordInput && passwordInput.value) ? passwordInput.value : '';

            var sMsg = '';

            if (valUser === '' && valEmail === '') {
                sMsg += "\nHarap masukkan username atau alamat email.";
            }

            if (valEmail !== '' && !emailcheck.test(valEmail)) {
                sMsg += "\nFormat alamat email tidak valid.";
            }

            var formAction = loginForm.getAttribute('action') || '';
            var isPhpBackend = (formAction.indexOf('.php') !== -1);

            if (valPass === '') {
                sMsg += "\nKata sandi tidak boleh kosong.";
            } else if (!isPhpBackend && valPass.length < 8) {
                sMsg += "\nKata sandi minimal harus 8 karakter.";
            }

            if (sMsg !== '') {
                alert("Harap perbaiki kesalahan berikut:" + sMsg);
                e.preventDefault();
                return false;
            }

            if (isPhpBackend) {
                return true;
            }

            alert("Gunakan halaman login.php untuk masuk ke aplikasi.");
            e.preventDefault();
            return false;
        });
    }

    var regDocumentInput = document.getElementById('regDocument');
    var regDocumentNameEl = document.getElementById('regDocumentName');

    function isValidDocumentFile(file) {
        if (!file) return true;
        var allowedExt = ['.pdf', '.jpg', '.jpeg', '.png'];
        var lowerName = (file.name || '').toLowerCase();
        var validExt = false;
        for (var idx = 0; idx < allowedExt.length; idx++) {
            if (lowerName.indexOf(allowedExt[idx], lowerName.length - allowedExt[idx].length) !== -1) {
                validExt = true;
                break;
            }
        }
        if (!validExt) {
            return 'Format dokumen kelayakan harus berupa PDF, JPG, atau PNG.';
        }
        if (file.size > 2 * 1024 * 1024) {
            return 'Ukuran dokumen kelayakan maksimal adalah 2MB.';
        }
        return true;
    }

    if (regDocumentInput) {
        regDocumentInput.addEventListener('change', function () {
            var docFile = regDocumentInput.files && regDocumentInput.files[0];
            if (!docFile) {
                if (regDocumentNameEl) {
                    regDocumentNameEl.textContent = 'Format: PDF, JPG, atau PNG (Maks. 2MB)';
                }
                return;
            }
            var checkResult = isValidDocumentFile(docFile);
            if (checkResult !== true) {
                alert(checkResult);
                regDocumentInput.value = '';
                if (regDocumentNameEl) {
                    regDocumentNameEl.textContent = 'Format: PDF, JPG, atau PNG (Maks. 2MB)';
                }
                return;
            }
            if (regDocumentNameEl) {
                var sizeMb = (docFile.size / (1024 * 1024)).toFixed(2);
                regDocumentNameEl.textContent = 'Berkas siap diunggah: ' + docFile.name + ' (' + sizeMb + ' MB)';
            }
        });
    }

    var registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            var fullnameInput = document.getElementById('regFullname');
            var usernameInput = document.getElementById('regUsername');
            var emailInput = document.getElementById('regEmail');
            var passwordInput = document.getElementById('regPassword');
            var confirmPasswordInput = document.getElementById('regConfirmPassword');
            var docFileInput = document.getElementById('regDocument');

            var valName = (fullnameInput && fullnameInput.value) ? fullnameInput.value.trim() : '';
            var valUser = (usernameInput && usernameInput.value) ? usernameInput.value.trim() : '';
            var valEmail = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
            var valPass = (passwordInput && passwordInput.value) ? passwordInput.value : '';
            var valConfirm = (confirmPasswordInput && confirmPasswordInput.value) ? confirmPasswordInput.value : '';

            var sMsg = '';
            var adaHuruf = /[a-zA-Z]/.test(valPass);
            var adaAngka = /[0-9]/.test(valPass);

            if (valName.length < 2 || valName.length > 100) {
                sMsg += "\nNama lengkap harus terdiri dari 2 hingga 100 karakter.";
            }

            if (valUser.length < 4 || valUser.length > 30) {
                sMsg += "\nUsername harus terdiri dari 4 sampai 30 karakter.";
            } else if (!/^[a-zA-Z0-9_]{4,30}$/.test(valUser)) {
                sMsg += "\nUsername hanya boleh berisi huruf, angka, dan garis bawah (_).";
            }

            if (emailInput) {
                if (valEmail === '' || !emailcheck.test(valEmail)) {
                    sMsg += "\nAlamat email wajib diisi dengan format yang valid.";
                }
            }

            if (valPass.length < 8) {
                sMsg += "\nKata sandi minimal harus 8 karakter.";
            } else if (!adaHuruf || !adaAngka) {
                sMsg += "\nKata sandi harus mengandung kombinasi huruf dan angka.";
            }

            if (valPass !== valConfirm) {
                sMsg += "\nKonfirmasi kata sandi tidak cocok.";
            }

            if (docFileInput && docFileInput.files && docFileInput.files[0]) {
                var docCheck = isValidDocumentFile(docFileInput.files[0]);
                if (docCheck !== true) {
                    sMsg += "\n" + docCheck;
                }
            }

            if (sMsg !== '') {
                alert("Harap perbaiki kesalahan berikut:" + sMsg);
                e.preventDefault();
                return false;
            }

            var btnRoleDonor = document.getElementById('btnRoleDonor');
            var selectedRole = (btnRoleDonor && btnRoleDonor.classList.contains('active')) ? 'produsen' : 'konsumen';
            var regRole = document.getElementById('regRole');
            if (regRole) {
                regRole.value = selectedRole;
            }

            try {
                localStorage.setItem('tubespwd_role', selectedRole);
                sessionStorage.setItem('tubespwd_role', selectedRole);
                localStorage.setItem('tubespwd_username', valUser);
                localStorage.setItem('tubespwd_fullname', valName);
            } catch (err) {}

            var regAction = registerForm.getAttribute('action') || '';
            var isPhpRegister = (regAction.indexOf('.php') !== -1);

            if (!isPhpRegister) {
                alert("Pendaftaran berhasil sebagai " + (selectedRole === 'produsen' ? 'Donatur Pangan (Produsen)' : 'Penerima Manfaat (Konsumen)') + "! Silakan login.");
            }
        });
    }
});
