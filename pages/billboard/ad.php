<?php
require_once dirname(__DIR__, 2) . '/include/config.php';
require_once dirname(__DIR__, 2) . '/class/Map.php';

$map = new Map($conn);
$offerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$offer = $map->getMarkerById($offerId);

if (!$offer) {
    header("Location: /oferty");
    exit;
}

// Sprawdzenie zdjęć
$images = !empty($offer['images']) && is_array($offer['images']) ? $offer['images'] : ['/assets/img/dom-i-mieszkanie.jpeg'];

// NOWE: Sprawdzenie, czy oglądający jest właścicielem ogłoszenia
$isOwner = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['uid']) && $_SESSION['uid'] == $offer['user_id']) {
    $isOwner = true;
}

require_once dirname(__DIR__, 2) . '/views/header.php';
?>
    <div class="single-offer-page">
        <div class="container">

            <div class="offer-header">
                <a href="/oferty" class="back-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Wróć do wyników wyszukiwania
                </a>
            </div>

            <div class="offer-grid">

                <div class="offer-main-content">
                    <div class="offer-gallery" id="offer-gallery">
                        <div class="slider-wrapper">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     alt="Zdjęcie nośnika <?php echo htmlspecialchars($offer['name']); ?>"
                                     class="slider-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                     data-index="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($images) > 1): ?>
                            <button class="slider-btn prev-btn" aria-label="Poprzednie zdjęcie">&#10094;</button>
                            <button class="slider-btn next-btn" aria-label="Następne zdjęcie">&#10095;</button>
                            <div class="slider-dots">
                                <?php foreach ($images as $index => $img): ?>
                                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>"
                                          data-slide="<?php echo $index; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="offer-details-section">
                        <h1 class="offer-title"><?php echo htmlspecialchars($offer['name']); ?></h1>
                        <p class="offer-address">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo htmlspecialchars($offer['address']); ?>
                        </p>

                        <div class="offer-description-box">
                            <h3>Opis nośnika</h3>
                            <p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
                        </div>

                        <div class="offer-map-box">
                            <h3>Dokładna lokalizacja</h3>
                            <div id="single-offer-map"></div>
                        </div>
                    </div>
                </div>

                <div class="offer-sidebar">
                    <div class="booking-card">
                        <div class="booking-price">
                            <span class="amount"><?php echo htmlspecialchars($offer['price']); ?> zł</span>
                            <span class="period">/ miesiąc</span>
                        </div>

                        <div class="booking-info">
                            <div class="info-row">
                                <span class="label">Dostępność:</span>
                                <span class="value date-ready">Od <?php echo date('d.m.Y', strtotime($offer['date_from'])); ?></span>
                            </div>
                        </div>

                        <?php if ($isOwner): ?>
                            <!-- Przycisk edycji dla właściciela -->
                            <a href="/oferta/edycja/<?php echo htmlspecialchars($offer['id']); ?>" class="btn-book"
                               style="background-color: #f59e0b; text-align: center; display: flex;
    justify-content: center;
    align-items: center; text-decoration: none; margin-bottom: 10px;">
                                <svg style="vertical-align: middle; margin-right: 5px; margin-top: -2px;" width="18"
                                     height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edytuj ogłoszenie
                            </a>
                            <p style="text-align: center; font-size: 13px; color: #8c7f77; margin-top: 5px;">
                                Widzisz ten przycisk, ponieważ jesteś autorem ogłoszenia.
                            </p>
                        <?php else: ?>
                            <!-- Standardowy przycisk rezerwacji dla kupujących -->
                            <button class="btn-book">Zarezerwuj teraz</button>
                        <?php endif; ?>

                        <div class="contact-info-section">
                            <h4>Dane kontaktowe</h4>

                            <div class="contact-detail">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><?php echo htmlspecialchars($offer['user_name'] ?? 'Właściciel nośnika'); ?></span>
                            </div>

                            <?php if (!empty($offer['user_phone'])): ?>
                                <div class="contact-detail">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <a href="tel:<?php echo htmlspecialchars($offer['user_phone']); ?>"><?php echo htmlspecialchars($offer['user_phone']); ?></a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($offer['user_email'])): ?>
                                <div class="contact-detail">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <a href="mailto:<?php echo htmlspecialchars($offer['user_email']); ?>"><?php echo htmlspecialchars($offer['user_email']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>


                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // --- LOGIKA MAPY ---
            const lat = <?php echo floatval($offer['lat']); ?>;
            const lng = <?php echo floatval($offer['lng']); ?>;

            const map = L.map('single-offer-map', {
                scrollWheelZoom: false
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const localMarkerIcon = L.icon({
                iconUrl: '/assets/img/marker-icon.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34]
            });

            L.marker([lat, lng], { icon: localMarkerIcon }).addTo(map);

            // --- LOGIKA SLIDERA ZDJĘĆ ---
            const images = document.querySelectorAll('.slider-image');
            const dots = document.querySelectorAll('.dot');
            if (images.length > 1) {
                let currentIndex = 0;
                const totalImages = images.length;

                const updateSlider = (index) => {
                    images.forEach(img => img.classList.remove('active'));
                    dots.forEach(dot => dot.classList.remove('active'));

                    images[index].classList.add('active');
                    dots[index].classList.add('active');
                };

                document.querySelector('.next-btn').addEventListener('click', () => {
                    currentIndex = (currentIndex + 1) % totalImages;
                    updateSlider(currentIndex);
                });

                document.querySelector('.prev-btn').addEventListener('click', () => {
                    currentIndex = (currentIndex - 1 + totalImages) % totalImages;
                    updateSlider(currentIndex);
                });

                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        currentIndex = index;
                        updateSlider(currentIndex);
                    });
                });
            }
        });
    </script>

<?php
require_once dirname(__DIR__, 2) . '/views/footer.php';
?>