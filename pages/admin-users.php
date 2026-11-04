<?php
require_once dirname(__DIR__) . '/include/config.php';

require_once dirname(__DIR__) . '/class/Map.php';

$map = new Map($conn);

if (empty($_SESSION['uid']) || !$map->isAdmin($_SESSION['uid'])) {
    header('Location: /');
    exit;
}

$isSuperAdmin = $map->isSuperAdmin($_SESSION['uid']);

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$totalUsers = $map->getTotalAdminUsersCount();
$totalPages = ceil($totalUsers / $limit);
$offset = ($page - 1) * $limit;

$pagedUsers = $map->getAdminUsers($limit, $offset);

require_once dirname(__DIR__) . '/views/header.php';
?>

    <div class="admin-section">
        <div class="main-con">
            <div class="admin-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px; margin-bottom: 1.5rem;">
                <div>
                    <h1>Panel Administratora</h1>
                    <p>Zarządzaj użytkownikami w systemie (Suma: <?php echo $totalUsers; ?>).</p>
                </div>

                <div class="admin-search-wrapper" style="position: relative; width: 100%; max-width: 350px;">
                    <ion-icon name="search-outline" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #86868b; font-size: 1.2rem;"></ion-icon>
                    <input type="text" id="admin-search-input" placeholder="Szukaj po ID, danych lub e-mailu..."
                           style="width: 100%; padding: 12px 15px 12px 42px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background-color: #f5f5f7; font-size: 0.95rem; outline: none; transition: all 0.2s;">
                </div>
            </div>

            <div class="admin-tabs">
                <a href="/admin">Ogłoszenia</a>
                <a href="/admin/users" class="active">Użytkownicy</a>
                <?php if ($isSuperAdmin): ?>
                    <a href="/admin/settings">Ustawienia strony</a>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <table id="admin-offers-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dane Użytkownika</th>
                        <th>Status / Rola</th>
                        <th style="text-align: right;">Akcja (Tylko SuperAdmin)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pagedUsers)): ?>
                        <tr class="no-results-row">
                            <td colspan="4" style="text-align: center; color: #86868b; padding: 3rem;">Brak użytkowników.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagedUsers as $user): ?>
                            <tr class="offer-row" data-href="/uzytkownik/<?php echo htmlspecialchars($user['id']); ?>">
                                <td style="color: #86868b;" class="searchable">#<?php echo htmlspecialchars($user['id']); ?></td>
                                <td class="searchable">
                                    <div class="user-info">
                                        <strong><?php echo htmlspecialchars(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')) ?: 'Brak Danych'); ?></strong>
                                        <span><?php echo htmlspecialchars($user['email']); ?> | <?php echo htmlspecialchars($user['phone'] ?? 'Brak Tel'); ?></span>
                                    </div>
                                </td>
                                <td class="searchable">
                                    <div class="user-info">
                                        <?php if ($user['isadmin'] == 2): ?>
                                            <strong style="color: #0071e3;">Główny Administrator</strong>
                                        <?php elseif ($user['isadmin'] == 1): ?>
                                            <strong style="color: #28a745;">Administrator</strong>
                                        <?php else: ?>
                                            <strong style="color: #86868b;">Użytkownik</strong>
                                        <?php endif; ?>

                                        <?php if ($user['status'] == 2): ?>
                                            <span style="color: #ff3b30; font-weight: 600;">Zablokowany</span>
                                        <?php else: ?>
                                            <span style="color: #34c759;">Aktywny</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right;" class="action-cell">
                                    <?php if ($isSuperAdmin): ?>
                                        <?php if ($user['isadmin'] == 2): ?>
                                            <span style="color: #86868b; font-size: 0.85rem;">(Brak akcji)</span>
                                        <?php else: ?>
                                            <div style="display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap;">
                                                <?php if ($user['isadmin'] == 1): ?>
                                                    <button class="btn-admin-action revoke" onclick="triggerAction(event, 'revoke_admin', <?php echo $user['id']; ?>)">Zabierz Admina</button>
                                                <?php else: ?>
                                                    <button class="btn-admin-action give" onclick="triggerAction(event, 'give_admin', <?php echo $user['id']; ?>)">Nadaj Admina</button>
                                                <?php endif; ?>

                                                <?php if ($user['status'] == 2): ?>
                                                    <button class="btn-admin-action give" onclick="triggerAction(event, 'unban', <?php echo $user['id']; ?>)">Odblokuj</button>
                                                <?php else: ?>
                                                    <button class="btn-admin-action ban" onclick="triggerAction(event, 'ban', <?php echo $user['id']; ?>)">Zablokuj</button>
                                                <?php endif; ?>

                                                <button class="btn-admin-action ban" onclick="triggerAction(event, 'delete_user', <?php echo $user['id']; ?>)"><ion-icon name="trash"></ion-icon></button>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #86868b; font-size: 0.85rem;">Wymagany SuperAdmin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="js-no-results" style="display: none;"><td colspan="4" style="text-align: center; color: #86868b; padding: 3rem;">Brak wyników wyszukiwania.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="?page=<?php echo ($page - 1); ?>" class="page-link prev-next">&#10094; Wstecz</a><?php else: ?><span class="page-link prev-next disabled">&#10094; Wstecz</span><?php endif; ?>
                        <div class="page-numbers">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?><span class="page-dots">...</span><?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <?php if ($page < $totalPages): ?><a href="?page=<?php echo ($page + 1); ?>" class="page-link prev-next">Dalej &#10095;</a><?php else: ?><span class="page-link prev-next disabled">Dalej &#10095;</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="delete-modal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-icon"><ion-icon name="shield-half-outline"></ion-icon></div>
            <h3 id="modal-title">Potwierdzenie akcji</h3>
            <p id="modal-desc">Wykonać operację?</p>
            <div class="modal-actions">
                <button class="btn-cancel" id="btn-cancel-action">Anuluj</button>
                <button class="btn-confirm-delete" id="btn-confirm-action">Tak, wykonaj</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let isDragging = false;
            let mouseDownPos = { x: 0, y: 0 };
            $('.offer-row').on('mousedown', function(e) { isDragging = false; mouseDownPos = { x: e.pageX, y: e.pageY }; });
            $('.offer-row').on('mousemove', function(e) { if (mouseDownPos.x === 0 && mouseDownPos.y === 0) return; if (Math.abs(e.pageX - mouseDownPos.x) > 3 || Math.abs(e.pageY - mouseDownPos.y) > 3) isDragging = true; });
            $('.offer-row').on('mouseup', function(e) {
                const targetUrl = $(this).data('href');
                const isActionCell = $(e.target).closest('.action-cell').length > 0;
                const textSelected = window.getSelection().toString().length > 0;
                mouseDownPos = { x: 0, y: 0 };
                if (isActionCell || textSelected || isDragging) return;
                if (targetUrl) window.location.href = targetUrl;
            });

            $('#admin-search-input').on('input', function() {
                const query = $(this).val().toLowerCase();
                let visibleCount = 0;
                $('.offer-row').each(function() {
                    let rowText = '';
                    $(this).find('.searchable').each(function() { rowText += $(this).text().toLowerCase() + ' '; });
                    if (rowText.indexOf(query) !== -1) { $(this).show(); visibleCount++; } else { $(this).hide(); }
                });
                if (visibleCount === 0 && query.length > 0) $('#js-no-results').show(); else $('#js-no-results').hide();
            });

            let currentAction = null;
            let currentTargetId = null;
            const modal = $('#delete-modal');

            window.triggerAction = function(e, action, id) {
                e.stopPropagation();
                currentAction = action;
                currentTargetId = id;

                let title = ''; let desc = ''; let btnColor = '#0071e3';

                if (action === 'ban') { title = 'Zablokuj konto'; desc = 'Czy na pewno chcesz zablokować tego użytkownika? Zostanie wylogowany.'; btnColor = '#ff3b30'; }
                if (action === 'unban') { title = 'Odblokuj konto'; desc = 'Czy na pewno chcesz odblokować to konto? Użytkownik odzyska dostęp.'; btnColor = '#34c759'; }
                if (action === 'give_admin') { title = 'Nadaj Administratora'; desc = 'Użytkownik zyska dostęp do panelu i będzie mógł edytować wszystkie ogłoszenia.'; btnColor = '#0071e3'; }
                if (action === 'revoke_admin') { title = 'Zabierz Administratora'; desc = 'Użytkownik straci uprawnienia zarządzania stroną.'; btnColor = '#ff9500'; }
                if (action === 'delete_user') { title = 'Całkowite usunięcie'; desc = 'UWAGA! Spowoduje to nieodwracalne usunięcie konta oraz WSZYSTKICH ogłoszeń przypisanych do tego użytkownika. Kontynuować?'; btnColor = '#ff3b30'; }

                $('#modal-title').text(title);
                $('#modal-desc').text(desc);
                $('#btn-confirm-action').css('background-color', btnColor);
                modal.addClass('show');
            }

            $('#btn-cancel-action').click(function(e) { e.preventDefault(); modal.removeClass('show'); });
            modal.click(function(e) { if ($(e.target).is(modal)) modal.removeClass('show'); });

            $('#btn-confirm-action').click(function(e) {
                e.preventDefault();
                if (!currentTargetId || !currentAction) return;
                const btn = $(this);
                btn.prop('disabled', true).text('Przetwarzanie...');

                const validCsrfToken = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";

                $.ajax({
                    url: '/include/apiMap.php',
                    type: 'POST',
                    data: {
                        action: currentAction,
                        target_id: currentTargetId,
                        csrf_token: validCsrfToken
                    },
                    success: function(response) {
                        if (response.status === 'success') { window.location.reload(); } else {
                            alert(response.message || "Błąd wykonania akcji.");
                            btn.prop('disabled', false).text('Tak, wykonaj');
                        }
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || "Krytyczny błąd serwera. Czy jesteś SuperAdminem?");
                        btn.prop('disabled', false).text('Tak, wykonaj');
                    }
                });
            });
        });
    </script>

<?php require_once dirname(__DIR__) . '/views/footer.php'; ?>