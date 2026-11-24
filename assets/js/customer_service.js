(function () {
    "use strict";

    function applyUserRole() {
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
        var btn = document.getElementById("profilePillBtn");
        var menu = document.getElementById("profileDropdownMenu");
        if (!btn || !menu) return;

        btn.addEventListener("click", function (e) {
            e.stopPropagation();
            menu.classList.toggle("show");
        });

        document.addEventListener("click", function () {
            menu.classList.remove("show");
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

    function initTicketForm() {
        var form = document.getElementById("csTicketForm");
        if (!form) return;

        form.addEventListener("submit", function (e) {
            var subjectInput = document.getElementById("subjectInput");
            var topicSelect = document.getElementById("topicSelect");
            var msgArea = document.getElementById("messageTextarea");

            if (!subjectInput || !topicSelect || !msgArea) return;

            var newTicket = {
                id: "#CS-20261125-" + Math.floor(100 + Math.random() * 900),
                topic: topicSelect.value,
                subject: subjectInput.value,
                message: msgArea.value,
                date: "25 November 2026, baru saja",
                status: "pending"
            };

            try {
                sessionStorage.setItem("latest_submitted_ticket", JSON.stringify(newTicket));
            } catch (err) {}
        });
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
        initNavbarDropdown();
        initFileUpload();
        initTicketForm();
        initTicketFiltering();
    });
})();
