document.addEventListener('DOMContentLoaded', function () {
    var merchantsData = {
        'pizzahut': {
            name: 'Pizza Hut Gejayan',
            storeName: 'Gerai Pizza Hut Gejayan',
            deadline: 'Hari ini, 17:00 WIB'
        },
        'dunkin': {
            name: 'Dunkin Donuts Sudirman',
            storeName: 'Gerai Dunkin Donuts Sudirman',
            deadline: 'Hari ini, 15:00 WIB'
        },
        'breadtalk': {
            name: 'BreadTalk Seturan',
            storeName: 'Gerai BreadTalk Seturan',
            deadline: 'Hari ini, 16:00 WIB'
        },
        'kfc': {
            name: 'KFC Babarsari',
            storeName: 'Gerai KFC Babarsari',
            deadline: 'Hari ini, 18:00 WIB'
        }
    };

    var urlParams = new URLSearchParams(window.location.search);
    var codeParam = urlParams.get('code');
    var merchantParam = (urlParams.get('merchant') || '').toLowerCase();

    var codeEl = document.getElementById('ticketCodeText');
    if (codeEl) {
        if (codeParam) {
            codeEl.textContent = codeParam;
        } else {
            codeEl.textContent = 'TRX-8821';
        }
    }

    var merchantInfo = merchantsData[merchantParam] || merchantsData['dunkin'];

    var placeEl = document.getElementById('ticketPlaceText');
    if (placeEl) {
        placeEl.textContent = merchantInfo.name;
    }

    var deadlineEl = document.getElementById('ticketDeadlineText');
    if (deadlineEl) {
        deadlineEl.textContent = merchantInfo.deadline;
    }
});