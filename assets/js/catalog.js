document.addEventListener('DOMContentLoaded', function () {
    var categoryPills = document.querySelectorAll('.category-pill');
    var merchantCards = document.querySelectorAll('.merchant-main-card');
    var searchInput = document.querySelector('.catalog-search-input');
    var searchForm = document.querySelector('.catalog-search-form');
    var emptyState = document.getElementById('catalogEmptyState');

    var currentCategory = 'all';
    var currentQuery = '';

    var urlParams = new URLSearchParams(window.location.search);
    var urlCat = urlParams.get('cat');
    var urlQ = urlParams.get('q');

    if (urlCat) {
        currentCategory = urlCat.toLowerCase();
    }
    if (urlQ) {
        currentQuery = urlQ.toLowerCase();
        if (searchInput) {
            searchInput.value = urlQ;
        }
    }

    function applyFilter() {
        var visibleCount = 0;

        merchantCards.forEach(function (card) {
            var cardCategories = (card.getAttribute('data-category') || '').toLowerCase().split(' ');
            var cardText = card.textContent.toLowerCase();

            var categoryMatch = (currentCategory === 'all') || (cardCategories.indexOf(currentCategory) !== -1);
            var queryMatch = !currentQuery || (cardText.indexOf(currentQuery) !== -1);

            if (categoryMatch && queryMatch) {
                card.classList.remove('is-hidden');
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.classList.add('is-hidden');
                card.style.display = 'none';
            }
        });

        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }
        }

        categoryPills.forEach(function (pill) {
            var pillCat = pill.getAttribute('data-cat') || 'all';
            if (pillCat === currentCategory) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    categoryPills.forEach(function (pill) {
        pill.addEventListener('click', function (e) {
            e.preventDefault();
            var targetCat = this.getAttribute('data-cat') || 'all';
            currentCategory = targetCat;

            applyFilter();

            try {
                if (window.history && window.history.pushState) {
                    var newUrl = new URL(window.location.href);
                    if (currentCategory === 'all') {
                        newUrl.searchParams.delete('cat');
                    } else {
                        newUrl.searchParams.set('cat', currentCategory);
                    }
                    window.history.pushState({}, '', newUrl.toString());
                }
            } catch (err) {
            }
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentQuery = this.value.trim().toLowerCase();
            applyFilter();
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilter();
        });
    }

    var resetBtn = document.getElementById('btnResetFilter');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            currentCategory = 'all';
            currentQuery = '';
            if (searchInput) {
                searchInput.value = '';
            }
            applyFilter();

            try {
                if (window.history && window.history.pushState) {
                    var newUrl = new URL(window.location.href);
                    newUrl.searchParams.delete('cat');
                    newUrl.searchParams.delete('q');
                    window.history.pushState({}, '', newUrl.toString());
                }
            } catch (err) {
            }
        });
    }

    applyFilter();
});
