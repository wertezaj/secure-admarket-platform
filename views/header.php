<?php
require_once dirname(__DIR__) . '/include/config.php';

?>
<!doctype html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Skorzystaj z profesjonalnych ubezpieczeń dla firm. Oferujemy dopasowane rozwiązania chroniące majątek, odpowiedzialność i pracowników Twojego biznesu. Sprawdź naszą ofertę!">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

    <!-- ZMIANA: Favicon również podąża za nowym logiem, o ile ma odpowiedni format -->
    <link rel="icon" href="<?php echo htmlspecialchars($GLOBALS['logo_path']); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($GLOBALS['logo_path']); ?>" type="image/x-icon">

    <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
    <script src="/assets/bootstrap/code.jquery.com_jquery-3.7.0.min.js"></script>
    <script src="/assets/bootstrap/popper.js@1.16.1_dist_umd_popper.min.js"></script>
    <script src="/assets/bootstrap/bootstrap@4.6.2_dist_js_bootstrap.bundle.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/assets/bootstrap/flickity.css" media="screen">

    <link rel="stylesheet" href="/assets/css/module/steps.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/text-center.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/tiles-3.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/list.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/loginregister.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/add-ad.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/offers.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/single-ad.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/user-page.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/kontakt.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/admin.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/module/header-footer.css?v=1.49">
    <link rel="stylesheet" href="/assets/css/main.css?v=1.49">
    <title>
        <?php
        if (isset($title)) {
            echo htmlspecialchars($title);
        } else {
            echo htmlspecialchars($GLOBALS['page_name']);
        }
        ?>
    </title>
</head>

<body>
<?php
$transparent = isset($transparent) ? $transparent : '';
?>
<header class="<?= $transparent ?>">
    <div class="header-container">
        <div class="left">
            <div class="logo">
                <a href="/">
                    <img src="<?php echo htmlspecialchars($GLOBALS['logo_path']); ?>" alt="logo">

                    <span><?php echo htmlspecialchars($GLOBALS['page_name']); ?></span>
                </a>
            </div>
        </div>
        <div class="menu-overlay"></div>
        <div class="center">
            <ul>
                <li><a href="/oferty">Oferty</a></li>
                <li><a href="/kontakt">Kontakt</a></li>
            </ul>

            <div class="mobile-actions">
                <a href="/logowanie/" class="mobile-btn-empty">Twoje konto</a>
                <a href="/dodaj-ogloszenie/" class="mobile-btn">Dodaj ogłoszenie</a>
            </div>
        </div>

        <div class="right">
            <div class="right-btn-empty">
                <a href="/logowanie/">Twoje konto</a>
            </div>
            <div class="right-btn">
                <a href="/dodaj-ogloszenie/">Dodaj ogłoszenie</a>
            </div>
            <button class="burger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const burger = document.querySelector('.burger');
        const center = document.querySelector('.center');
        const header = document.querySelector('header');
        const overlay = document.querySelector('.menu-overlay');

        function toggleMenu() {
            burger.classList.toggle('active');
            center.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = center.classList.contains('active') ? 'hidden' : '';
        }

        burger.addEventListener('click', toggleMenu);
        if(overlay) overlay.addEventListener('click', toggleMenu);

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    if (window.scrollY > 10) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    });
</script>

<main>