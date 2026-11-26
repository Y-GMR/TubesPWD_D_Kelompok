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

    if (catNameInput && !catNameInput.placeholder) {
        catNameInput.placeholder = 'Contoh: Roti & Kue';
    }
    if (catDescInput && !catDescInput.placeholder) {
        catDescInput.placeholder = 'Tuliskan deskripsi singkat kelompok makanan ini...';
    }

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
            var desc = catDescInput ? catDescInput.value.trim() : '';

            if (name.length < 2 || name.length > 100) {
                e.preventDefault();
                window.alert('Nama kategori wajib diisi antara 2 hingga 100 karakter.');
                if (catNameInput) catNameInput.focus();
                return;
            }
            if (desc.length > 500) {
                e.preventDefault();
                window.alert('Deskripsi kategori maksimal 500 karakter.');
                if (catDescInput) catDescInput.focus();
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
                var row = target.closest('tr');
                if (!row) return;

                var idElem = row.querySelector('.col-cat-id');
                var nameElem = row.querySelector('.col-cat-name');
                var descElem = row.querySelector('.col-cat-desc');

                var id = idElem ? idElem.textContent.replace('#', '').trim() : '';
                var name = nameElem ? nameElem.textContent.trim() : '';
                var desc = descElem ? descElem.textContent.trim() : '';
                if (desc === '-') desc = '';

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
                var row = form.closest('tr');
                var nameElem = row ? row.querySelector('.col-cat-name') : null;
                var catName = nameElem ? nameElem.textContent.trim() : 'kategori ini';
                var confirmed = window.confirm('Apakah Anda yakin ingin menghapus kategori "' + catName + '"?');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }
});
