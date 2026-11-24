document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var categoryForm = document.getElementById('categoryForm');
    var formCardTitle = document.getElementById('formCardTitle');
    var catActionInput = document.getElementById('catActionInput');
    var catIdInput = document.getElementById('catIdInput');
    var catNameInput = document.getElementById('catNameInput');
    var catDescInput = document.getElementById('catDescInput');
    var btnSubmitCategory = document.getElementById('btnSubmitCategory');
    var btnCancelEdit = document.getElementById('btnCancelEdit');
    var categoriesTableBody = document.getElementById('categoriesTableBody');

    function resetForm() {
        if (catActionInput) catActionInput.value = 'create';
        if (catIdInput) catIdInput.value = '';
        if (catNameInput) catNameInput.value = '';
        if (catDescInput) catDescInput.value = '';
        if (formCardTitle) formCardTitle.textContent = 'Tambah Kategori Baru';
        if (btnSubmitCategory) btnSubmitCategory.textContent = 'Simpan Kategori';
        if (btnCancelEdit) btnCancelEdit.style.display = 'none';
        if (catNameInput) catNameInput.focus();
    }

    if (categoryForm) {
        categoryForm.addEventListener('submit', function (e) {
            var name = catNameInput ? catNameInput.value.trim() : '';
            if (name.length < 2) {
                e.preventDefault();
                window.alert('Nama kategori wajib diisi minimal 2 karakter.');
                if (catNameInput) catNameInput.focus();
            }
        });
    }

    if (btnCancelEdit) {
        btnCancelEdit.addEventListener('click', function () {
            resetForm();
        });
    }

    if (categoriesTableBody) {
        categoriesTableBody.addEventListener('click', function (e) {
            var target = e.target;

            if (target.classList.contains('btn-cat-edit')) {
                var id = target.getAttribute('data-id') || '';
                var name = target.getAttribute('data-name') || '';
                var desc = target.getAttribute('data-desc') || '';

                if (catActionInput) catActionInput.value = 'update';
                if (catIdInput) catIdInput.value = id;
                if (catNameInput) catNameInput.value = name;
                if (catDescInput) catDescInput.value = desc;

                if (formCardTitle) formCardTitle.textContent = 'Ubah Kategori (#' + id + ')';
                if (btnSubmitCategory) btnSubmitCategory.textContent = 'Perbarui Kategori';
                if (btnCancelEdit) btnCancelEdit.style.display = 'block';

                if (catNameInput) {
                    catNameInput.focus();
                    catNameInput.select();
                }

                if (formCardTitle && formCardTitle.scrollIntoView) {
                    formCardTitle.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else if (target.classList.contains('btn-cat-delete-disabled')) {
                window.alert('Kategori tidak dapat dihapus karena masih memiliki makanan yang terdaftar dalam katalog.');
            }
        });

        var deleteForms = categoriesTableBody.querySelectorAll('.form-cat-delete');
        deleteForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var catName = form.getAttribute('data-name') || 'kategori ini';
                var confirmed = window.confirm('Apakah Anda yakin ingin menghapus kategori "' + catName + '"?');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }
});
