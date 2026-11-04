<?php
require_once __DIR__ . '/include/config.php';

require_once __DIR__ . '/class/Map.php';

$map = new Map($conn);

$latestOffers = $map->getMarkers('', null, null, '', null, 8, 0);

$userId = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$userFavorites = $userId > 0 ? $map->getUserFavorites($userId) : [];

// POBRANIE DANYCH KONTAKTOWYCH Z GLOBALNYCH USTAWIEŃ
$contactPhone = $GLOBALS['settings']['contact_phone1'] ?? '';
$contactEmail = $GLOBALS['settings']['contact_email'] ?? '';

require_once __DIR__ . '/views/header.php';

?>
    <div class="img-full">
        <div class="main-con">
            <div class="main-img-text">
                <div class="img-bg">
                    <div class="text-content">

                        <div class="hero-badge">
                            <svg version="1.1" id="fi_709701" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 428.16 428.16" xml:space="preserve">
                            <g>
                                <path d="M393.8,110.208c-0.512-11.264-0.512-22.016-0.512-32.768c0-8.704-6.656-15.36-15.36-15.36
                c-64,0-112.64-18.432-153.088-57.856c-6.144-5.632-15.36-5.632-21.504,0C162.888,43.648,114.248,62.08,50.248,62.08
                c-8.704,0-15.36,6.656-15.36,15.36c0,10.752,0,21.504-0.512,32.768c-2.048,107.52-5.12,254.976,174.592,316.928l5.12,1.024
                l5.12-1.024C398.408,365.184,395.848,218.24,393.8,110.208z M201.8,259.2c-3.072,2.56-6.656,4.096-10.752,4.096h-0.512
                c-4.096,0-8.192-2.048-10.752-5.12l-47.616-52.736l23.04-20.48l37.376,41.472l82.944-78.848l20.992,22.528L201.8,259.2z">
                                </path>
                            </g>
                        </svg>
                            <span>Giełda Nośników Reklamowych</span>
                        </div>

                        <h1>Najlepsze billboardy <span class="hide-mobile">w Polsce.</span><br>w jednym miejscu.</h1>

                        <p class="hero-description">
                            <?= htmlspecialchars($GLOBALS['page_name']) ?> łączy twórców kampanii z właścicielami przestrzeni. Znajdź idealne miejsce na swoją reklamę lub zacznij zarabiać, wynajmując własny nośnik. Bez ukrytych prowizji.
                        </p>

                        <div class="btn-group">
                            <a href="/oferty" class="btn-primary">Szukam miejsca na reklamę</a>
                            <a href="/dodaj-ogloszenie" class="btn-secondary">Wynajmij swój billboard</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ... reszta kodu (sekcja ofert, kroki) pozostaje bez zmian ... -->
    <div class="main-con">
        <div class="offers-section">
            <h2 class="section-title">Ostatnio dodane nośniki reklamowe</h2>

            <div class="listings-container">
                <?php if (count($latestOffers) > 0): ?>
                    <?php foreach ($latestOffers as $offer): ?>
                        <a href="/oferta/<?= $offer['id'] ?>" class="listing-card">
                            <div class="listing-image">
                                <?php
                                $displayImage = '/assets/img/dom-i-mieszkanie.jpeg'; // Domyślne zdjęcie
                                if (!empty($offer['images']) && is_array($offer['images'])) {
                                    $displayImage = $offer['images'][0];
                                } elseif (!empty($offer['image_path']) && !str_starts_with(trim($offer['image_path']), '[')) {
                                    $displayImage = $offer['image_path'];
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="Wynajem billboardu: <?php echo htmlspecialchars($offer['name']); ?>" style="object-fit: cover; width: 100%; height: 100%;">
                            </div>
                            <div class="listing-content">
                                <div class="listing-info">
                                    <h2 class="listing-title"><?php echo htmlspecialchars($offer['name']); ?></h2>
                                    <div class="listing-meta">
                                        <p class="address-date">
                                            <span class="address-text"><?php echo htmlspecialchars($offer['address'] ?? 'Cała Polska'); ?></span>
                                            - <?php echo date('d.m.Y', strtotime($offer['date_from'])); ?>
                                        </p>
                                        <p class="listing-details">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4 4H20V20H4V4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span><?php echo htmlspecialchars(mb_strimwidth($offer['description'], 0, 45, '...')); ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="listing-actions">
                                    <div class="price-box">
                                        <span class="price"><?php echo htmlspecialchars($offer['price']); ?> zł</span>
                                        <span class="price-label">/ miesiąc</span>
                                    </div>

                                    <?php $isLiked = in_array($offer['id'], $userFavorites); ?>
                                    <button class="favorite-btn <?php echo $isLiked ? 'active' : ''; ?>" data-id="<?php echo $offer['id']; ?>" aria-label="Dodaj do ulubionych">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $isLiked ? 'currentColor' : 'none'; ?>" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>

                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #777;">
                        <p>Baza nośników jest w trakcie aktualizacji. Bądź pierwszą osobą, która doda billboard na wynajem!</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="steps-section main-con">
        <h2 class="section-title">Jak to działa?</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-icon">
                    <ion-icon name="search-outline"></ion-icon>
                </div>
                <h3>1. Znajdź lub dodaj</h3>
                <p>Wyszukaj idealny nośnik na mapie, lub bezpłatnie dodaj własną przestrzeń reklamową w kilka minut.</p>
            </div>
            <div class="step-card">
                <div class="step-icon">
                    <ion-icon name="chatbubbles-outline"></ion-icon>
                </div>
                <h3>2. Bezpośredni kontakt</h3>
                <p>Skontaktuj się bezpośrednio z właścicielem lub reklamodawcą. Bez ukrytych prowizji i pośredników.</p>
            </div>
            <div class="step-card">
                <div class="step-icon">
                    <ion-icon name="rocket-outline"></ion-icon>
                </div>
                <h3>3. Start kampanii</h3>
                <p>Sfinalizuj ustalenia, podpisz umowę i uruchom kampanię znacznie szybciej niż przez tradycyjne agencje.</p>
            </div>
        </div>
    </div>

    <div class="b2b-section main-con">
        <div class="b2b-box">
            <div class="b2b-text">
                <h2>Planujesz kampanię ogólnopolską?</h2>
                <p>Nie masz czasu na kontakt z dziesiątkami właścicieli? Zostaw to naszym ekspertom. Dobierzemy zoptymalizowaną paczkę nośników dopasowaną do Twojego budżetu i grupy docelowej.</p>
                <ul class="b2b-benefits">
                    <li><ion-icon name="checkmark-circle"></ion-icon> Dedykowany opiekun kampanii</li>
                    <li><ion-icon name="checkmark-circle"></ion-icon> Zasięg w całej Polsce (Billboardy, LED)</li>
                    <li><ion-icon name="checkmark-circle"></ion-icon> Szybka, bezpłatna wycena</li>
                </ul>
            </div>
            <div class="b2b-form-card">
                <h3>Skontaktuj się z nami</h3>
                <p class="contact-desc">
                    Nasi eksperci są do Twojej dyspozycji. Zadzwoń lub napisz bezpośrednio, a my zajmiemy się całą resztą.
                </p>

                <div class="contact-actions">
                    <!-- ZMIANA: Dynamiczne wczytywanie z bazy -->
                    <?php if (!empty($contactPhone)): ?>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)); ?>" class="btn-primary">
                            <ion-icon name="call"></ion-icon> <?php echo htmlspecialchars($contactPhone); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contactEmail)): ?>
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="btn-secondary">
                            <ion-icon name="mail"></ion-icon> <?php echo htmlspecialchars($contactEmail); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="img-text first">
        <div class="left">
            <div class="text">
                <h2>Wynajem billboardów i reklama zewnętrzna (OOH)</h2>
                <p>
                    Szukasz idealnego miejsca na swoją kampanię? Nasza giełda nośników reklamowych oferuje bezpośredni dostęp do tysięcy <strong>tablic reklamowych, siatek mesh</strong> oraz nowoczesnych <strong>ekranów LED</strong> w całej Polsce.
                </p>
                <p>
                    Wybierając bezpośredni wynajem powierzchni reklamowej od właścicieli, omijasz prowizje agencji. Skuteczna reklama OOH (Out of Home) jeszcze nigdy nie była tak prosta, przejrzysta i opłacalna.
                </p>
            </div>
        </div>
        <div class="right">
            <div class="img">
                <img loading="lazy" src="/assets/img/obsluga.webp" alt="Wynajem powierzchni reklamowych, billboardy i tablice">
            </div>
        </div>
    </div>

    <div class="img-text">
        <div class="left">
            <div class="img">
                <img loading="lazy" src="/assets/img/obsluga.webp" alt="Zarabiaj na wynajmie przestrzeni pod reklamę wielkoformatową">
            </div>
        </div>
        <div class="right">
            <div class="text">
                <h2>Zarabiaj na wynajmie powierzchni reklamowej</h2>
                <p>
                    Masz działkę przy ruchliwej trasie lub pustą ścianę w centrum miasta? Dodaj bezpłatne ogłoszenie i zacznij zarabiać na wynajmie tablic reklamowych.
                </p>
                <ul>
                    <li>
                        <strong>Darmowa ekspozycja</strong> – Twoje nośniki reklamowe trafiają do ogólnopolskiej bazy marek i reklamodawców.
                    </li>
                    <li>
                        <strong>Bez ukrytych prowizji</strong> – Ty ustalasz cenę. Kontakt z wynajmującym odbywa się bezpośrednio, bez pośredników.
                    </li>
                    <li>
                        <strong>Wysoka widoczność</strong> – Codziennie łączymy właścicieli przestrzeni z firmami szukającymi miejsc na kampanie wielkoformatowe.
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const isUserLoggedIn = <?php echo $userId > 0 ? 'true' : 'false'; ?>;
            const favoriteBtns = document.querySelectorAll('.favorite-btn');

            favoriteBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (navigator.vibrate) navigator.vibrate(10);

                    if (!isUserLoggedIn) {
                        window.location.href = '/logowanie';
                        return;
                    }

                    let markerId = this.getAttribute('data-id');
                    let svg = this.querySelector('svg');
                    let currentBtn = this;

                    $.ajax({
                        url: '/include/toggle-favorite.php',
                        type: 'POST',
                        data: {
                            marker_id: markerId,
                            csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                        },
                        success: function(response) {
                            if (response.status === 'added') {
                                currentBtn.classList.add('active');
                                svg.setAttribute('fill', 'currentColor');
                                if (navigator.vibrate) navigator.vibrate([15, 50, 15]);
                            } else if (response.status === 'removed') {
                                currentBtn.classList.remove('active');
                                svg.setAttribute('fill', 'none');
                            } else if (response.status === 'unauthorized') {
                                window.location.href = '/logowanie';
                            }
                        }
                    });
                });
            });
        });
    </script>

<?php
require_once __DIR__ . '/views/footer.php';
?>
