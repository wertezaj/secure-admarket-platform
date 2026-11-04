$(document).ready(function () {
    let currentStep = 1;
    const totalSteps = 4;
    let summaryMap = null;
    let summaryMarker = null;

    let uploadedFilesMap = new Map();
    let currentSortedIds = [];
    let isUsingExisting = false;
    let safeExistingImages = [];

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

    let latVal = $('#lat').val();
    let lngVal = $('#lng').val();
    if (latVal) latVal = latVal.toString().replace(',', '.');
    if (lngVal) lngVal = lngVal.toString().replace(',', '.');
    let initLat = parseFloat(latVal);
    let initLng = parseFloat(lngVal);
    if (isNaN(initLat) || isNaN(initLng)) {
        initLat = 52.0688;
        initLng = 19.4797;
    }

    const map = L.map('edit-map').setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let currentMarker = L.marker([initLat, initLng]).addTo(map);
    setTimeout(() => {
        map.invalidateSize();
    }, 500);

    function formatShortAddress(addressObj) {
        const parts = [(addressObj.road || addressObj.pedestrian || '') + (addressObj.house_number ? ' ' + addressObj.house_number : '').trim(), addressObj.city || addressObj.town || addressObj.village || ''].filter(part => part.length > 0);
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

    function initSortable() {
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
    }

    function updateMainBadge() {
        $('#image-sortable-list .sortable-thumb').css({'border-color': 'transparent', 'box-shadow': 'none'});
        $('#image-sortable-list .sortable-thumb .badge-main').hide();
        const first = $('#image-sortable-list .sortable-thumb').first();
        first.css({'border-color': '#f59e0b', 'box-shadow': '0 4px 6px rgba(245, 158, 11, 0.3)'});
        first.find('.badge-main').show();
    }

    if (typeof existingOfferImages !== 'undefined' && existingOfferImages !== null) {
        safeExistingImages = Array.isArray(existingOfferImages) ? existingOfferImages : [];
    }

    if (safeExistingImages.length > 0) {
        isUsingExisting = true;
        $('#sortable-images-container').show();
        $('.file-info').text('Wczytano ' + safeExistingImages.length + ' zapisanych zdjęć');

        safeExistingImages.forEach((url, index) => {
            const id = 'ext_' + index;
            currentSortedIds.push(id);
            const thumbHtml = `
                <div class="sortable-thumb" data-id="${id}" style="position: relative; display: inline-block; vertical-align: top; cursor: grab; width: 110px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid transparent; margin: 0 12px 12px 0;">
                    <div class="badge-main" style="display: none; position: absolute; top: 0; left: 0; right: 0; background: #f59e0b; color: white; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; z-index: 2;">GŁÓWNE</div>
                    <img src="${url}" draggable="false" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none; user-select: none; -webkit-user-drag: none;">
                </div>
            `;
            $('#image-sortable-list').append(thumbHtml);
        });
        updateMainBadge();
        initSortable();
    }

    function readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = e => reject(e);
            reader.readAsDataURL(file);
        });
    }

    $('#m_image').on('change', async function (e) {
        const files = e.target.files;
        if (files.length === 0) return;

        if (files.length > 5) {
            showNotification("Możesz dodać maksymalnie 5 zdjęć.", "error");
            $(this).val('');
            return;
        }

        isUsingExisting = false;
        $('.file-info').text(files.length + ' nowych plików wybranych');
        $('#image-sortable-list').empty();
        uploadedFilesMap.clear();
        currentSortedIds = [];
        $('#sortable-images-container').show();

        for (let i = 0; i < files.length; i++) {
            const id = 'img_' + Math.random().toString(36).substr(2, 9);
            uploadedFilesMap.set(id, files[i]);
            currentSortedIds.push(id);
            const dataUrl = await readFileAsDataURL(files[i]);

            const thumbHtml = `
                <div class="sortable-thumb" data-id="${id}" style="position: relative; display: inline-block; vertical-align: top; cursor: grab; width: 110px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid transparent; margin: 0 12px 12px 0;">
                    <div class="badge-main" style="display: none; position: absolute; top: 0; left: 0; right: 0; background: #f59e0b; color: white; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; z-index: 2;">GŁÓWNE</div>
                    <img src="${dataUrl}" draggable="false" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none; user-select: none; -webkit-user-drag: none;">
                </div>
            `;
            $('#image-sortable-list').append(thumbHtml);
        }
        updateMainBadge();
        initSortable();
    });

    let debounceTimer;
    const searchInput = $('#location-search');
    const suggestionsList = $('#suggestions-list');

    searchInput.on('input', function () {
        clearTimeout(debounceTimer);
        const query = $(this).val().trim();
        if (query.length < 3) {
            suggestionsList.empty().removeClass('active');
            return;
        }
        debounceTimer = setTimeout(() => {
            suggestionsList.html('<li><ion-icon name="hourglass-outline"></ion-icon><span class="suggestion-text"><span class="main-text text-muted">Szukanie...</span></span></li>').addClass('active');
            $.getJSON(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5&lang=default`, function (data) {
                suggestionsList.empty();
                if (data.features && data.features.length > 0) {
                    data.features.forEach(item => {
                        const props = item.properties;
                        const mainText = [props.name, props.street].filter(Boolean).join(', ');
                        const li = $('<li></li>');
                        const icon = $('<ion-icon name="location-outline"></ion-icon>');
                        const suggestionText = $('<span class="suggestion-text"></span>');
                        const mainTextSpan = $('<span class="main-text"></span>').text(mainText);

                        suggestionText.append(mainTextSpan);
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
                } else {
                    suggestionsList.html('<li><span class="suggestion-text">Brak wyników</span></li>');
                }
            });
        }, 400);
    });

    $('#btn-search').click(function (e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        if (!query) return;
        $.getJSON(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=1`, function (data) {
            if (data.features && data.features.length > 0) {
                const item = data.features[0];
                map.setView([item.geometry.coordinates[1], item.geometry.coordinates[0]], 15);
                if (currentMarker) map.removeLayer(currentMarker);
                currentMarker = L.marker([item.geometry.coordinates[1], item.geometry.coordinates[0]]).addTo(map);
                $('#lat').val(item.geometry.coordinates[1]);
                $('#lng').val(item.geometry.coordinates[0]);
                $('#address').val(item.properties.name || "Wybrana lokalizacja");
            }
        });
    });

    searchInput.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (suggestionsList.hasClass('active') && suggestionsList.children().length > 0) suggestionsList.children().first().trigger('click');
            else $('#btn-search').click();
        }
    });

    function updateWizard() {
        $('.step-content').removeClass('active').hide();
        $(`#step-${currentStep}`).addClass('active').show();
        $('.step-dot').removeClass('active');
        $(`#dot-${currentStep}`).addClass('active');

        $('#btn-prev').css('visibility', currentStep === 1 ? 'hidden' : 'visible');

        if (currentStep === totalSteps) {
            $('#btn-next').hide();
            $('#btn-save-edit').show();

            $('#summary-name').text($('#m_name').val() || 'Brak nazwy');
            $('#summary-price').text($('#m_price').val() || '0');

            const container = $('#summary-image-container');
            container.empty();

            if (currentSortedIds.length > 0) {
                const introText = isUsingExisting ? 'Zaktualizowana kolejność Twoich zdjęć:' : 'Nowe zdjęcia zastąpią stare:';
                container.append(`<p style="margin: 0 0 8px 0; color: #8c7f77; font-size: 14px; font-weight: 500;"><strong>${introText}</strong></p>`);

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
                container.append('<p style="margin: 0 0 8px 0; color: #8c7f77; font-size: 14px; font-weight: 500;"><strong>Zdjęcia pozostaną bez zmian.</strong></p>');
                container.show();
            }

            const sumLatVal = $('#lat').val();
            const sumLngVal = $('#lng').val();
            let lat = parseFloat(sumLatVal ? sumLatVal.toString().replace(',', '.') : 52.0688);
            let lng = parseFloat(sumLngVal ? sumLngVal.toString().replace(',', '.') : 19.4797);

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
            $('#btn-save-edit').hide();
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

    $('#btn-save-edit').click(function () {
        $(this).prop('disabled', true).text('Zapisywanie...');

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('csrf_token', csrfToken);
        formData.append('offer_id', $('#offer_id').val());
        formData.append('name', $('#m_name').val());
        formData.append('description', $('#m_desc').val());
        formData.append('lat', $('#lat').val());
        formData.append('lng', $('#lng').val());
        formData.append('price', $('#m_price').val());
        formData.append('date_from', $('#m_date_start').val());
        formData.append('address', $('#address').val());

        if (isUsingExisting) {
            currentSortedIds.forEach(id => {
                const idx = parseInt(id.split('_')[1]);
                formData.append('existing_images[]', safeExistingImages[idx]);
            });
        } else {
            currentSortedIds.forEach(id => {
                formData.append('image[]', uploadedFilesMap.get(id));
            });
        }

        $.ajax({
            url: '/include/apiMap.php', type: 'POST', data: formData, processData: false, contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    showNotification("Zaktualizowano pomyślnie!", "success");
                    setTimeout(() => {
                        window.location.href = '/oferta/' + response.id;
                    }, 1500);
                } else {
                    showNotification(response.message, "error");
                    $('#btn-save-edit').prop('disabled', false).text('Zapisz zmiany');
                }
            },
            error: function (xhr) {
                showNotification(xhr.responseJSON?.message || "Błąd serwera.", "error");
                $('#btn-save-edit').prop('disabled', false).text('Zapisz zmiany');
            }
        });
    });

    const modal = $('#delete-modal');

    $('#btn-show-delete').click(function (e) {
        e.preventDefault();
        modal.addClass('show');
    });

    $('#btn-cancel-delete').click(function (e) {
        e.preventDefault();
        modal.removeClass('show');
    });

    modal.click(function (e) {
        if ($(e.target).is(modal)) {
            modal.removeClass('show');
        }
    });

    $('#btn-confirm-delete').click(function (e) {
        e.preventDefault();
        const btn = $(this);
        btn.prop('disabled', true).text('Usuwanie...');

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '/include/apiMap.php',
            type: 'POST',
            data: {
                action: 'delete',
                offer_id: $('#offer_id').val(),
                csrf_token: csrfToken
            },
            success: function (response) {
                if (response.status === 'success') {
                    showNotification("Ogłoszenie zostało usunięte.", "success");
                    modal.removeClass('show');
                    setTimeout(() => {
                        window.location.href = '/oferty';
                    }, 1500);
                } else {
                    showNotification(response.message, "error");
                    btn.prop('disabled', false).text('Tak, usuń');
                }
            },
            error: function (xhr) {
                showNotification(xhr.responseJSON?.message || "Błąd serwera. Spróbuj ponownie.", "error");
                btn.prop('disabled', false).text('Tak, usuń');
            }
        });
    });
});