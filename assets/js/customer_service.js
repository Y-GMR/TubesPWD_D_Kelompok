(function () {
    "use strict";

    function applyUserRole() {
        if (window.location.pathname.indexOf('.php') !== -1) {
            return;
        }
        var params = new URLSearchParams(window.location.search);
        var role = params.get("role");
        if (!role) {
            try {
                role = localStorage.getItem("tubespwd_role");
                if (!role) {
                    role = sessionStorage.getItem("tubespwd_role");
                }
            } catch (e) {}
        }

        if (!role && document.referrer) {
            if (document.referrer.indexOf("producer") !== -1) {
                role = "produsen";
            }
        }

        var nameEl = document.querySelector(".profile-consumer-name");
        var roleEl = document.querySelector(".profile-consumer-role");
        var avatarEl = document.querySelector(".profile-consumer-avatar");
        var pillBtn = document.querySelector(".profile-consumer-pill");

        if (role === "konsumen") {
            try {
                localStorage.setItem("tubespwd_role", "konsumen");
                sessionStorage.setItem("tubespwd_role", "konsumen");
            } catch (e) {}
            if (nameEl) nameEl.textContent = "Iwang Petra";
            if (roleEl) roleEl.textContent = "Konsumen";
            if (avatarEl) {
                avatarEl.src = "assets/images/avatar_consumer.png";
                avatarEl.alt = "Avatar Iwang Petra";
            }
            if (pillBtn) pillBtn.title = "Profil Konsumen - Iwang Petra";
        } else {
            try {
                localStorage.setItem("tubespwd_role", "produsen");
                sessionStorage.setItem("tubespwd_role", "produsen");
            } catch (e) {}
            if (nameEl) nameEl.textContent = "KFC Sudirman";
            if (roleEl) roleEl.textContent = "Produsen";
            if (avatarEl) {
                avatarEl.src = "assets/images/producer/avatar_produsen.png";
                avatarEl.alt = "Avatar KFC Sudirman";
            }
            if (pillBtn) pillBtn.title = "Profil Produsen - KFC Sudirman";
        }
    }

    function initNavbarDropdown() {
        var dropdown = document.querySelector(".profile-consumer-dropdown");
        var btn = document.getElementById("profilePillBtn");
        if (!dropdown || !btn) return;

        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle("is-open");
        });

        document.addEventListener("click", function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove("is-open");
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" || e.keyCode === 27) {
                dropdown.classList.remove("is-open");
            }
        });
    }

    function initFileUpload() {
        var fileInput = document.getElementById("fileAttachment");
        var labelText = document.getElementById("fileAttachmentLabel");
        if (!fileInput || !labelText) return;

        fileInput.addEventListener("change", function () {
            if (fileInput.files && fileInput.files.length > 0) {
                var file = fileInput.files[0];
                var sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                labelText.textContent = "Berkas terpilih: " + file.name + " (" + sizeMb + " MB)";
                labelText.style.fontWeight = "600";
            } else {
                labelText.textContent = "Pilih Berkas Foto Bukti (JPG, PNG maks. 2MB) — Sangat disarankan jika melaporkan makanan basi/rusak";
                labelText.style.fontWeight = "400";
            }
        });
    }

    function applyPlaceholders() {
        var placeholderMap = [
            { id: "emailInput", text: "nama@posko.id" },
            { id: "nameInput", text: "Nama lengkap sesuai KTP / profil" },
            { id: "phoneInput", text: "0812-xxxx-xxxx (dapat dihubungi jika butuh klarifikasi cepat)" },
            { id: "trxCodeInput", text: "Contoh: #PP-20261122-001 (Kosongkan jika bukan mengenai klaim)" },
            { id: "subjectInput", text: "Ringkasan kendala Anda dalam satu kalimat" },
            { id: "messageTextarea", text: "Jelaskan kendala Anda secara rinci, termasuk waktu kejadian, nama posko donatur, dan bantuan yang diharapkan..." },
            { id: "ticketSearchInput", text: "Cari nomor tiket atau kata kunci..." }
        ];
        for (var i = 0; i < placeholderMap.length; i++) {
            var el = document.getElementById(placeholderMap[i].id);
            if (el && !el.placeholder) {
                el.placeholder = placeholderMap[i].text;
            }
        }
    }

    function initTicketForm() {
        var form = document.getElementById("csTicketForm");
        if (!form) return;

        form.addEventListener("submit", function (e) {
            var emailInput = document.getElementById("emailInput");
            var nameInput = document.getElementById("nameInput");
            var subjectInput = document.getElementById("subjectInput");
            var topicSelect = document.getElementById("topicSelect");
            var msgArea = document.getElementById("messageTextarea");

            if (!subjectInput || !topicSelect || !msgArea) return;

            var errors = [];
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailInput && !emailInput.readOnly) {
                var emailVal = emailInput.value.trim();
                if (!emailVal || !emailPattern.test(emailVal)) {
                    errors.push("Alamat surel aktif wajib diisi dengan format yang valid.");
                }
            }
            if (nameInput && !nameInput.readOnly && !nameInput.value.trim()) {
                errors.push("Nama lengkap wajib diisi.");
            }
            if (!topicSelect.value.trim()) {
                errors.push("Topik bantuan wajib dipilih.");
            }
            if (!subjectInput.value.trim()) {
                errors.push("Judul atau subjek tiket wajib diisi.");
            }
            if (!msgArea.value.trim()) {
                errors.push("Rincian masalah atau pesan pengaduan wajib diisi.");
            }

            if (errors.length > 0) {
                alert(errors.join("\n"));
                e.preventDefault();
                return;
            }

            var trxCodeInput = document.getElementById("trxCodeInput");
            var trxCodeVal = trxCodeInput ? trxCodeInput.value.trim() : "";

            var newTicket = {
                id: "#CS-20261125-" + Math.floor(100 + Math.random() * 900),
                topic: topicSelect.value,
                trxCode: trxCodeVal,
                subject: subjectInput.value.trim(),
                message: msgArea.value.trim(),
                date: "Baru saja",
                status: "pending"
            };

            try {
                sessionStorage.setItem("latest_submitted_ticket", JSON.stringify(newTicket));
            } catch (err) {}

            var actionUrl = form.getAttribute("action") || "";
            if (actionUrl.indexOf(".xhtml") !== -1) {
                e.preventDefault();
                window.location.href = actionUrl;
            }
        });
    }

    function renderSubmittedTicket() {
        var listContainer = document.getElementById("ticketListContainer");
        if (!listContainer || window.location.pathname.indexOf(".php") !== -1) return;

        var rawTicket = null;
        try {
            rawTicket = sessionStorage.getItem("latest_submitted_ticket");
        } catch (err) {}
        if (!rawTicket) return;

        var data = null;
        try {
            data = JSON.parse(rawTicket);
        } catch (err) {}
        if (!data || !data.id || document.querySelector('.ticket-item-card[data-ticket-id="' + data.id + '"]')) {
            return;
        }

        var card = document.createElement("div");
        card.className = "ticket-item-card";
        card.setAttribute("data-status", "pending");
        card.setAttribute("data-ticket-id", data.id);

        var topLine = document.createElement("div");
        topLine.className = "ticket-top-line";
        var metaLeft = document.createElement("div");
        metaLeft.className = "ticket-meta-left";
        var idSpan = document.createElement("span");
        idSpan.className = "ticket-id";
        idSpan.textContent = data.id;
        var dateSpan = document.createElement("span");
        dateSpan.className = "ticket-date";
        dateSpan.textContent = " \u2022 Diajukan pada: " + (data.date || "Baru saja");
        metaLeft.appendChild(idSpan);
        metaLeft.appendChild(dateSpan);

        var badge = document.createElement("span");
        badge.className = "ticket-badge ticket-badge-pending";
        badge.textContent = "Menunggu Balasan";
        topLine.appendChild(metaLeft);
        topLine.appendChild(badge);

        var topicDiv = document.createElement("div");
        topicDiv.className = "ticket-topic";
        topicDiv.textContent = "Topik: " + (data.topic || "-") + (data.trxCode ? " | Terkait: " + data.trxCode : "");

        var subjectDiv = document.createElement("div");
        subjectDiv.className = "ticket-subject";
        subjectDiv.textContent = "Subjek: " + (data.subject || "-");

        var msgBox = document.createElement("div");
        msgBox.className = "ticket-msg-box";
        var msgLabel = document.createElement("div");
        msgLabel.className = "ticket-msg-label";
        msgLabel.textContent = "Pesan Anda:";
        var msgText = document.createElement("p");
        msgText.className = "ticket-msg-text";
        msgText.textContent = '"' + (data.message || "") + '"';
        msgBox.appendChild(msgLabel);
        msgBox.appendChild(msgText);

        var noticeBox = document.createElement("div");
        noticeBox.className = "ticket-notice-box";
        var noticeText = document.createElement("p");
        noticeText.className = "ticket-notice-text";
        noticeText.textContent = "Status: Tiket baru Anda telah diterima dan masuk antrean petugas.";
        noticeBox.appendChild(noticeText);

        card.appendChild(topLine);
        card.appendChild(topicDiv);
        card.appendChild(subjectDiv);
        card.appendChild(msgBox);
        card.appendChild(noticeBox);

        listContainer.insertBefore(card, listContainer.firstChild);

        var allCards = listContainer.querySelectorAll(".ticket-item-card");
        var pendingCards = listContainer.querySelectorAll('.ticket-item-card[data-status="pending"]');
        var doneCards = listContainer.querySelectorAll('.ticket-item-card[data-status="done"]');
        var pills = document.querySelectorAll(".ticket-filter-pill");
        for (var p = 0; p < pills.length; p++) {
            var df = pills[p].getAttribute("data-filter");
            if (df === "all") pills[p].textContent = "Semua Tiket (" + allCards.length + ")";
            if (df === "pending") pills[p].textContent = "Menunggu Balasan (" + pendingCards.length + ")";
            if (df === "done") pills[p].textContent = "Sudah Selesai (" + doneCards.length + ")";
        }
    }

    function initTicketFiltering() {
        var filterPills = document.querySelectorAll(".ticket-filter-pill");
        var searchInput = document.getElementById("ticketSearchInput");
        var ticketCards = document.querySelectorAll(".ticket-item-card");
        var emptyState = document.getElementById("ticketEmptyState");
        if (!filterPills.length && !searchInput) return;

        var currentStatus = "all";
        var currentSearch = "";

        function filterTickets() {
            var visibleCount = 0;
            var term = currentSearch.toLowerCase().trim();

            for (var i = 0; i < ticketCards.length; i++) {
                var card = ticketCards[i];
                var cardStatus = card.getAttribute("data-status");
                var cardText = card.textContent.toLowerCase();

                var statusMatch = (currentStatus === "all" || cardStatus === currentStatus);
                var searchMatch = (!term || cardText.indexOf(term) !== -1);

                if (statusMatch && searchMatch) {
                    card.style.display = "block";
                    visibleCount++;
                } else {
                    card.style.display = "none";
                }
            }

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? "block" : "none";
            }
        }

        for (var i = 0; i < filterPills.length; i++) {
            filterPills[i].addEventListener("click", function () {
                for (var j = 0; j < filterPills.length; j++) {
                    filterPills[j].classList.remove("active");
                }
                this.classList.add("active");
                currentStatus = this.getAttribute("data-filter") || "all";
                filterTickets();
            });
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                currentSearch = this.value;
                filterTickets();
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        applyUserRole();
        applyPlaceholders();
        initNavbarDropdown();
        initFileUpload();
        renderSubmittedTicket();
        initTicketForm();
        initTicketFiltering();
    });
})();
