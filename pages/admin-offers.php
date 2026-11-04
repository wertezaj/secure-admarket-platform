<?php


require_once dirname(__DIR__) . '/include/config.php';
require_once dirname(__DIR__) . '/class/Map.php';

$map = new Map($conn);

// Twarde zabezpieczenie: Wyrzucamy na stronę główną każdego, kto nie jest zalogowany lub nie ma isadmin=1
if (empty($_SESSION['uid']) || !$map->isAdmin($_SESSION['uid'])) {
    header('Location: /');
    exit;
}


// --- LOGIKA PAGINACJI ---
// --- LOGIKA PAGINACJI NA POZIOMIE BAZY DANYCH ---
$limit = 20; // Ofert na stronę dla Admina
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$totalOffers = $map->getTotalAdminMarkersCount();
$totalPages = ceil($totalOffers / $limit);
$offset = ($page - 1) * $limit;

// Pobieramy wyłącznie rekordy dla bieżącej strony (ochrona przed wyczerpaniem RAM)
$pagedOffers = $map->getAdminMarkers($limit, $offset);

require_once dirname(__DIR__) . '/views/header.php';
?>

    <div class="admin-section">
        <div class="main-con">
            <div class="admin-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px; margin-bottom: 2.5rem;">
                <div>
                    <h1>Panel Administratora</h1>
                    <p>Zarządzaj wszystkimi ogłoszeniami w systemie (Suma: <?php echo $totalOffers; ?>).</p>
                </div>

                <!-- NOWE: WYSZUKIWARKA (Real-time Filter) -->
                <div class="admin-search-wrapper" style="position: relative; width: 100%; max-width: 350px;">
                    <ion-icon name="search-outline" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #86868b; font-size: 1.2rem;"></ion-icon>
                    <input type="text" id="admin-search-input" placeholder="Szukaj po ID, nazwie, osobie lub adresie..."
                           style="width: 100%; padding: 12px 15px 12px 42px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background-color: #f5f5f7; font-size: 0.95rem; outline: none; transition: all 0.2s;">
                </div>
            </div>
            <div class="admin-tabs">
                <a href="/admin" class="active">Ogłoszenia</a>
                <a href="/admin/users">Użytkownicy</a>
                <?php if ($map->isSuperAdmin($_SESSION['uid'])): ?>
                    <a href="/admin/settings">Ustawienia strony</a>
                <?php endif; ?>
            </div>


            <div class="admin-card">
                <table id="admin-offers-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Osoba i Kontakt</th>
                        <th>Nazwa oferty</th>
                        <th>Adres (Lokalizacja)</th>
                        <th style="text-align: right;">Akcja</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pagedOffers)): ?>
                        <tr class="no-results-row">
                            <td colspan="5" style="text-align: center; color: #86868b; padding: 3rem;">Brak ogłoszeń na tej stronie.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagedOffers as $offer): ?>
                            <!-- ZMIANA: Usunięto sztywne 'onclick' z atrybutu elementu tr -->
                            <tr class="offer-row" data-href="/oferta/<?php echo htmlspecialchars($offer['id']); ?>">
                                <td style="color: #86868b;" class="searchable">#<?php echo htmlspecialchars($offer['id']); ?></td>
                                <td class="searchable">
                                    <div class="user-info">
                                        <strong><?php echo htmlspecialchars(trim(($offer['user_name'] ?? '') . ' ' . ($offer['user_surname'] ?? '')) ?: 'Nieznany'); ?></strong>
                                        <span><?php echo htmlspecialchars($offer['user_email'] ?? 'Brak emaila'); ?></span>
                                    </div>
                                </td>
                                <td class="searchable"><strong><?php echo htmlspecialchars($offer['offer_name']); ?></strong></td>
                                <td class="searchable">
                                    <div class="user-info">
                                        <span style="color: #1d1d1f;"><ion-icon name="location-outline" style="vertical-align: middle;"></ion-icon> <?php echo htmlspecialchars($offer['address']); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right;" class="action-cell">
                                    <button class="btn-delete-offer" data-id="<?php echo htmlspecialchars($offer['id']); ?>">
                                        <ion-icon name="trash-outline"></ion-icon> Usuń
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Pusty wiersz weryfikacyjny dla JavaScript, jeśli wyszukiwarka nic nie znajdzie -->
                        <tr id="js-no-results" style="display: none;">
                            <td colspan="5" style="text-align: center; color: #86868b; padding: 3rem;">Brak wyników wyszukiwania.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- NOWE: STRONICOWANIE DLA ADMINA -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination" style="margin-top: 2rem; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 1.5rem;">
                        <?php if ($page > 1): ?>
                            <a href="/admin?page=<?php echo ($page - 1); ?>" class="page-link prev-next">&#10094; Wstecz</a>
                        <?php else: ?>
                            <span class="page-link prev-next disabled">&#10094; Wstecz</span>
                        <?php endif; ?>

                        <div class="page-numbers">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="/admin?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a href="/admin?page=<?php echo ($page + 1); ?>" class="page-link prev-next">Dalej &#10095;</a>
                        <?php else: ?>
                            <span class="page-link prev-next disabled">Dalej &#10095;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL USUWANIA (Apple Style) -->
    <div id="delete-modal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-icon">
                <ion-icon name="warning-outline"></ion-icon>
            </div>
            <h3>Potwierdź usunięcie</h3>
            <p>Usuwasz ogłoszenie jako administrator. Zniknie ono całkowicie z systemu wraz ze zdjęciami. Kontynuować?</p>
            <div class="modal-actions">
                <button class="btn-cancel" id="btn-cancel-delete">Anuluj</button>
                <button class="btn-confirm-delete" id="btn-confirm-delete">Tak, usuń trwale</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // ==========================================
            // LOGIKA INTELIGENTNEGO KLIKNIĘCIA (Ochrona zaznaczania tekstu)
            // ==========================================
            let isDragging = false;
            let mouseDownPos = { x: 0, y: 0 };

            // Kiedy zaczynamy klikać myszką, rejestrujemy pozycję
            $('.offer-row').on('mousedown', function(e) {
                isDragging = false;
                mouseDownPos = { x: e.pageX, y: e.pageY };
            });

            // Śledzimy czy podczas trzymania przycisku przesuwa się mysz (czyli zaznacza tekst)
            $('.offer-row').on('mousemove', function(e) {
                if (mouseDownPos.x === 0 && mouseDownPos.y === 0) return;

                // Zabezpieczenie minimalnego progu błędu dłoni (3 piksele)
                if (Math.abs(e.pageX - mouseDownPos.x) > 3 || Math.abs(e.pageY - mouseDownPos.y) > 3) {
                    isDragging = true;
                }
            });

            // Ostateczne puszczenie przycisku (odpalenie przeniesienia)
            $('.offer-row').on('mouseup', function(e) {
                const targetUrl = $(this).data('href');

                // Zabezpieczenia anulujące link
                const isActionCell = $(e.target).closest('.action-cell').length > 0;
                const textSelected = window.getSelection().toString().length > 0;

                // Reset pozycji
                mouseDownPos = { x: 0, y: 0 };

                // Jeśli kliknięto przycisk usunięcia, zaznaczono tekst, lub myszka mocno się przesunęła - nic nie rób
                if (isActionCell || textSelected || isDragging) {
                    return;
                }

                // Tylko dla czystego pojedynczego kliknięcia przenosimy
                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            });

            // ==========================================
            // LOGIKA WYSZUKIWARKI REAL-TIME
            // ==========================================
            $('#admin-search-input').on('input', function() {
                const query = $(this).val().toLowerCase();
                let visibleCount = 0;

                $('.offer-row').each(function() {
                    let rowText = '';

                    // Pobieranie treści ze wszystkich zdefiniowanych kolumn oprócz przycisków
                    $(this).find('.searchable').each(function() {
                        rowText += $(this).text().toLowerCase() + ' ';
                    });

                    if (rowText.indexOf(query) !== -1) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                // Obsługa komunikatu o braku ofert
                if (visibleCount === 0 && query.length > 0) {
                    $('#js-no-results').show();
                } else {
                    $('#js-no-results').hide();
                }
            });

            // Design inputu przy aktywności (focus)
            $('#admin-search-input').on('focus', function() {
                $(this).css({
                    'background-color': '#ffffff',
                    'border-color': '#0071e3',
                    'box-shadow': '0 0 0 4px rgba(0, 113, 227, 0.15)'
                });
            }).on('blur', function() {
                $(this).css({
                    'background-color': '#f5f5f7',
                    'border-color': 'rgba(0,0,0,0.1)',
                    'box-shadow': 'none'
                });
            });

            // ==========================================
            // LOGIKA USUWANIA MODALU
            // ==========================================
            let deleteOfferId = null;
            const modal = $('#delete-modal');

            // Aktywacja z podpięciem ID
            $('.btn-delete-offer').click(function(e) {
                e.preventDefault();
                e.stopPropagation();
                deleteOfferId = $(this).data('id');
                modal.addClass('show');
            });

            $('#btn-cancel-delete').click(function(e) {
                e.preventDefault();
                modal.removeClass('show');
                deleteOfferId = null;
            });

            modal.click(function(e) {
                if ($(e.target).is(modal)) {
                    modal.removeClass('show');
                    deleteOfferId = null;
                }
            });

            $('#btn-confirm-delete').click(function(e) {
                e.preventDefault();
                if (!deleteOfferId) return;

                const btn = $(this);
                btn.prop('disabled', true).text('Usuwanie...');

                const validCsrfToken = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";

                $.ajax({
                    url: '/include/apiMap.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        offer_id: deleteOfferId,
                        csrf_token: validCsrfToken
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            window.location.reload();
                        } else {
                            alert(response.message || "Błąd zapisu.");
                            btn.prop('disabled', false).text('Tak, usuń trwale');
                        }
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || "Krytyczny błąd serwera.");
                        btn.prop('disabled', false).text('Tak, usuń trwale');
                    }
                });
            });
        });
    </script>

<?php require_once dirname(__DIR__) . '/views/footer.php'; ?>