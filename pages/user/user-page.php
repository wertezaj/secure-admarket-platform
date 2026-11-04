<?php
require_once dirname(__DIR__, 2) . '/include/config.php';

$target_user_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['uid'] ?? 0);

if ($target_user_id === 0) {
    header('Location: /logowanie');
    exit;
}

$loggedInUserId = $_SESSION['uid'] ?? 0;
$isOwner = ($loggedInUserId === $target_user_id);

require_once dirname(__DIR__, 2) . '/class/Map.php';

$map = new Map($conn);

$isAdmin = false;
if ($loggedInUserId > 0) {
    $isAdmin = $map->isAdmin($loggedInUserId);
}

// 3. Pobranie danych użytkownika Z DODANYM TELEFONEM
$user_query = $conn->prepare("SELECT name, surname, email, phone FROM users WHERE id = ?");
$user_query->bind_param("i", $target_user_id);
$user_query->execute();
$result = $user_query->get_result();

if ($result->num_rows === 0) {
    header('Location: /');
    exit;
}

$userData = $result->fetch_assoc();
$user_query->close();

$imie = $userData['name'] ?? '';
$nazwisko = $userData['surname'] ?? '';
$telefon = $userData['phone'] ?? '';

// 4. Pobieranie ogłoszeń
$myOffers = $map->getMarkers('', null, null, '', $target_user_id);

// 5. Pobieranie prawdziwych ulubionych z nowej metody (widoczne tylko dla właściciela)
$favoriteOffers = [];
if ($isOwner) {
    $favoriteOffers = $map->getFavoriteMarkersDetails($loggedInUserId);
}

require_once dirname(__DIR__, 2) . '/views/header.php';
?>

    <div class="user-profile-page">
        <div class="profile-container">

            <div class="profile-grid">

                <div class="profile-sidebar">
                    <div class="user-card-box">
                        <div class="user-avatar-placeholder">
                            <?php echo !empty($imie) ? htmlspecialchars(mb_strtoupper(mb_substr($imie, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') : 'U'; ?>
                        </div>
                        <h2 class="user-display-name">
                            <?php echo htmlspecialchars($isOwner || $isAdmin ? ($imie . ' ' . $nazwisko) : $imie, ENT_QUOTES, 'UTF-8'); ?>
                        </h2>

                        <?php if ($isOwner || $isAdmin): ?>
                            <p class="user-email"><?php echo htmlspecialchars($userData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if ($isOwner): ?>
                            <form id="form-edit-profile" class="profile-form" method="POST" action="/include/apiUser.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="inputbox">
                                    <input type="text" id="p_imie" name="imie" placeholder=" " value="<?php echo htmlspecialchars($imie, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="p_imie">Imię</label>
                                </div>
                                <div class="inputbox">
                                    <input type="text" id="p_nazwisko" name="nazwisko" placeholder=" " value="<?php echo htmlspecialchars($nazwisko, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="p_nazwisko">Nazwisko</label>
                                </div>
                                <div class="inputbox">
                                    <input type="tel" id="p_phone" name="phone" placeholder=" " value="<?php echo htmlspecialchars($telefon, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="p_phone">Numer telefonu</label>
                                </div>
                                <button type="submit" id="btn-save-profile" class="btn-save-profile">Zapisz zmiany</button>
                            </form>

                            <form action="/include/user-system/logout.inc.php" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn-save-profile btn-logout" style="border: none; width: 100%; cursor: pointer;">
                                    Wyloguj ze swojego konta
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a href="/admin" class="btn-save-profile" style="background-color: #1d1d1f; color: #fff; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 8px; border: none;">
                                <ion-icon name="shield-checkmark-outline" style="font-size: 1.1rem;"></ion-icon> Panel Administratora
                            </a>
                        <?php endif; ?>

                        <div id="profile-alert" class="profile-alert" style="display:none;"></div>
                    </div>
                </div>

                <div class="profile-main-content">

                    <div class="offers-wrapper-section">
                        <h3 class="tabs-title">
                            <?php echo $isOwner ? 'Moje ogłoszenia' : 'Ogłoszenia tego użytkownika'; ?>
                            <span class="count-badge">(<?php echo count($myOffers); ?>)</span>
                        </h3>

                        <div class="profile-listings-container">
                            <?php if (count($myOffers) > 0): ?>
                                <?php foreach ($myOffers as $offer): ?>
                                    <?php
                                    $displayImage = '/assets/img/dom-i-mieszkanie.jpeg';
                                    if (!empty($offer['images']) && is_array($offer['images'])) {
                                        $displayImage = $offer['images'][0];
                                    } elseif (!empty($offer['image_path']) && is_string($offer['image_path'])) {
                                        // Fallback
                                        $displayImage = $offer['image_path'];
                                    }
                                    ?>
                                    <a href="/oferta/<?php echo htmlspecialchars($offer['id']); ?>" class="profile-listing-card">
                                        <div class="listing-image">
                                            <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo htmlspecialchars($offer['name']); ?>">
                                        </div>
                                        <div class="listing-content">
                                            <div class="listing-info">
                                                <h4 class="listing-title"><?php echo htmlspecialchars($offer['name']); ?></h4>
                                                <p class="listing-address"><?php echo htmlspecialchars($offer['address'] ?? 'Brak adresu'); ?></p>
                                            </div>
                                            <div class="listing-actions">
                                                <span class="price"><?php echo htmlspecialchars($offer['price']); ?> zł</span>
                                                <span class="price-label">/ miesiąc</span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-list-box">
                                    <?php if ($isOwner): ?>
                                        <p>Nie dodałeś jeszcze żadnego ogłoszenia.</p>
                                        <a href="/dodaj-ogloszenie" class="btn-inline-add">Dodaj pierwsze ogłoszenie</a>
                                    <?php else: ?>
                                        <p>Ten użytkownik nie posiada obecnie aktywnych ogłoszeń.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isOwner): ?>
                        <div class="offers-wrapper-section" style="margin-top: 40px;">
                            <h3 class="tabs-title">Ulubione ogłoszenia <span class="count-badge">(<?php echo count($favoriteOffers); ?>)</span></h3>

                            <div class="profile-listings-container">
                                <?php if (count($favoriteOffers) > 0): ?>
                                    <?php foreach ($favoriteOffers as $offer): ?>
                                        <a href="/oferta/<?php echo htmlspecialchars($offer['id']); ?>" class="profile-listing-card">
                                            <div class="listing-image">
                                                <img src="<?php echo htmlspecialchars($offer['display_image']); ?>" alt="<?php echo htmlspecialchars($offer['name']); ?>">
                                            </div>
                                            <div class="listing-content">
                                                <div class="listing-info">
                                                    <h4 class="listing-title"><?php echo htmlspecialchars($offer['name']); ?></h4>
                                                    <p class="listing-address"><?php echo htmlspecialchars($offer['address'] ?? 'Brak adresu'); ?></p>
                                                </div>
                                                <div class="listing-actions">
                                                    <span class="price"><?php echo htmlspecialchars($offer['price']); ?> zł</span>
                                                    <span class="price-label">/ miesiąc</span>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-list-box">
                                        <p>Twoja lista ulubionych jest pusta.</p>
                                        <a href="/oferty" class="btn-inline-add">Przeglądaj ogłoszenia</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>


<?php if ($isOwner): ?>
    <script>
        $(document).ready(function() {
            // Skrypt z zabezpieczeniem przycisku i obsługą odpowiedzi JSON
            $('#form-edit-profile').submit(function(e) {
                e.preventDefault();
                const alertBox = $('#profile-alert');
                const btn = $('#btn-save-profile');

                btn.prop('disabled', true).text('Zapisywanie...');
                alertBox.hide();

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        btn.prop('disabled', false).text('Zapisz zmiany');

                        if (response.status === 'success') {
                            alertBox.css({display: 'block', background: '#d4edda', color: '#155724', border: '1px solid #c3e6cb'})
                                .text(response.message);
                            $('.user-display-name').text($('#p_imie').val() + ' ' + $('#p_nazwisko').val());
                        } else {
                            alertBox.css({display: 'block', background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb'})
                                .text(response.message);
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).text('Zapisz zmiany');
                        alertBox.css({display: 'block', background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb'})
                            .text("Wystąpił błąd serwera podczas zapisu danych.");
                    }
                });
            });
        });
    </script>
<?php endif; ?>

<?php
require_once dirname(__DIR__, 2) . '/views/footer.php';