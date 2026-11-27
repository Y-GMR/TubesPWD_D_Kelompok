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

    var dbUsers = [
        {
            username: 'admin',
            email: 'admin@pangkalanpangan.id',
            passwords: ['password123', 'admin123'],
            role: 'admin',
            name: 'Administrator Pangkalan Pangan',
            target: 'views/admin/dashboard.php'
        },
        {
            username: 'kfc_babarsari',
            aliases: ['kfcsudirman', 'kfc', 'produsen'],
            email: 'kfc@pangkalanpangan.id',
            passwords: ['password123', 'AyamGorengOriginal', 'kfc123'],
            role: 'produsen',
            name: 'KFC Babarsari',
            target: 'producer_dashboard.xhtml'
        },
        {
            username: 'dunkin_sudirman',
            aliases: ['dunkin'],
            email: 'dunkin@pangkalanpangan.id',
            passwords: ['password123', 'dunkin123'],
            role: 'produsen',
            name: 'Dunkin Donuts Sudirman',
            target: 'producer_dashboard.xhtml'
        },
        {
            username: 'breadtalk_seturan',
            aliases: ['breadtalk'],
            email: 'breadtalk@pangkalanpangan.id',
            passwords: ['password123'],
            role: 'produsen',
            name: 'BreadTalk Seturan',
            target: 'producer_dashboard.xhtml'
        },
        {
            username: 'pizzahut_gejayan',
            aliases: ['pizzahut'],
            email: 'pizzahut@pangkalanpangan.id',
            passwords: ['password123'],
            role: 'produsen',
            name: 'Pizza Hut Gejayan',
            target: 'producer_dashboard.xhtml'
        },
        {
            username: 'iwang_petra',
            aliases: ['yillogmr', 'petra', 'konsumen'],
            email: 'iwangpetra@gmail.com',
            passwords: ['password123', 'PetraKonsumenLapar', 'petra123'],
            role: 'konsumen',
            name: 'Iwang Petra',
            target: 'food_catalogue.xhtml'
        },
        {
            username: 'budi_santoso',
            aliases: ['budi'],
            email: 'budi@gmail.com',
            passwords: ['password123'],
            role: 'konsumen',
            name: 'Budi Santoso',
            target: 'food_catalogue.xhtml'
        }
    ];

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

            var identifier = (valUser !== '' ? valUser : valEmail).toLowerCase();
            var matchedUser = null;

            for (var i = 0; i < dbUsers.length; i++) {
                var u = dbUsers[i];
                var matchUser = (u.username.toLowerCase() === identifier);
                var matchEmail = (u.email.toLowerCase() === identifier);
                var matchAlias = false;

                if (u.aliases && u.aliases.length > 0) {
                    for (var j = 0; j < u.aliases.length; j++) {
                        if (u.aliases[j].toLowerCase() === identifier) {
                            matchAlias = true;
                            break;
                        }
                    }
                }

                if (matchUser || matchEmail || matchAlias) {
                    var matchPass = false;
                    for (var k = 0; k < u.passwords.length; k++) {
                        if (u.passwords[k] === valPass) {
                            matchPass = true;
                            break;
                        }
                    }

                    if (matchPass) {
                        matchedUser = u;
                        break;
                    }
                }
            }

            var formAction = loginForm.getAttribute('action') || '';
            var isPhpBackend = (formAction.indexOf('.php') !== -1);

            if (matchedUser) {
                try {
                    localStorage.setItem('tubespwd_role', matchedUser.role);
                    sessionStorage.setItem('tubespwd_role', matchedUser.role);
                    localStorage.setItem('tubespwd_username', matchedUser.username);
                    localStorage.setItem('tubespwd_fullname', matchedUser.name);
                } catch (err) {}

                if (!isPhpBackend) {
                    loginForm.action = matchedUser.target;
                }
            } else {
                if (!isPhpBackend) {
                    alert("Kombinasi username/email dan kata sandi tidak sesuai dengan akun basis data.");
                    e.preventDefault();
                    return false;
                }
            }
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

            if (valName.length < 2) {
                sMsg += "\nNama lengkap minimal harus 2 karakter.";
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
