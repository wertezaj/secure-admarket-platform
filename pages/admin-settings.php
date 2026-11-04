<?php


require_once dirname(__DIR__) . '/include/config.php';
require_once dirname(__DIR__) . '/class/Map.php';

$map = new Map($conn);

if (empty($_SESSION['uid']) || !$map->isSuperAdmin($_SESSION['uid'])) {
    header('Location: /admin');
    exit;
}

$settings = $map->getSettings();
require_once dirname(__DIR__) . '/views/header.php';

?>

    <div class="admin-section">
        <div class="main-con">
            <div class="admin-header">
                <h1>Ustawienia Główne</h1>
                <p>Zarządzaj informacjami wyświetlanymi na stronie Kontakt.</p>
            </div>

            <div class="admin-tabs">
                <a href="/admin" class="active">Ogłoszenia</a>
                <a href="/admin/users">Użytkownicy</a>
                <a href="/admin/settings">Ustawienia strony</a>
            </div>

            <div class="admin-card" style="max-width: 800px;">
                <form id="settings-form" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">

                    <h3 style="margin: 0; font-size: 1.2rem; padding-bottom: 10px; border-bottom: 1px solid #eee;">Identyfikacja wizualna</h3>

                    <div style="display: flex; gap: 15px; align-items: center; background: #f9f9fb; padding: 15px; border-radius: 12px; border: 1px solid #eee;">
                        <img src="<?php echo htmlspecialchars($settings['logo_path'] ?? '/assets/img/logo.svg'); ?>" style="height: 60px; max-width: 150px; object-fit: contain;">
                        <div style="flex-grow: 1;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Podmień Logo (PNG, JPG, WEBP)</label>
                            <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" style="font-size: 0.9rem; padding: 5px;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Nazwa Firmy / Marki</label>
                            <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Podpis (użyj &lt;br&gt; do nowej linii)</label>
                            <input type="text" name="company_subtitle" value="<?php echo htmlspecialchars($settings['company_subtitle'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                    </div>

                    <h3 style="margin: 15px 0 0 0; font-size: 1.2rem; padding-bottom: 10px; border-bottom: 1px solid #eee;">Dane Kontaktowe</h3>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Telefon 1</label>
                            <input type="text" name="contact_phone1" value="<?php echo htmlspecialchars($settings['contact_phone1'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Telefon 2</label>
                            <input type="text" name="contact_phone2" value="<?php echo htmlspecialchars($settings['contact_phone2'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Adres E-mail</label>
                            <input type="text" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Adres Biura</label>
                            <input type="text" name="contact_address" value="<?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                    </div>

                    <h3 style="margin: 15px 0 0 0; font-size: 1.2rem; padding-bottom: 10px; border-bottom: 1px solid #eee;">Godziny Otwarcia</h3>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 120px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Od (Pon-Pt)</label>
                            <input type="time" name="hours_week_open" value="<?php echo htmlspecialchars($settings['hours_week_open'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 120px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Do (Pon-Pt)</label>
                            <input type="time" name="hours_week_close" value="<?php echo htmlspecialchars($settings['hours_week_close'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 120px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Od (Sobota)</label>
                            <input type="time" name="hours_sat_open" value="<?php echo htmlspecialchars($settings['hours_sat_open'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                        <div style="flex: 1; min-width: 120px;">
                            <label style="display: block; font-size: 0.85rem; color: #86868b; margin-bottom: 5px;">Do (Sobota)</label>
                            <input type="time" name="hours_sat_close" value="<?php echo htmlspecialchars($settings['hours_sat_close'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none;">
                        </div>
                    </div>

                    <h3 style="margin: 15px 0 0 0; font-size: 1.2rem; padding-bottom: 10px; border-bottom: 1px solid #eee;">Mapa Google (Ramka Iframe)</h3>
                    <textarea name="map_iframe" rows="3" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; outline: none; font-family: monospace; font-size: 0.8rem;"><?php echo htmlspecialchars($settings['map_iframe'] ?? ''); ?></textarea>

                    <button type="submit" id="btn-save" style="margin-top: 15px; padding: 12px; background: #0071e3; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;">Zapisz ustawienia strony</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $('#settings-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btn-save');
            btn.prop('disabled', true).text('Zapisywanie...');

            let formData = new FormData(this);
            formData.append('action', 'update_settings');
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            $.ajax({
                url: '/include/apiMap.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        window.location.reload();
                    } else {
                        alert(res.message || "Błąd!");
                        btn.prop('disabled', false).text('Zapisz ustawienia strony');
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || "Błąd serwera.");
                    btn.prop('disabled', false).text('Zapisz ustawienia strony');
                }
            });
        });
    </script>

<?php require_once dirname(__DIR__) . '/views/footer.php'; ?>