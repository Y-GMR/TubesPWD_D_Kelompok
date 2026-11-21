document.addEventListener('DOMContentLoaded', function () {
    var btnRoleConsumer = document.getElementById('btnRoleConsumer');
    var btnRoleDonor = document.getElementById('btnRoleDonor');

    var urlParams = new URLSearchParams(window.location.search);
    var roleParam = (urlParams.get('role') || '').toLowerCase();

    function setRole(role) {
        if (role === 'donor' || role === 'produsen') {
            if (btnRoleDonor && btnRoleConsumer) {
                btnRoleDonor.classList.add('active');
                btnRoleConsumer.classList.remove('active');
            }
        } else {
            if (btnRoleConsumer && btnRoleDonor) {
                btnRoleConsumer.classList.add('active');
                btnRoleDonor.classList.remove('active');
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

    if (regUsername && usernameFeedback) {
        regUsername.addEventListener('input', function () {
            var val = this.value.trim();
            if (val.length >= 3) {
                regUsername.classList.add('is-valid');
                usernameFeedback.style.display = 'block';
            } else {
                regUsername.classList.remove('is-valid');
                usernameFeedback.style.display = 'none';
            }
        });
    }
});
