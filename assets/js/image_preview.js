(function () {
    'use strict';

    var MAX_FILE_SIZE = 2 * 1024 * 1024;
    var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    var ALLOWED_EXTS = ['.jpg', '.jpeg', '.png', '.webp'];

    function isValidImageExtension(filename) {
        var lower = (filename || '').toLowerCase();
        for (var i = 0; i < ALLOWED_EXTS.length; i++) {
            if (lower.indexOf(ALLOWED_EXTS[i], lower.length - ALLOWED_EXTS[i].length) !== -1) {
                return true;
            }
        }
        return false;
    }

    function handleFileSelection(fileInput, previewTarget) {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        if (file.size > MAX_FILE_SIZE) {
            alert('Ukuran berkas maksimal adalah 2MB.');
            fileInput.value = '';
            return;
        }

        var isTypeAllowed = ALLOWED_TYPES.indexOf(file.type) !== -1 || isValidImageExtension(file.name);
        if (!isTypeAllowed) {
            alert('Format berkas harus berupa JPG, PNG, atau WEBP.');
            fileInput.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            if (previewTarget) {
                if (previewTarget.tagName.toLowerCase() === 'img') {
                    previewTarget.src = e.target.result;
                    previewTarget.style.display = 'block';
                } else {
                    var innerImg = previewTarget.querySelector('img');
                    if (innerImg) {
                        innerImg.src = e.target.result;
                        innerImg.style.display = 'block';
                    } else {
                        var newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Pratinjau Foto';
                        newImg.style.width = '100%';
                        newImg.style.height = '100%';
                        newImg.style.objectFit = 'cover';
                        newImg.style.borderRadius = '50%';
                        while (previewTarget.firstChild) {
                            previewTarget.removeChild(previewTarget.firstChild);
                        }
                        previewTarget.appendChild(newImg);
                    }
                }
            }
        };
        reader.readAsDataURL(file);
    }

    function bindFileInput(fileInput, targetEl) {
        if (!fileInput) return;
        fileInput.addEventListener('change', function () {
            handleFileSelection(fileInput, targetEl);
        });
    }

    function initImagePreview() {
        var uploadButtons = document.querySelectorAll('.btn-upload-photo, #btnUploadPhoto');
        uploadButtons.forEach(function (btn) {
            var fileInput = document.getElementById('profilePhotoInput');
            if (!fileInput) {
                fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.id = 'profilePhotoInput';
                fileInput.name = 'foto_profil';
                fileInput.accept = 'image/jpeg,image/png,image/webp';
                fileInput.style.display = 'none';
                document.body.appendChild(fileInput);
            }

            var avatarCircle = document.querySelector('.profile-large-avatar-circle') || document.getElementById('avatarPreviewContainer');

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                fileInput.click();
            });

            bindFileInput(fileInput, avatarCircle);
        });

        var genericFileInputs = document.querySelectorAll('input[type="file"].image-preview-input, input[type="file"][data-preview-target]');
        genericFileInputs.forEach(function (input) {
            var targetSelector = input.getAttribute('data-preview-target');
            var targetEl = targetSelector ? document.querySelector(targetSelector) : null;
            bindFileInput(input, targetEl);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImagePreview);
    } else {
        initImagePreview();
    }

    window.ImagePreview = {
        bind: bindFileInput,
        init: initImagePreview
    };
})();
