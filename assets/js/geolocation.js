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
            latInput.value = (typeof lat === 'number') ? lat.toFixed(6) : lat;

            if (!lngInput) {
                lngInput = document.createElement('input');
                lngInput.type = 'hidden';
                lngInput.name = 'longitude';
                form.appendChild(lngInput);
            }
            lngInput.value = (typeof lng === 'number') ? lng.toFixed(6) : lng;
        });

        var previewEl = document.getElementById('geoCoordinatesPreview') || document.querySelector('.geo-preview-text');
        if (previewEl) {
            previewEl.style.display = 'none';
        }

        var statusEl = document.getElementById('geoStatusText');
        if (statusEl) {
            var latFormatted = (typeof lat === 'number') ? lat.toFixed(5) : lat;
            var lngFormatted = (typeof lng === 'number') ? lng.toFixed(5) : lng;
            statusEl.textContent = 'Lokasi GPS berhasil diperbarui (' + latFormatted + ', ' + lngFormatted + ')';
            statusEl.style.color = '#15803d';
        }

        var statusBox = document.querySelector('.geo-status-box');
        if (statusBox) {
            statusBox.style.display = 'flex';
            statusBox.style.justifyContent = 'center';
            statusBox.style.textAlign = 'center';
        }
    }

    function handleGeoError(err) {
        var statusEl = document.getElementById('geoStatusText');
        if (!statusEl) return;

        var message = 'Gagal mengakses GPS';
        if (err && err.code === 1) {
            message = 'Akses lokasi ditolak pengguna';
        } else if (err && err.code === 2) {
            message = 'Posisi GPS tidak tersedia';
        } else if (err && err.code === 3) {
            message = 'Waktu permintaan GPS habis';
        }

        statusEl.textContent = message;
        statusEl.style.color = '#6b7280';
    }

    function requestLocation(onSuccess, onError) {
        if (!('geolocation' in navigator)) {
            if (onError) onError(new Error('Geolocation tidak didukung oleh peramban ini'));
            return;
        }

        var options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
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
                btn.textContent = 'Mendeteksi Lokasi GPS...';
                requestLocation(
                    function () {
                        btn.disabled = false;
                        btn.textContent = 'Perbarui Lokasi GPS';
                    },
                    function () {
                        btn.disabled = false;
                        btn.textContent = 'Perbarui Lokasi GPS';
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