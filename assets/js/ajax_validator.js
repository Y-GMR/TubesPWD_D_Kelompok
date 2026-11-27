(function () {
    'use strict';

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function ensureFeedbackElement(inputEl) {
        var parent = inputEl.parentElement;
        if (!parent) return null;
        var existing = parent.querySelector('#usernameFeedback, #emailFeedback, .ajax-feedback-msg');
        if (existing) {
            existing.className = 'ajax-feedback-msg auth-helper-text';
            return existing;
        }
        var feedback = document.createElement('div');
        feedback.className = 'ajax-feedback-msg';
        feedback.style.fontSize = '12px';
        feedback.style.marginTop = '4px';
        feedback.style.fontWeight = '600';
        feedback.style.transition = 'all 0.2s ease';
        parent.appendChild(feedback);
        return feedback;
    }

    function checkAvailability(field, value, inputEl) {
        var feedbackEl = ensureFeedbackElement(inputEl);
        if (!feedbackEl) return;

        var trimmedVal = (value || '').trim();
        if (trimmedVal.length === 0) {
            feedbackEl.textContent = '';
            feedbackEl.style.display = 'none';
            inputEl.style.borderColor = '';
            return;
        }

        if (field === 'username') {
            var usernameRegex = /^[a-zA-Z0-9_]{4,30}$/;
            if (!usernameRegex.test(trimmedVal)) {
                feedbackEl.textContent = '\u2717 Username minimal 4 karakter (alfanumerik dan _).';
                feedbackEl.style.color = '#dc2626';
                feedbackEl.style.display = 'block';
                inputEl.style.borderColor = '#dc2626';
                return;
            }
        } else if (field === 'email') {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(trimmedVal)) {
                feedbackEl.textContent = '\u2717 Format alamat email tidak valid.';
                feedbackEl.style.color = '#dc2626';
                feedbackEl.style.display = 'block';
                inputEl.style.borderColor = '#dc2626';
                return;
            }
        }

        feedbackEl.textContent = 'Memeriksa ketersediaan...';
        feedbackEl.style.color = '#6b7280';
        feedbackEl.style.display = 'block';

        var basePath = (window.location.pathname.indexOf('/views/') !== -1 ? '../../actions/check_user_ajax.php' : 'actions/check_user_ajax.php');
        var url = basePath + '?field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(trimmedVal);
        fetch(url)
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && data.status === 'success' && data.data && data.data.available) {
                    var msg = data.message || (field === 'username' ? 'Username tersedia.' : 'Email tersedia.');
                    feedbackEl.textContent = '\u2713 ' + msg;
                    feedbackEl.style.color = '#15803d';
                    feedbackEl.style.display = 'block';
                    inputEl.style.borderColor = '#15803d';
                } else {
                    var errMsg = (data && data.message) ? data.message : (field === 'username' ? 'Username sudah digunakan.' : 'Email sudah terdaftar.');
                    feedbackEl.textContent = '\u2717 ' + errMsg;
                    feedbackEl.style.color = '#dc2626';
                    feedbackEl.style.display = 'block';
                    inputEl.style.borderColor = '#dc2626';
                }
            })
            .catch(function () {
                feedbackEl.textContent = '';
                feedbackEl.style.display = 'none';
                inputEl.style.borderColor = '';
            });
    }

    function initAjaxValidator() {
        var usernameInputs = document.querySelectorAll('input[name="username"], #inputUsername, #regUsername');
        usernameInputs.forEach(function (input) {
            input.addEventListener('input', debounce(function () {
                checkAvailability('username', input.value, input);
            }, 300));
        });

        var emailInputs = document.querySelectorAll('input[name="email"], #inputEmail, #regEmail');
        emailInputs.forEach(function (input) {
            input.addEventListener('input', debounce(function () {
                checkAvailability('email', input.value, input);
            }, 300));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAjaxValidator);
    } else {
        initAjaxValidator();
    }

    window.AjaxValidator = {
        check: checkAvailability,
        init: initAjaxValidator
    };
})();
