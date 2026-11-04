<?php

require_once dirname(__DIR__, 2) . '/include/config.php';
require_once dirname(__DIR__, 2) . '/class/Map.php';
$map = new Map($conn);

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$priceMin = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$priceMax = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// --- LOGIKA PAGINACJI ---
$limit = 20; // Max ofert na stronę
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Pobieranie odpowiedniej "paczki" danych z bazy
$totalOffers = $map->getTotalMarkersCount($searchQuery, $priceMin, $priceMax, $dateFilter);
$totalPages = ceil($totalOffers / $limit);
$offers = $map->getMarkers($searchQuery, $priceMin, $priceMax, $dateFilter, null, $limit, $offset);

// --- ULUBIONE ---
$userId = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$userFavorites = $userId > 0 ? $map->getUserFavorites($userId) : [];

require_once dirname(__DIR__, 2) . '/views/header.php';
?>

    <div class="offers">
        <div class="search-container">
            <div class="search-box">
                <h2 class="search-title">Wyszukaj ogłoszenia</h2>
                <form method="GET" action="/oferty" class="search-form">

                    <div class="main-search">
                        <div class="input-wrapper">
                            <input
                                    type="text"
                                    name="search"
                                    placeholder="Wpisz miasto lub ulicę..."
                                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                            >
                            <?php if (!empty($searchQuery)): ?>
                                <a href="/oferty?price_min=<?php echo urlencode($priceMin); ?>&price_max=<?php echo urlencode($priceMax); ?>&date_filter=<?php echo urlencode($dateFilter); ?>"
                                   class="clear-icon" aria-label="Wyczyść tekst">&#10005;</a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary">Szukaj</button>
                    </div>

                    <div class="filters">
                        <div class="filter-group">
                            <label>Cena (zł)</label>
                            <div class="range-inputs">
                                <input type="number" name="price_min" placeholder="Od" min="0"
                                       value="<?php echo htmlspecialchars($priceMin); ?>">
                                <span class="separator">-</span>
                                <input type="number" name="price_max" placeholder="Do" min="0"
                                       value="<?php echo htmlspecialchars($priceMax); ?>">
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Dostępne od</label>
                            <input type="date" name="date_filter" value="<?php echo htmlspecialchars($dateFilter); ?>">
                        </div>

                        <?php if (!empty($searchQuery) || !empty($priceMin) || !empty($priceMax) || !empty($dateFilter)): ?>
                            <div class="filter-group clear-all-group">
                                <label>&nbsp;</label>
                                <a href="/oferty" class="reset-filters">Resetuj filtry</a>
                            </div>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>

        <div class="offers-section">
            <h2 class="section-title">
                <?php echo (!empty($searchQuery) || !empty($priceMin) || !empty($dateFilter)) ? 'Znalezione oferty' : 'Oferty'; ?>
                <span class="results-count">(<?php echo $totalOffers; ?>)</span>
            </h2>

            <div class="listings-container">
                <?php if (count($offers) > 0): ?>
                    <?php foreach ($offers as $offer): ?>
                        <a href="/oferta/<?= $offer['id'] ?>" class="listing-card">

                            <div class="listing-image">
                                <?php
                                $images = !empty($offer['image_path']) ? json_decode($offer['image_path'], true) : [];
                                if (empty($images)) {
                                    $images = ['/assets/img/dom-i-mieszkanie.jpeg'];
                                }
                                ?>

                                <div class="image-carousel"
                                     data-images='<?php echo htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8'); ?>'
                                     data-current="0">
                                    <img src="<?php echo htmlspecialchars($images[0]); ?>" class="carousel-img"
                                         alt="<?php echo htmlspecialchars($offer['name']); ?>">

                                    <?php if (count($images) > 1): ?>
                                        <button class="carousel-arrow prev" aria-label="Poprzednie zdjęcie">&#10094;
                                        </button>
                                        <button class="carousel-arrow next" aria-label="Następne zdjęcie">&#10095;
                                        </button>
                                        <div class="carousel-indicators">
                                            <?php foreach ($images as $idx => $img): ?>
                                                <span class="indicator <?php echo $idx === 0 ? 'active' : ''; ?>"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="listing-content">
                                <div class="listing-info">
                                    <h2 class="listing-title"><?php echo htmlspecialchars($offer['name']); ?></h2>
                                    <div class="listing-meta">
                                        <p class="address-date">
                                            <span class="address-text"><?php echo htmlspecialchars($offer['address'] ?? 'Brak adresu'); ?></span>
                                            - <?php echo date('d.m.Y', strtotime($offer['date_from'])); ?>
                                        </p>
                                        <p class="listing-details">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4 4H20V20H4V4Z" stroke="currentColor" stroke-width="2"
                                                      stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M4 12H20" stroke="currentColor" stroke-width="2"
                                                      stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 4V20" stroke="currentColor" stroke-width="2"
                                                      stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span><?php echo htmlspecialchars(mb_strimwidth($offer['description'], 0, 40, '...')); ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="listing-actions">
                                    <div class="price-box">
                                        <span class="price"><?php echo htmlspecialchars($offer['price']); ?> zł</span>
                                    </div>

                                    <?php $isLiked = in_array($offer['id'], $userFavorites); ?>
                                    <button class="favorite-btn <?php echo $isLiked ? 'active' : ''; ?>"
                                            data-id="<?php echo $offer['id']; ?>" aria-label="Dodaj do ulubionych">
                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                             fill="<?php echo $isLiked ? 'currentColor' : 'none'; ?>"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                                                  stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>Niestety, nie znaleźliśmy ofert spełniających podane kryteria. Spróbuj zmienić filtry.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <?php
                // Zachowanie obecnych filtrów w linkach paginacji
                $queryParams = $_GET;
                unset($queryParams['page']); // Usuwamy starą stronę
                $queryString = http_build_query($queryParams);
                $baseUrl = "/oferty?" . ($queryString ? $queryString . "&" : "");
                ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($page - 1); ?>" class="page-link prev-next">&#10094;
                            Wstecz</a>
                    <?php else: ?>
                        <span class="page-link prev-next disabled">&#10094; Wstecz</span>
                    <?php endif; ?>

                    <div class="page-numbers">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="<?php echo $baseUrl . 'page=' . $i; ?>"
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($page + 1); ?>" class="page-link prev-next">Dalej
                            &#10095;</a>
                    <?php else: ?>
                        <span class="page-link prev-next disabled">Dalej &#10095;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        $(document).ready(function () {

            // --- OBSŁUGA KARUZELI ZDJĘĆ ---
            $('.carousel-arrow').on('click', function (e) {
                e.preventDefault(); // Blokuje przejście do oferty po kliknięciu strzałki!
                e.stopPropagation();

                let $carousel = $(this).closest('.image-carousel');
                let images = JSON.parse($carousel.attr('data-images'));
                let currentIndex = parseInt($carousel.attr('data-current'));
                let isNext = $(this).hasClass('next');

                if (isNext) {
                    currentIndex = (currentIndex + 1) % images.length;
                } else {
                    currentIndex = (currentIndex - 1 + images.length) % images.length;
                }

                $carousel.attr('data-current', currentIndex);
                $carousel.find('.carousel-img').attr('src', images[currentIndex]);

                $carousel.find('.indicator').removeClass('active');
                $carousel.find('.indicator').eq(currentIndex).addClass('active');
            });

            // --- OBSŁUGA ULUBIONYCH ---
            const isUserLoggedIn = <?php echo $userId > 0 ? 'true' : 'false'; ?>;

            $('.favorite-btn').on('click', function (e) {
                e.preventDefault(); // Zatrzymuje link karty ogłoszenia
                e.stopPropagation();

                if (!isUserLoggedIn) {
                    window.location.href = '/logowanie';
                    return;
                }
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                let $btn = $(this);
                let markerId = $btn.data('id');
                let $svg = $btn.find('svg');

                $.ajax({
                    url: '/include/toggle-favorite.php',
                    type: 'POST',
                    data: {
                        marker_id: markerId,
                        csrf_token: csrfToken
                    },
                    success: function (response) {
                        if (response.status === 'added') {
                            $btn.addClass('active');
                            $svg.attr('fill', 'currentColor');
                        } else if (response.status === 'removed') {
                            $btn.removeClass('active');
                            $svg.attr('fill', 'none');
                        } else if (response.status === 'unauthorized') {
                            window.location.href = '/logowanie';
                        }
                    }
                });
            });
        });
    </script>

<?php
require_once dirname(__DIR__, 2) . '/views/footer.php';
?>