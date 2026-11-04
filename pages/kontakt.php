<?php
$title = "Kontakt - AdMarket";
require_once dirname(__DIR__) . '/include/config.php';
require_once dirname(__DIR__) . '/views/header.php';
require_once dirname(__DIR__) . '/class/Map.php';

$mapApp = new Map($conn);
$settings = $mapApp->getSettings();

// Zabezpieczamy wartości domyślne na wypadek pustej bazy
$logoPath = $settings['logo_path'] ?? '/assets/img/logo.svg';
$companyName = $settings['company_name'] ?? 'Nazwa Firmy';
$companySubtitle = $settings['company_subtitle'] ?? 'Podtytuł';
$phone1 = $settings['contact_phone1'] ?? '';
$phone2 = $settings['contact_phone2'] ?? '';
$email = $settings['contact_email'] ?? '';
$address = $settings['contact_address'] ?? '';
$mapIframe = $settings['map_iframe'] ?? '';

// Godziny
$weekOpen = $settings['hours_week_open'] ?? '09:00';
$weekClose = $settings['hours_week_close'] ?? '17:00';
$satOpen = $settings['hours_sat_open'] ?? '10:00';
$satClose = $settings['hours_sat_close'] ?? '12:00';
?>

    <div class="contact-page-wrapper">
        <div class="container">

            <div class="contact-grid">
                <!-- LEWA KOLUMNA: Informacje -->
                <div class="contact-info-panel">
                    <div class="company-header">
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="company-logo">
                        <div class="company-details">
                            <h2><?php echo htmlspecialchars($companyName); ?></h2>
                            <p class="subtitle"><?php echo nl2br(htmlspecialchars($companySubtitle, ENT_QUOTES, 'UTF-8')); ?></p>

                            <div class="stars-rating">
                                <?php for($i=0; $i<5; $i++): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                <?php endfor; ?>
                                <span class="rating-text">4.8/5</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3>Kontakt</h3>
                        <ul class="contact-list">
                            <?php if (!empty($phone1)): ?>
                                <li>
                                    <svg viewBox="0 0 384 512"><path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H288c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64H64zm80 432h64c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16s7.2-16 16-16z"></path></svg>
                                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $phone1)); ?>"><?php echo htmlspecialchars($phone1); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($phone2)): ?>
                                <li>
                                    <svg viewBox="0 0 512 512"><path fill="currentColor" d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"></path></svg>
                                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $phone2)); ?>"><?php echo htmlspecialchars($phone2); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($email)): ?>
                                <li>
                                    <svg viewBox="0 0 512 512"><path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"></path></svg>
                                    <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="info-section">
                        <h3>Adres</h3>
                        <p><?php echo htmlspecialchars($address); ?></p>
                    </div>

                    <div class="info-section">
                        <?php
                        date_default_timezone_set('Europe/Warsaw');
                        $now = new DateTime();
                        $day = $now->format('N');

                        // Dynamiczne generowanie statusu z Bazy Danych
                        $open_hours = [
                            1 => [$weekOpen, $weekClose], 2 => [$weekOpen, $weekClose],
                            3 => [$weekOpen, $weekClose], 4 => [$weekOpen, $weekClose],
                            5 => [$weekOpen, $weekClose], 6 => [$satOpen, $satClose], 7 => null
                        ];

                        function isOpen($day, $now, $hours) {
                            if (!isset($hours[$day]) || $hours[$day] === null || $hours[$day][0] == '' || $hours[$day][1] == '') return false;
                            [$open, $close] = $hours[$day];
                            return $now >= DateTime::createFromFormat('H:i', $open) && $now <= DateTime::createFromFormat('H:i', $close);
                        }

                        $is_open = isOpen($day, $now, $open_hours);
                        $status_class = $is_open ? 'status-open' : 'status-closed';
                        $status_text = $is_open ? 'czynne' : 'nieczynne';
                        ?>
                        <h3>Godziny Otwarcia</h3>
                        <ul class="hours-list">
                            <li><span>Pon - Pt:</span> <strong><?php echo htmlspecialchars($weekOpen, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($weekClose, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                            <li><span>Sobota:</span> <strong><?php echo htmlspecialchars($satOpen, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($satClose, ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        </ul>
                        <div class="current-status <?php echo $status_class; ?>">
                            <span class="dot"></span>
                            Biuro jest obecnie <?php echo $status_text; ?>
                        </div>
                    </div>
                </div>

                <!-- PRAWA KOLUMNA: Formularz -->
                <div class="contact-form-panel">
                    <h2>Skontaktuj się z nami!</h2>

                    <form id="kontakt_form" action="" method="POST" class="elegant-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="inputbox">
                            <input name="name" id="name" placeholder=" " required type="text">
                            <label for="name">Imię i nazwisko</label>
                        </div>

                        <div class="inputbox">
                            <input name="email" id="email" placeholder=" " required type="email">
                            <label for="email">Adres e-mail</label>
                            <span class="help-text">Służy wyłącznie do udzielenia odpowiedzi.</span>
                        </div>

                        <div class="inputbox">
                            <input name="title" id="title" placeholder=" " required type="text">
                            <label for="title">Tytuł</label>
                        </div>

                        <div class="inputbox textarea-box">
                            <textarea id="message" name="message" placeholder=" " required></textarea>
                            <label for="message">Twoja wiadomość</label>
                        </div>

                        <button type="submit" class="btn-submit">
                            <svg viewBox="0 0 512 512"><path fill="currentColor" d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z"></path></svg>
                            Wyślij wiadomość
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($mapIframe)): ?>
                <!-- SEKCJA MAPY -->
                <div class="map-section">
                    <h2>Zapraszamy do naszego biura</h2>
                    <div class="map-container">
                        <?php echo $mapIframe; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php require_once dirname(__DIR__) . '/views/footer.php'; ?>