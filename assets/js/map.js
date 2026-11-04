$(document).ready(function () {
    let currentStep = 1;
    const totalSteps = 4;
    let summaryMap = null;
    let summaryMarker = null;

    let uploadedFilesMap = new Map();
    let currentSortedIds = [];

    let sortableInstance = null;

    function showNotification(message, type = 'error') {
        const icon = type === 'error' ? '<ion-icon name="alert-circle-outline"></ion-icon>' : '<ion-icon name="checkmark-circle-outline"></ion-icon>';
        const toast = $(`<div class="toast-notification ${type}">${icon} <span>${message}</span></div>`);
        $('body').append(toast);
        setTimeout(() => {
            toast.addClass('show');
        }, 10);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 4000);
    }

    const map = L.map('map').setView([52.0688, 19.4797], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '&copy; OpenStreetMap'}).addTo(map);
    let currentMarker = null;
    setTimeout(() => {
        map.invalidateSize();
    }, 500);

    function formatShortAddress(addressObj) {
        if (!addressObj) return '';
        const street = addressObj.road || addressObj.pedestrian || '';
        const houseNumber = addressObj.house_number || '';
        const city = addressObj.city || addressObj.town || addressObj.village || '';
        const parts = [(street + (houseNumber ? ' ' + houseNumber : '')).trim(), city].filter(part => part.length > 0);
        return parts.join(', ');
    }

    map.on('click', function (e) {
        if (currentMarker) map.removeLayer(currentMarker);
        currentMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
        $('#lat').val(e.latlng.lat);
        $('#lng').val(e.latlng.lng);
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&accept-language=pl&addressdetails=1`, function (data) {
            if (data && data.address) $('#address').val(formatShortAddress(data.address) || data.display_name);
        });
    });

    function readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = e => reject(e);
            reader.readAsDataURL(file);
        });
    }

    function updateMainBadge() {
        $('#image-sortable-list .sortable-thumb').css({'border-color': 'transparent', 'box-shadow': 'none'});
        $('#image-sortable-list .sortable-thumb .badge-main').hide();
        const first = $('#image-sortable-list .sortable-thumb').first();
        first.css({'border-color': '#f59e0b', 'box-shadow': '0 4px 6px rgba(245, 158, 11, 0.3)'});
        first.find('.badge-main').show();
    }

    $('#m_image').on('change', async function (e) {
        const files = e.target.files;
        if (files.length === 0) {
            $('#sortable-images-container').hide();
            $('.file-info').text('Nie wybrano plików');
            return;
        }
        if (files.length > 5) {
            showNotification("Możesz dodać maksymalnie 5 zdjęć.", "error");
            $(this).val('');
            $('#sortable-images-container').hide();
            $('.file-info').text('Przekroczono limit');
            return;
        }

        $('.file-info').text(files.length + ' plików wybranych');
        $('#image-sortable-list').empty();
        uploadedFilesMap.clear();
        currentSortedIds = [];
        $('#sortable-images-container').show();

        for (let i = 0; i < files.length; i++) {
            const id = 'img_' + Math.random().toString(36).substr(2, 9);
            uploadedFilesMap.set(id, files[i]);
            currentSortedIds.push(id);
            const dataUrl = await readFileAsDataURL(files[i]);

            // ZMIANA: Zastosowano "display: inline-block" omijające błędy silnika Google Chrome
            const thumbHtml = `
                <div class="sortable-thumb" data-id="${id}" style="position: relative; display: inline-block; vertical-align: top; cursor: grab; width: 110px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid transparent; margin: 0 12px 12px 0;">
                    <div class="badge-main" style="display: none; position: absolute; top: 0; left: 0; right: 0; background: #f59e0b; color: white; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; z-index: 2;">GŁÓWNE</div>
                    <img src="${dataUrl}" draggable="false" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none; user-select: none; -webkit-user-drag: none;">
                </div>
            `;
            $('#image-sortable-list').append(thumbHtml);
        }
        updateMainBadge();


        if (sortableInstance) {
            sortableInstance.destroy();
        }

        sortableInstance = new Sortable(document.getElementById('image-sortable-list'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            forceFallback: true,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            direction: 'horizontal',
            onEnd: function () {
                currentSortedIds = [];
                $('#image-sortable-list .sortable-thumb').each(function () {
                    currentSortedIds.push($(this).data('id'));
                });
                updateMainBadge();
            }
        });
    });

    let debounceTimer;
    const suggestionsList = $('#suggestions-list');
    const searchInput = $('#location-search');

    function formatPhotonAddress(props) {
        let mainTextArr = [];
        if (props.name) mainTextArr.push(props.name);
        if (props.street && props.street !== props.name) mainTextArr.push(props.street);
        if (props.housenumber && mainTextArr.length > 0) mainTextArr[mainTextArr.length - 1] += " " + props.housenumber;
        let mainText = mainTextArr.join(', ');
        let cityText = props.city || props.town || props.village || '';
        if (cityText && !mainText.includes(cityText)) mainText = mainText ? mainText + ', ' + cityText : cityText;
        return mainText || props.state || "Nieznana lokalizacja";
    }

    $(document).on('click', e => {
        if (!$(e.target).closest('.search-input-container').length) suggestionsList.removeClass('active');
    });

    searchInput.on('input', function () {
        clearTimeout(debounceTimer);
        const query = $(this).val().trim();
        if (query.length < 3) {
            suggestionsList.empty().removeClass('active');
            return;
        }
        debounceTimer = setTimeout(() => {
            suggestionsList.html('<li><ion-icon name="hourglass-outline"></ion-icon><span class="suggestion-text"><span class="main-text text-muted">Szukanie lokalizacji...</span></span></li>').addClass('active');
            const mapCenter = map.getCenter();
            $.getJSON(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=15&lang=default&lat=${mapCenter.lat}&lon=${mapCenter.lng}&location_bias_scale=0.3`, function (data) {
                suggestionsList.empty();
                if (data.features && data.features.length > 0) {
                    let uniqueAddresses = new Set();
                    let resultsCount = 0;
                    data.features.forEach(item => {
                        if (resultsCount >= 5) return;
                        const mainText = formatPhotonAddress(item.properties);
                        const subText = [item.properties.state, item.properties.country].filter(Boolean).join(', ');
                        const uniqueKey = mainText + "|" + subText;
                        if (uniqueAddresses.has(uniqueKey)) return;
                        uniqueAddresses.add(uniqueKey);
                        resultsCount++;

                        const li = $('<li></li>');
                        const icon = $('<ion-icon name="location-outline"></ion-icon>');
                        const suggestionText = $('<span class="suggestion-text"></span>');
                        const mainTextSpan = $('<span class="main-text"></span>').text(mainText);
                        const subTextSpan = $('<span class="sub-text"></span>').text(subText);

                        suggestionText.append(mainTextSpan, subTextSpan);
                        li.append(icon, suggestionText);
                        li.on('click', function () {
                            $('#lat').val(item.geometry.coordinates[1]);
                            $('#lng').val(item.geometry.coordinates[0]);
                            $('#address').val(mainText);
                            searchInput.val(mainText);
                            map.setView([item.geometry.coordinates[1], item.geometry.coordinates[0]], 15);
                            if (currentMarker) map.removeLayer(currentMarker);
                            currentMarker = L.marker([item.geometry.coordinates[1], item.geometry.coordinates[0]]).addTo(map);
                            suggestionsList.removeClass('active');
                        });
                        suggestionsList.append(li);
                    });
                } else suggestionsList.html('<li><ion-icon name="close-circle-outline"></ion-icon><span class="suggestion-text"><span class="main-text" style="color:#ed1c24;">Nie znaleziono wyników</span></span></li>');
            });
        }, 400);
    });

    $('#btn-search').click(function (e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        if (!query) return;
        suggestionsList.removeClass('active');
        $.getJSON(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=1`, function (data) {
            if (data.features && data.features.length > 0) {
                const item = data.features[0];
                const mainText = formatPhotonAddress(item.properties);
                map.setView([item.geometry.coordinates[1], item.geometry.coordinates[0]], 15);
                if (currentMarker) map.removeLayer(currentMarker);
                currentMarker = L.marker([item.geometry.coordinates[1], item.geometry.coordinates[0]]).addTo(map);
                $('#lat').val(item.geometry.coordinates[1]);
                $('#lng').val(item.geometry.coordinates[0]);
                $('#address').val(mainText);
                searchInput.val(mainText);
            } else showNotification("Nie znaleziono lokalizacji.", "error");
        });
    });

    function updateWizard() {
        $('.step-content').removeClass('active').hide();
        $(`#step-${currentStep}`).addClass('active').show();
        $('.step-dot').removeClass('active');
        $(`#dot-${currentStep}`).addClass('active');

        $('#btn-prev').css('visibility', currentStep === 1 ? 'hidden' : 'visible');

        if (currentStep === totalSteps) {
            $('#btn-next').hide();
            $('#btn-save-marker').show();

            $('#summary-name').text($('#m_name').val() || 'Brak nazwy');
            $('#summary-price').text($('#m_price').val() || '0');

            const container = $('#summary-image-container');
            container.empty();

            if (currentSortedIds.length > 0) {
                container.append('<p style="margin: 0 0 8px 0; color: #8c7f77; font-size: 14px; font-weight: 500;"><strong>Docelowa kolejność zdjęć:</strong></p>');

                let wrapper = $('<div style="display: flex; flex-wrap: wrap; padding-bottom: 10px; user-select: none;" ondragstart="return false;"></div>');

                currentSortedIds.forEach((id, index) => {
                    const imgSrc = $(`#image-sortable-list .sortable-thumb[data-id="${id}"] img`).attr('src');
                    if (index === 0) {
                        wrapper.append(`
                            <div style="position: relative; flex-shrink: 0; margin-right: 12px; margin-bottom: 12px; pointer-events: none;">
                                <span style="position: absolute; top: -8px; left: 50%; transform: translateX(-50%); background: #f59e0b; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 10px; z-index: 2; white-space: nowrap;">GŁÓWNE</span>
                                <img src="${imgSrc}" draggable="false" style="height: 90px; width: 135px; border-radius: 8px; border: 2px solid #f59e0b; object-fit: cover; box-shadow: 0 2px 5px rgba(245,158,11,0.3); pointer-events: none; -webkit-user-drag: none;">
                            </div>
                        `);
                    } else {
                        wrapper.append(`
                            <div style="flex-shrink: 0; padding-top: 5px; margin-right: 12px; margin-bottom: 12px; pointer-events: none;">
                                <img src="${imgSrc}" draggable="false" style="height: 80px; width: 120px; border-radius: 8px; border: 1px solid #ede8e1; object-fit: cover; pointer-events: none; -webkit-user-drag: none;">
                            </div>
                        `);
                    }
                });
                container.append(wrapper).show();
            } else {
                container.hide();
            }

            const lat = parseFloat($('#lat').val());
            const lng = parseFloat($('#lng').val());
            setTimeout(() => {
                if (!summaryMap) {
                    summaryMap = L.map('summary-map-container', {
                        zoomControl: false,
                        dragging: false,
                        scrollWheelZoom: false
                    }).setView([lat, lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(summaryMap);
                    summaryMarker = L.marker([lat, lng]).addTo(summaryMap);
                } else {
                    summaryMap.setView([lat, lng], 15);
                    summaryMarker.setLatLng([lat, lng]);
                    summaryMap.invalidateSize();
                }
            }, 50);

        } else {
            $('#btn-next').show();
            $('#btn-save-marker').hide();
        }

        if (currentStep === 1) setTimeout(() => {
            map.invalidateSize();
        }, 50);
        window.scrollTo({ top: $('.wizard-container').offset().top - 20, behavior: 'smooth' });
    }

    $('#btn-next').click(function () {
        if (currentStep === 1 && (!$('#lat').val() || !$('#lng').val())) return showNotification("Proszę wybrać lokalizację.", "error");
        if (currentStep === 2 && !$('#m_name').val().trim()) return showNotification("Proszę podać nazwę.", "error");
        if (currentStep === 3 && (!$('#m_date_start').val() || !$('#m_price').val())) return showNotification("Proszę podać datę i cenę.", "error");

        if (currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        }
    });

    $('#btn-prev').click(function () {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    $('#btn-save-marker').click(function () {
        $(this).prop('disabled', true).text('Zapisywanie...');

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('csrf_token', csrfToken);
        formData.append('name', $('#m_name').val());
        formData.append('description', $('#m_desc').val());
        formData.append('lat', $('#lat').val());
        formData.append('lng', $('#lng').val());
        formData.append('price', $('#m_price').val());
        formData.append('date_from', $('#m_date_start').val());
        formData.append('address', $('#address').val());

        currentSortedIds.forEach(id => {
            formData.append('image[]', uploadedFilesMap.get(id));
        });

        $.ajax({
            url: '/include/apiMap.php', type: 'POST', data: formData, processData: false, contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    showNotification("Zapisano pomyślnie!", "success");
                    setTimeout(() => {
                        window.location.href = '/oferta/' + response.id;
                    }, 1500);
                } else {
                    showNotification(response.message || "Błąd zapisu.", "error");
                    $('#btn-save-marker').prop('disabled', false).text('Zapisz ogłoszenie');
                }
            },
            error: function (xhr) {
                showNotification(xhr.responseJSON?.message || "Błąd serwera.", "error");
                $('#btn-save-marker').prop('disabled', false).text('Zapisz ogłoszenie');
            }
        });
    });
});