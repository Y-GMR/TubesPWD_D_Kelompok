document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var profilePillBtn = document.getElementById('profilePillBtn');
    var profileDropdownMenu = document.getElementById('profileDropdownMenu');

    if (profilePillBtn && profileDropdownMenu) {
        profilePillBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!profileDropdownMenu.contains(e.target) && e.target !== profilePillBtn) {
                profileDropdownMenu.classList.remove('show');
            }
        });
    }

    var categoryForm = document.getElementById('categoryForm');
    var formCardTitle = document.getElementById('formCardTitle');
    var catIdInput = document.getElementById('catIdInput');
    var catNameInput = document.getElementById('catNameInput');
    var catDescInput = document.getElementById('catDescInput');
    var btnSubmitCategory = document.getElementById('btnSubmitCategory');
    var btnCancelEdit = document.getElementById('btnCancelEdit');
    var categoriesTableBody = document.getElementById('categoriesTableBody');

    var isEditing = false;
    var editingRow = null;

    function resetForm() {
        isEditing = false;
        editingRow = null;
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
            e.preventDefault();

            var name = catNameInput ? catNameInput.value.trim() : '';
            var desc = catDescInput ? catDescInput.value.trim() : '';

            if (!name) {
                alert('Nama kategori wajib diisi.');
                if (catNameInput) catNameInput.focus();
                return;
            }

            if (isEditing && editingRow) {
                var nameElem = editingRow.querySelector('.col-cat-name');
                var descElem = editingRow.querySelector('.col-cat-desc');

                if (nameElem) nameElem.textContent = name;
                if (descElem) descElem.textContent = desc || '-';

                editingRow.classList.remove('cat-row-highlight');
                void editingRow.offsetWidth;
                editingRow.classList.add('cat-row-highlight');
                setTimeout(function () {
                    editingRow.classList.remove('cat-row-highlight');
                }, 2000);

                resetForm();
            } else {
                var currentRows = categoriesTableBody.querySelectorAll('tr');
                var nextIdNumber = currentRows.length + 1;
                var nextIdString = '#' + nextIdNumber;

                var newRow = document.createElement('tr');
                newRow.setAttribute('data-id', nextIdString);
                newRow.setAttribute('data-count', '0');

                var tdId = document.createElement('td');
                tdId.className = 'col-cat-id';
                tdId.textContent = nextIdString;

                var tdName = document.createElement('td');
                tdName.className = 'col-cat-name';
                tdName.textContent = name;

                var tdDesc = document.createElement('td');
                tdDesc.className = 'col-cat-desc';
                tdDesc.textContent = desc || '-';

                var tdCount = document.createElement('td');
                var badge = document.createElement('span');
                badge.className = 'badge-menu-count badge-zero-menu';
                badge.textContent = '0 Menu';
                tdCount.appendChild(badge);

                var tdAction = document.createElement('td');
                var actionCell = document.createElement('div');
                actionCell.className = 'cat-actions-cell';

                var btnEdit = document.createElement('button');
                btnEdit.type = 'button';
                btnEdit.className = 'btn-cat-edit';
                btnEdit.textContent = 'Ubah';

                var btnDelete = document.createElement('button');
                btnDelete.type = 'button';
                btnDelete.className = 'btn-cat-delete-active';
                btnDelete.textContent = 'Hapus';

                actionCell.appendChild(btnEdit);
                actionCell.appendChild(btnDelete);
                tdAction.appendChild(actionCell);

                newRow.appendChild(tdId);
                newRow.appendChild(tdName);
                newRow.appendChild(tdDesc);
                newRow.appendChild(tdCount);
                newRow.appendChild(tdAction);

                categoriesTableBody.appendChild(newRow);

                newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                newRow.classList.add('cat-row-highlight');
                setTimeout(function () {
                    newRow.classList.remove('cat-row-highlight');
                }, 2000);

                resetForm();
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

                var id = row.getAttribute('data-id') || '';
                var nameElem = row.querySelector('.col-cat-name');
                var descElem = row.querySelector('.col-cat-desc');

                var name = nameElem ? nameElem.textContent.trim() : '';
                var desc = descElem ? descElem.textContent.trim() : '';
                if (desc === '-') desc = '';

                isEditing = true;
                editingRow = row;

                if (catIdInput) catIdInput.value = id;
                if (catNameInput) catNameInput.value = name;
                if (catDescInput) catDescInput.value = desc;

                if (formCardTitle) formCardTitle.textContent = 'Ubah Kategori (' + id + ')';
                if (btnSubmitCategory) btnSubmitCategory.textContent = 'Perbarui Kategori';
                if (btnCancelEdit) btnCancelEdit.style.display = 'block';

                if (catNameInput) {
                    catNameInput.focus();
                    catNameInput.select();
                }

                formCardTitle.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (target.classList.contains('btn-cat-delete-active')) {
                var deleteRow = target.closest('tr');
                if (!deleteRow) return;

                var delNameElem = deleteRow.querySelector('.col-cat-name');
                var catName = delNameElem ? delNameElem.textContent.trim() : 'kategori ini';

                var confirmed = window.confirm('Apakah Anda yakin ingin menghapus kategori "' + catName + '"?');
                if (confirmed) {
                    if (isEditing && editingRow === deleteRow) {
                        resetForm();
                    }
                    deleteRow.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    deleteRow.style.opacity = '0';
                    deleteRow.style.transform = 'translateX(20px)';
                    setTimeout(function () {
                        if (deleteRow.parentNode) {
                            deleteRow.parentNode.removeChild(deleteRow);
                        }
                    }, 260);
                }
            } else if (target.classList.contains('btn-cat-delete-disabled')) {
                alert('Kategori tidak dapat dihapus karena masih memiliki makanan yang terdaftar dalam katalog.');
            }
        });
    }
});
