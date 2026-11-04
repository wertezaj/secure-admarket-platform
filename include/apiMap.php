<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Funkcja do kompresji i konwersji zdjęć na WEBP
function compressAndResizeImage($sourcePath, $destinationPath, $mimeType, $quality = 80, $maxWidth = 1920)
{
    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }

    [$origWidth, $origHeight] = $imageInfo;

    $maxPixels = 12000 * 12000;
    if ($origWidth <= 0 || $origHeight <= 0 || $origWidth > 12000 || $origHeight > 12000 || ($origWidth * $origHeight) > $maxPixels) {
        return false;
    }
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/pjpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
        case 'image/x-png':
            $image = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // Zachowanie przezroczystości (dla PNG/WEBP)
    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);

    $width = imagesx($image);
    $height = imagesy($image);

    // 2. Skalowanie obrazu, jeśli jest zbyt szeroki
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Utrzymanie przezroczystości przy zmianie rozmiaru
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefill($newImage, 0, 0, $transparent);

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $newImage;
    }

    // 3. Zapis jako lekki plik WEBP
    $result = imagewebp($image, $destinationPath, $quality);
    imagedestroy($image);

    return $result;
}

try {
    require_once __DIR__ . '/config.php';
    require_once dirname(__DIR__) . '/class/Map.php';

    $mapApp = new Map($conn);
    $action = $_POST['action'] ?? null;
    if ($action !== 'list') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Nieważny token CSRF. Odśwież stronę i spróbuj ponownie.']);
            exit;
        }
    }

    if ($action === 'list') {
        echo json_encode($mapApp->getMarkers());
    } elseif ($action === 'add') {
        if (!isset($_SESSION['uid'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany.']);
            exit;
        }

        $user_id = $_SESSION['uid'];
        $name = mb_substr(trim(strip_tags($_POST['name'] ?? '')), 0, 150, 'UTF-8');
        $desc = mb_substr(trim(strip_tags($_POST['description'] ?? '')), 0, 5000, 'UTF-8');
        $address = mb_substr(trim(strip_tags($_POST['address'] ?? '')), 0, 255, 'UTF-8');

        $lat = filter_var($_POST['lat'] ?? 0, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['lng'] ?? 0, FILTER_VALIDATE_FLOAT);
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lat < -90 || $lat > 90 || $lng === false || $lng < -180 || $lng > 180) {
            throw new Exception("Nieprawidłowe współrzędne geograficzne.");
        }
        if ($price === false || $price < 0 || $price > 10000000) {
            throw new Exception("Nieprawidłowa kwota ogłoszenia.");
        }

        $date_from = $_POST['date_from'] ?? null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            throw new Exception("Nieprawidłowy format daty.");
        }

        if (empty($name) || empty($desc) || $lat === false || $lng === false) {
            throw new Exception("Brak wymaganych danych w formularzu.");
        }

        $uploadedImages = [];
        if (isset($_FILES['image'])) {
            $fileCount = count($_FILES['image']['name']);

            if ($fileCount > 5) {
                throw new Exception("Przekroczono limit ilości zdjęć (maksymalnie 5).");
            }
            if ($fileCount === 0 || empty($_FILES['image']['name'][0])) {
                throw new Exception("Wymagane jest dodanie przynajmniej jednego zdjęcia.");
            }

            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/x-png', 'image/pjpeg'];

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['image']['error'][$i] === UPLOAD_ERR_INI_SIZE) {
                    throw new Exception("Plik " . $_FILES['image']['name'][$i] . " przekracza limit konfiguracji serwera PHP.");
                }

                if ($_FILES['image']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['image']['tmp_name'][$i];
                    $fileSize = $_FILES['image']['size'][$i];

                    // Zwiększony limit zabezpieczenia PHP do 15 MB
                    if ($fileSize > (15 * 1024 * 1024)) {
                        throw new Exception("Jedno ze zdjęć jest za duże (Maksymalnie 15MB).");
                    }

                    $mimeType = finfo_file($finfo, $tmpName);

                    if ($mimeType === false) {
                        $mimeType = mime_content_type($tmpName);
                    }

                    if (!in_array($mimeType, $allowedMimes)) {
                        throw new Exception("Wykryto niedozwolony format pliku. Obsługiwane to JPG, PNG, WEBP.");
                    }

                    // Wszystkie pliki będą zapisywane jako WEBP dla najlepszej kompresji
                    $newFileName = bin2hex(random_bytes(16)) . '.webp';
                    $destination = $uploadDir . $newFileName;

                    // Kompresja i zapis zamiast zwykłego move_uploaded_file
                    $isCompressed = compressAndResizeImage($tmpName, $destination, $mimeType);

                    if ($isCompressed) {
                        $uploadedImages[] = '/uploads/' . $newFileName;
                    } else {
                        throw new Exception("Nie udało się skompresować zdjęcia " . $_FILES['image']['name'][$i] . ". Możliwe, że plik jest uszkodzony.");
                    }
                }
            }
            finfo_close($finfo);
        }

        $imagePathJson = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

        // ZMIANA: Pobranie i walidacja nowego ID z bazy danych
        $newMarkerId = $mapApp->saveMarker($user_id, $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson);

        if ($newMarkerId) {
            // Zwracamy $newMarkerId w formacie JSON
            echo json_encode([
                'status' => 'success',
                'message' => 'Dodano do bazy',
                'id' => $newMarkerId
            ]);
        } else {
            throw new Exception("Wystąpił błąd podczas zapisu do bazy danych.");
        }
    } elseif ($action === 'edit') {
        if (!isset($_SESSION['uid'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany.']);
            exit;
        }

        $user_id = $_SESSION['uid'];
        $offer_id = filter_var($_POST['offer_id'] ?? 0, FILTER_VALIDATE_INT);

        // Krytyczne zabezpieczenie: Upewniamy się na poziomie API, że nikt nie podmienił ID w konsoli przeglądarki
        if (!$offer_id || !$mapApp->checkMarkerOwnership($offer_id, $user_id)) {
            throw new Exception("Brak uprawnień do edycji tego ogłoszenia.");
        }

        $name = mb_substr(trim(strip_tags($_POST['name'] ?? '')), 0, 150, 'UTF-8');
        $desc = mb_substr(trim(strip_tags($_POST['description'] ?? '')), 0, 5000, 'UTF-8');
        $address = mb_substr(trim(strip_tags($_POST['address'] ?? '')), 0, 255, 'UTF-8');

        $lat = filter_var($_POST['lat'] ?? 0, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['lng'] ?? 0, FILTER_VALIDATE_FLOAT);
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lat < -90 || $lat > 90 || $lng === false || $lng < -180 || $lng > 180) {
            throw new Exception("Nieprawidłowe współrzędne geograficzne.");
        }
        if ($price === false || $price < 0 || $price > 10000000) {
            throw new Exception("Nieprawidłowa kwota ogłoszenia.");
        }

        $date_from = $_POST['date_from'] ?? null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            throw new Exception("Nieprawidłowy format daty.");
        }

        if (empty($name) || empty($desc) || $lat === false || $lng === false) {
            throw new Exception("Brak wymaganych danych w formularzu.");
        }

        $uploadedImages = [];
        $imagePathJson = null;

        // Logika zdjęć aktywuje się tylko, jeśli użytkownik przesłał nowe pliki
        // Logika zdjęć: Sprawdzamy czy są nowe wgrywane pliki, a jeśli nie - czy zmieniono kolejność starych
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'][0])) {
            $fileCount = count($_FILES['image']['name']);

            if ($fileCount > 5) {
                throw new Exception("Przekroczono limit ilości zdjęć (maksymalnie 5).");
            }

            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/x-png', 'image/pjpeg'];

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['image']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['image']['tmp_name'][$i];
                    $fileSize = $_FILES['image']['size'][$i];

                    if ($fileSize > (15 * 1024 * 1024)) {
                        throw new Exception("Jedno ze zdjęć jest za duże (Maksymalnie 15MB).");
                    }

                    $mimeType = finfo_file($finfo, $tmpName) ?: mime_content_type($tmpName);

                    if (!in_array($mimeType, $allowedMimes)) {
                        throw new Exception("Niedozwolony format pliku.");
                    }

                    $newFileName = bin2hex(random_bytes(16)) . '.webp';
                    $destination = $uploadDir . $newFileName;

                    $isCompressed = compressAndResizeImage($tmpName, $destination, $mimeType);

                    if ($isCompressed) {
                        $uploadedImages[] = '/uploads/' . $newFileName;
                    }
                }
            }
            finfo_close($finfo);

            if (!empty($uploadedImages)) {
                // Usuwamy dotychczasowe pliki ze starej wersji ogłoszenia, aby uniknąć zapełnienia dysku (Disk Exhaustion DoS)
                $currentOffer = $mapApp->getMarkerById($offer_id);
                $oldImages = $currentOffer['images'] ?? [];
                $uploadsDir = realpath(__DIR__ . '/../uploads');

                if (is_array($oldImages) && $uploadsDir) {
                    foreach ($oldImages as $oldImg) {
                        $oldFilePath = realpath(__DIR__ . '/..' . $oldImg);
                        if ($oldFilePath && str_starts_with($oldFilePath, $uploadsDir . DIRECTORY_SEPARATOR) && is_file($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                }
                $imagePathJson = json_encode($uploadedImages);
            } else {
                $imagePathJson = null;
            }

        } elseif (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
            $currentOffer = $mapApp->getMarkerById($offer_id);
            $allowedCurrentImages = $currentOffer['images'] ?? [];

            $safeExisting = [];
            foreach ($_POST['existing_images'] as $img) {
                $cleanImg = trim($img);
                // Akceptujemy TYLKO zdjęcia, które już należały do tego ogłoszenia
                if (in_array($cleanImg, $allowedCurrentImages, true)) {
                    $safeExisting[] = $cleanImg;
                }
            }
            $uploadsDir = realpath(__DIR__ . '/../uploads');
            foreach ($allowedCurrentImages as $oldImg) {
                if (!in_array($oldImg, $safeExisting, true)) {
                    $oldFilePath = realpath(__DIR__ . '/..' . $oldImg);
                    if ($oldFilePath && $uploadsDir && str_starts_with($oldFilePath, $uploadsDir . DIRECTORY_SEPARATOR) && is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
            }
            $imagePathJson = json_encode($safeExisting);
        }

        $isUpdated = $mapApp->updateMarker($offer_id, $user_id, $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson);

        if ($isUpdated) {
            echo json_encode(['status' => 'success', 'message' => 'Ogłoszenie zaktualizowane', 'id' => $offer_id]);
        } else {
            throw new Exception("Wystąpił błąd podczas aktualizacji bazy danych.");
        }
    } elseif ($action === 'delete') {
        if (!isset($_SESSION['uid'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany.']);
            exit;
        }

        $user_id = $_SESSION['uid'];
        $offer_id = filter_var($_POST['offer_id'] ?? 0, FILTER_VALIDATE_INT);

        // ZMIANA: Sprawdzamy czy to admin. Jeśli nie, sprawdzamy czy to właściciel.
        $isAdmin = $mapApp->isAdmin($user_id);

        if (!$offer_id || (!$isAdmin && !$mapApp->checkMarkerOwnership($offer_id, $user_id))) {
            throw new Exception("Brak uprawnień do usunięcia tego ogłoszenia.");
        }

        if ($mapApp->deleteMarker($offer_id, $user_id, $isAdmin)) {
            echo json_encode(['status' => 'success', 'message' => 'Ogłoszenie usunięte pomyślnie.']);
        } else {
            throw new Exception("Wystąpił błąd podczas usuwania. Skontaktuj się z administratorem.");
        }
    } elseif (in_array($action, ['ban', 'unban', 'give_admin', 'revoke_admin', 'delete_user'])) {
        if (!isset($_SESSION['uid'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany.']);
            exit;
        }

        $superadmin_id = $_SESSION['uid'];
        if (!$mapApp->isSuperAdmin($superadmin_id)) {
            throw new Exception("Tylko Główny Administrator (SuperAdmin) może wykonywać tę akcję.");
        }

        $target_id = filter_var($_POST['target_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$target_id) throw new Exception("Brak ID użytkownika.");

        if ($action === 'ban') $mapApp->updateUserBan($target_id, true);
        if ($action === 'unban') $mapApp->updateUserBan($target_id, false);
        if ($action === 'give_admin') $mapApp->updateUserRole($target_id, true);
        if ($action === 'revoke_admin') $mapApp->updateUserRole($target_id, false);
        if ($action === 'delete_user') $mapApp->deleteUserComplete($target_id);

        echo json_encode(['status' => 'success', 'message' => 'Akcja wykonana pomyślnie.']);
        exit;
    } elseif ($action === 'update_settings') {
        if (!isset($_SESSION['uid']) || !$mapApp->isSuperAdmin($_SESSION['uid'])) {
            throw new Exception("Brak uprawnień do zmiany ustawień. Wymagany profil SuperAdmina.");
        }

        $allowed_keys = [
            'company_name', 'company_subtitle', 'contact_phone1', 'contact_phone2',
            'contact_email', 'contact_address', 'hours_week_open', 'hours_week_close',
            'hours_sat_open', 'hours_sat_close', 'map_iframe'
        ];

        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                if ($key === 'map_iframe') {
                    // Wyciągamy sam URL z iframe lub przyjmujemy tylko bezpieczny URL z Google Maps
                    if (preg_match('/src=["\'](https:\/\/www\.google\.com\/maps\/embed\?[^"\']+)["\']/', $val, $matches)) {
                        $safeUrl = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
                        $safeIframe = '<iframe src="' . $safeUrl . '" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
                        $mapApp->updateSetting($key, $safeIframe);
                    } else {
                        throw new Exception("Nieprawidłowy kod mapy Google.");
                    }
                } else {
                    // Zapisujemy czysty tekst, a ewentualne <br> generujemy przy wyświetlaniu funkcją nl2br(htmlspecialchars(...))
                    $mapApp->updateSetting($key, strip_tags($val));
                }
            }
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['logo']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['logo']['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new Exception("Plik logo przekracza dopuszczalny limit konfiguracji serwera PHP (upload_max_filesize).");
            }
            if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Wystąpił błąd podczas przesyłania pliku logo (kod błędu: " . (int)$_FILES['logo']['error'] . ").");
            }

            $tmpName = $_FILES['logo']['tmp_name'];
            $fileSize = $_FILES['logo']['size'];

            // Limit wielkości pliku: 5 MB
            if ($fileSize > (5 * 1024 * 1024)) {
                throw new Exception("Plik logo jest za duży (Maksymalnie 5MB).");
            }

            // Bezpieczna weryfikacja MIME przez finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            // Wykluczamy SVG ze względu na wektor XSS; akceptujemy wyłącznie bitmapy
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/x-png', 'image/pjpeg'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                throw new Exception("Niedozwolony format logo. Obsługiwane formaty to JPG, PNG oraz WEBP.");
            }

            $imgDir = dirname(__DIR__) . '/assets/img/';
            if (!is_dir($imgDir)) {
                mkdir($imgDir, 0755, true);
            }

            // Losowa, nieprzewidywalna nazwa
            $newFileName = 'logo_' . bin2hex(random_bytes(16)) . '.webp';
            $destination = $imgDir . $newFileName;

            // Przepuszczenie przez silnik graficzny GD (usuwa złośliwy kod w metadanych EXIF / polyglots)
            $isCompressed = compressAndResizeImage($tmpName, $destination, $mimeType, 90, 600);

            if (!$isCompressed) {
                throw new Exception("Nie udało się przetworzyć pliku logo. Plik może być uszkodzony.");
            }

            // Pobranie starej ścieżki i bezpieczne usunięcie starego logo (jeśli nie jest to domyślne logo.svg)
            $currentSettings = $mapApp->getSettings();
            $oldLogo = $currentSettings['logo_path'] ?? '';

            if (!empty($oldLogo) && str_starts_with($oldLogo, '/assets/img/logo_')) {
                $oldLogoPath = realpath(dirname(__DIR__) . $oldLogo);
                $imgRealDir = realpath($imgDir);

                if ($oldLogoPath && $imgRealDir && str_starts_with($oldLogoPath, $imgRealDir . DIRECTORY_SEPARATOR) && is_file($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }

            $mapApp->updateSetting('logo_path', '/assets/img/' . $newFileName);
        }

        echo json_encode(['status' => 'success', 'message' => 'Ustawienia strony zostały zaktualizowane.']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nieznana akcja.']);
    }

} catch
(Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>