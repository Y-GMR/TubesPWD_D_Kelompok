(function () {
    'use strict';

    var currentCoords = {
        latitude: null,
        longitude: null,
        accuracy: null
    };

    function injectCoordinatesToForm(lat, lng) {
        var forms = document.querySelectorAll('form');
        forms.forEach(function (form) {
            var latInput = form.querySelector('input[name="latitude"]');
            var lngInput = form.querySelector('input[name="longitude"]');

            if (!latInput) {
                latInput = document.createElement('input');
                latInput.type = 'hidden';
                latInput.name = 'latitude';
                form.appendChild(latInput);
            }
            latInput.value = lat;

            if (!lngInput) {
                lngInput = document.createElement('input');
                lngInput.type = 'hidden';
                lngInput.name = 'longitude';
                form.appendChild(lngInput);
            }
            lngInput.value = lng;
        });

        var previewEl = document.getElementById('geoCoordinatesPreview') || document.querySelector('.geo-preview-text');
        if (previewEl) {
            previewEl.textContent = 'Koordinat GPS Anda: ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
            previewEl.style.display = 'block';
        }

        var statusEl = document.getElementById('geoStatusText');
        if (statusEl) {
            statusEl.textContent = 'Lokasi GPS berhasil terdeteksi.';
            statusEl.style.color = '#15803d';
        }
    }

    function handleGeoError(err) {
        var statusEl = document.getElementById('geoStatusText');
        if (!statusEl) return;

        var message = 'Gagal mengakses GPS. Menggunakan alamat pengambilan default.';
        if (err && err.code === 1) {
            message = 'Akses lokasi ditolak pengguna. Menggunakan alamat pengambilan default.';
        } else if (err && err.code === 2) {
            message = 'Posisi GPS tidak tersedia. Menggunakan alamat pengambilan default.';
        } else if (err && err.code === 3) {
            message = 'Waktu permintaan GPS habis. Menggunakan alamat pengambilan default.';
        }

        statusEl.textContent = message;
        statusEl.style.color = '#6b7280';
    }

    function requestLocation(onSuccess, onError) {
        if (!('geolocation' in navigator)) {
            if (onError) onError(new Error('Geolocation tidak didukung oleh peramban ini.'));
            return;
        }

        var options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        };

        navigator.geolocation.getCurrentPosition(
            function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                var acc = position.coords.accuracy;

                currentCoords.latitude = lat;
                currentCoords.longitude = lng;
                currentCoords.accuracy = acc;

                injectCoordinatesToForm(lat, lng);

                if (typeof onSuccess === 'function') {
                    onSuccess(currentCoords);
                }
            },
            function (err) {
                handleGeoError(err);
                if (typeof onError === 'function') {
                    onError(err);
                }
            },
            options
        );
    }

    function initGeolocation() {
        var geoButtons = document.querySelectorAll('#btnDetectLocation, .btn-detect-gps');
        geoButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                btn.disabled = true;
                btn.textContent = 'Mendeteksi...';
                requestLocation(
                    function () {
                        btn.disabled = false;
                        btn.textContent = 'Perbarui Lokasi GPS';
                    },
                    function () {
                        btn.disabled = false;
                        btn.textContent = 'Deteksi Ulang GPS';
                    }
                );
            });
        });

        var checkoutContainer = document.querySelector('.checkout-container, .checkout-page-wrapper');
        if (checkoutContainer) {
            requestLocation();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGeolocation);
    } else {
        initGeolocation();
    }

    window.AppGeolocation = {
        requestLocation: requestLocation,
        getCoordinates: function () {
            return currentCoords;
        }
    };
})();
