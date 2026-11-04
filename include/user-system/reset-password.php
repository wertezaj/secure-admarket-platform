<?php
require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__, 2) . '/views/header.php';
?>
    <style> header, footer { display: none !important; } main { overflow: hidden; } </style>
    <div class="form-users-con">
        <div class="form-users">
            <?php
            $selector = $_GET["selector"] ?? '';
            $validator = $_GET["validator"] ?? '';

            if (empty($selector) || empty($validator) || ctype_xdigit($selector) === false || ctype_xdigit($validator) === false) {
                echo "<p class='php-error'>Nieprawidłowe zapytanie.</p>";
            } else {
                ?>
                <form action="/include/user-system/reset-password.inc.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                    <input type="hidden" name="validator" value="<?php echo htmlspecialchars($validator); ?>">

                    <div class="title"><h2>Ustaw nowe hasło</h2></div>

                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd" id="pwd" required placeholder=" ">
                        <label for="pwd">Nowe hasło</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd-repeat" id="pwd-repeat" required placeholder=" ">
                        <label for="pwd-repeat">Powtórz nowe hasło</label>
                    </div>

                    <button type="submit" name="reset-password-submit">Zmień hasło</button>
                </form>
                <?php
            }
            if (isset($_GET["error"])) {
                if ($_GET["error"] == "pwdnotsame") echo "<p class='php-error'>Hasła nie pasują.</p>";
                if ($_GET["error"] == "weakpassword") echo "<p class='php-error'>Hasło jest za słabe! (Min. 8 znaków, duża, mała litera, cyfra i znak specjalny).</p>";
                if ($_GET["error"] == "tokenexpired") echo "<p class='php-error'>Link wygasł lub jest nieprawidłowy.</p>";
            }
            ?>
        </div>
    </div>
<?php require_once dirname(__DIR__, 2) . '/views/footer.php'; ?>