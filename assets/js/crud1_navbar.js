(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var dropdown = document.querySelector('.profile-consumer-dropdown');
        var btn = document.getElementById('profilePillBtn');
        if (!dropdown || !btn) {
            return;
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle('is-open');
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                dropdown.classList.remove('is-open');
            }
        });
    });
})();
