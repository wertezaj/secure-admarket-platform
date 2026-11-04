<?php
require_once dirname(__DIR__, 2) . '/include/config.php';

// 1. Zabezpieczenie przed niezalogowanymi
if (empty($_SESSION['uid'])) {
    header('Location: /logowanie');
    exit;
}


require_once dirname(__DIR__, 2) . '/class/Map.php';

$map = new Map($conn);
$offerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['uid'];

// 2. Twarde zabezpieczenie: czy ogłoszenie istnieje i czy zalogowany jest właścicielem
if (!$map->checkMarkerOwnership($offerId, $userId)) {
    header('Location: /moje-oferty?error=unauthorized');
    exit;
}

$offer = $map->getMarkerById($offerId);
require_once dirname(__DIR__, 2) . '/views/header.php';

?>
    <input type="hidden" id="offer_id" value="<?php echo htmlspecialchars($offer['id']); ?>">
    <input type="hidden" id="address" name="address" value="<?php echo htmlspecialchars($offer['address']); ?>">

    <div class="wizard-container">
        <div class="wizard-header">
            <h2>Edytuj swoje ogłoszenie</h2>
            <div class="steps-indicator">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
                <div class="step-dot" id="dot-4"></div>
            </div>
        </div>

        <div class="wizard-body">
            <div class="step-content active" id="step-1">
                <h3>01. Weryfikacja lokalizacji</h3>
                <p class="subtitle">Jeśli nośnik zmienił adres, możesz go tutaj skorygować.</p>

                <div class="location-search-wrapper">
                    <div class="search-input-container">
                        <input type="text" id="location-search" class="form-control" value="<?php echo htmlspecialchars($offer['address']); ?>" placeholder="Wpisz miasto lub ulicę..." autocomplete="off">
                        <ul id="suggestions-list" class="suggestions-dropdown"></ul>
                    </div>
                    <button class="btn-search" id="btn-search">Szukaj</button>
                </div>

                <div class="map-wrapper">
                    <div id="edit-map" style="height: 400px; width: 100%; border-radius: 8px;"></div>
                </div>

                <input type="hidden" id="lat" value="<?php echo floatval($offer['lat']); ?>">
                <input type="hidden" id="lng" value="<?php echo floatval($offer['lng']); ?>">
            </div>

            <div class="step-content" id="step-2">
                <h3>02. Specyfikacja nośnika</h3>

                <div class="form-group">
                    <label>Nazwa nośnika</label>
                    <input type="text" id="m_name" class="form-control" value="<?php echo htmlspecialchars($offer['name']); ?>">
                </div>

                <div class="form-group">
                    <label>Typ nośnika i opis</label>
                    <textarea id="m_desc" class="form-control" rows="4"><?php echo htmlspecialchars($offer['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Zdjęcia nośnika (Maksymalnie 5)</label>

                    <div class="file-upload-wrapper">
                        <input type="file" id="m_image" class="form-control" accept="image/jpeg, image/png, image/webp" multiple>
                        <label for="m_image" class="file-label">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <span>Wybierz zdjęcia</span>
                        </label>
                        <span class="file-info">Nie wybrano plików</span>
                    </div>

                    <div id="sortable-images-container" style="display:none; background-color: #faf8f5; border: 1px solid #ede8e1; padding: 15px; margin-top: 15px; border-radius: 8px;">
                        <p style="font-size: 13px; color: #8c7f77; margin-bottom: 12px; font-weight: 500;"><ion-icon name="move-outline" style="vertical-align: middle;"></ion-icon> Przeciągnij zdjęcia, aby zmienić ich kolejność. Złota ramka oznacza <strong>główne zdjęcie profilowe</strong>.</p>
                        <div id="image-sortable-list" style="display: block;"></div>
                    </div>
                </div>
            </div>

            <div class="step-content" id="step-3">
                <h3>03. Dostępność i cena</h3>

                <div class="form-group">
                    <label>Dostępny od</label>
                    <input type="date" id="m_date_start" class="form-control" value="<?php echo htmlspecialchars($offer['date_from']); ?>">
                </div>

                <div class="form-group">
                    <label>Cena za miesiąc (PLN)</label>
                    <input type="number" step="0.01" id="m_price" class="form-control" value="<?php echo htmlspecialchars($offer['price']); ?>">
                </div>
            </div>

            <div class="step-content" id="step-4">
                <h3>04. Podsumowanie zmian</h3>
                <div style="background:#faf8f5; padding: 24px; border-radius: 12px; border: 1px solid #ede8e1;">
                    <p style="margin: 0 0 10px 0;"><strong>Nazwa:</strong> <span id="summary-name">---</span></p>
                    <p style="margin: 0 0 15px 0;"><strong>Cena:</strong> <span id="summary-price">---</span> PLN</p>

                    <div id="summary-image-container" style="display: none; margin-bottom: 15px;"></div>

                    <p style="margin: 0 0 8px 0; color: #8c7f77; font-size: 14px; font-weight: 500;"><strong>Lokalizacja:</strong></p>
                    <div id="summary-map-container" style="height: 180px; width: 100%; border-radius: 8px; border: 1px solid #ede8e1; z-index: 1;"></div>
                </div>
            </div>
        </div>

        <div class="wizard-footer">
            <button class="btn-delete-offer" id="btn-show-delete">
                <ion-icon name="trash-outline"></ion-icon> Usuń ogłoszenie
            </button>

            <!-- Przyciski nawigacji (Prawa strona) ujęte w nową klasę -->
            <div class="wizard-nav-buttons">
                <button class="btn btn-prev" id="btn-prev" style="visibility: hidden;">Wróć</button>
                <button class="btn btn-next" id="btn-next">Dalej</button>
                <button class="btn btn-save" id="btn-save-edit" style="display:none;">Zapisz zmiany</button>
            </div>
        </div>

        <!-- MODAL USUWANIA -->
        <div id="delete-modal" class="custom-modal-overlay">
            <div class="custom-modal">
                <div class="modal-icon">
                    <ion-icon name="trash-outline"></ion-icon>
                </div>
                <h3>Czy na pewno usunąć?</h3>
                <p>Tej operacji nie można cofnąć. Twoje ogłoszenie zniknie z platformy na zawsze.</p>
                <div class="modal-actions">
                    <button class="btn-cancel" id="btn-cancel-delete">Anuluj</button>
                    <button class="btn-confirm-delete" id="btn-confirm-delete">Tak, usuń</button>
                </div>
            </div>
        </div>
    </div> <!-- Zamknięcie .wizard-container -->
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        const existingOfferImages = <?php echo json_encode(is_array($offer['images']) ? $offer['images'] : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="/assets/js/edit-map.js"></script>

<?php require_once dirname(__DIR__, 2) . '/views/footer.php'; ?>