<?php
require_once dirname(__DIR__) . '/config.php';



if (!empty($_SESSION['uid'])) {
    header('Location: /uzytkownik/'.$_SESSION['uid']);
    exit;
}
require_once dirname(__DIR__, 2) . '/views/header.php';
?>
<style>
    body{padding: 0;}
    header,
    footer {
        display: none !important;
    }

    main {
        overflow: hidden;
        margin: 0;
    }
</style>
    <div class="form-users-con">
        <div class="form-users">
            <form action="/include/user-system/login.inc.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="title">
                    <h2>Zaloguj</h2>
                </div>

                <div class="inputbox">
                    <ion-icon name="mail-outline"></ion-icon>
                    <input type="email" name="email" id="email" required placeholder=" ">
                    <label for="email">E-mail</label>
                </div>

                <div class="inputbox">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <ion-icon name="eye-off-outline" class="toggle-password"></ion-icon>
                    <input type="password" name="password" id="password" required placeholder=" ">
                    <label for="password">Hasło</label>
                </div>

                <div class="forgot-password">
                    <a href="/zapomnialem-hasla">Nie pamiętasz hasła?</a>
                </div>

                <button type="submit" name="submit" id="login">Zaloguj</button>

                <div class="register">
                    <p>Jeszcze nie masz konta? <a href="/rejestracja">Stwórz konto</a></p>
                </div>
            </form>

            <?php
            if (isset($_GET["mess"]) && $_GET["mess"] == "emptyinput") {
                echo "<p class=\"php-error\">Uzupełnij wszystkie pola logowania.</p>";
            }
            if (isset($_GET["mess"]) && $_GET["mess"] == "passwordupdated") {
                echo "<p class=\"php-success\">Hasło zresetowane! Możesz się teraz zalogować.</p>";
            }
            if (isset($_GET["mess"]) && $_GET["mess"] == "niepoprawnedane") {
                echo "<p class=\"php-error\">Niepoprawne dane logowania. Jeśli nie pamiętasz hasła, możesz je zresetować.</p>";
            }
            if (isset($_GET["mess"]) && $_GET["mess"] == "stmtfailed") {
                echo "<p class=\"php-error\">Coś poszło nie tak.</p>";
            }
            if (isset($_GET["mess"]) && $_GET["mess"] == "zablokowane") {
                echo "<p class=\"php-error\">Twoje konto zostało zablokowane przez Administratora.</p>";
            }
            ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.setAttribute('name', 'eye-outline');
                } else {
                    input.type = 'password';
                    this.setAttribute('name', 'eye-off-outline');
                }
            });
        });
    </script>
    </main>
<?php
require_once dirname(__DIR__, 2) . '/views/footer.php';
?>