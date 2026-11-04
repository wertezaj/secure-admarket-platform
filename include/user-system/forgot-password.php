<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__, 2) . '/views/header.php';
?>
    <style> body{padding: 0;} header, footer { display: none !important; } main { overflow: hidden; } </style>
    <div class="form-users-con">
        <div class="form-users">
            <form action="/include/user-system/forgot-password.inc.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="title">
                    <h2>Zresetuj hasło</h2>
                    <p style="font-size: 14px; text-align: center;">Podaj e-mail, a wyślemy Ci link do resetu.</p>
                </div>
                <div class="inputbox">
                    <ion-icon name="mail-outline"></ion-icon>
                    <input type="email" name="email" id="email" required placeholder=" ">
                    <label for="email">E-mail</label>
                </div>
                <button type="submit" name="reset-request-submit">Wyślij link</button>
                <div class="register">
                    <p>Pamiętasz hasło? <a href="/logowanie">Zaloguj się</a></p>
                </div>
            </form>
            <?php
            if (isset($_GET["reset"]) && $_GET["reset"] == "success") {
                echo "<p class=\"php-success\">Sprawdź swoją skrzynkę odbiorczą (i folder SPAM)!</p>";
            }
            ?>
        </div>
    </div>
<?php require_once dirname(__DIR__, 2) . '/views/footer.php'; ?>