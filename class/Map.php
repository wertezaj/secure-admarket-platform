<?php
class Map {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    public function getMarkers($search = '', $priceMin = null, $priceMax = null, $dateFilter = '', $userId = null, $limit = 20, $offset = 0) {
        $query = "SELECT id, name, description, lat, lng, price, date_from, address, image_path FROM markers WHERE 1=1";
        $types = "";
        $params = [];

        $this->buildFilters($query, $types, $params, $search, $priceMin, $priceMax, $dateFilter, $userId);

        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $markers = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['image_path'])) {
                    $row['images'] = json_decode($row['image_path'], true);
                } else {
                    $row['images'] = [];
                }
                $markers[] = $row;
            }
        }
        $stmt->close();
        return $markers;
    }

    public function getTotalMarkersCount($search = '', $priceMin = null, $priceMax = null, $dateFilter = '', $userId = null) {
        $query = "SELECT COUNT(*) as total FROM markers WHERE 1=1";
        $types = "";
        $params = [];

        $this->buildFilters($query, $types, $params, $search, $priceMin, $priceMax, $dateFilter, $userId);

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['total'] ?? 0;
    }

    private function buildFilters(&$query, &$types, &$params, $search, $priceMin, $priceMax, $dateFilter, $userId) {
        if (!empty($search)) {
            $query .= " AND (name LIKE ? ESCAPE '\\\\' OR address LIKE ? ESCAPE '\\\\')";
            $escapedSearch = addcslashes($search, '%_\\');
            $searchTerm = '%' . $escapedSearch . '%';
            $types .= "ss";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($priceMin !== null && $priceMin !== '') {
            $query .= " AND price >= ?";
            $types .= "d";
            $params[] = $priceMin;
        }
        if ($priceMax !== null && $priceMax !== '') {
            $query .= " AND price <= ?";
            $types .= "d";
            $params[] = $priceMax;
        }
        if (!empty($dateFilter)) {
            $query .= " AND date_from >= ?";
            $types .= "s";
            $params[] = $dateFilter;
        }
        if ($userId !== null && $userId !== 0) {
            $query .= " AND user_id = ?";
            $types .= "i";
            $params[] = intval($userId);
        }
    }

    public function getUserFavorites($userId) {
        $query = "SELECT marker_id FROM liked WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row['marker_id'];
        }
        $stmt->close();
        return $favorites;
    }

    public function saveMarker($user_id, $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson = null) {
        $stmt = $this->db->prepare("INSERT INTO markers (user_id, name, description, lat, lng, price, date_from, address, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdddsss", $user_id, $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson);
        $success = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $success ? $insert_id : false;
    }

    public function getMarkerById($id) {
        $query = "
            SELECT 
                m.id, m.user_id, m.name, m.description, m.lat, m.lng, m.price, m.date_from, m.address, m.image_path,
                u.name AS user_name, u.phone AS user_phone, u.email AS user_email
            FROM markers m 
            LEFT JOIN users u ON m.user_id = u.id 
            WHERE m.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $marker = null;
        if ($result && $result->num_rows > 0) {
            $marker = $result->fetch_assoc();
            $marker['images'] = !empty($marker['image_path']) ? json_decode($marker['image_path'], true) : [];
        }
        $stmt->close();
        return $marker;
    }

    public function getFavoriteMarkersDetails($userId) {
        $query = "
            SELECT m.id, m.name, m.price, m.address, m.image_path 
            FROM markers m
            INNER JOIN liked l ON m.id = l.marker_id
            WHERE l.user_id = ?
            ORDER BY l.created_at DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $favorites = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['image_path'])) {
                    $images = json_decode($row['image_path'], true);
                    $row['display_image'] = !empty($images) ? $images[0] : '/assets/img/dom-i-mieszkanie.jpeg';
                } else {
                    $row['display_image'] = '/assets/img/dom-i-mieszkanie.jpeg';
                }
                $favorites[] = $row;
            }
        }
        $stmt->close();
        return $favorites;
    }
    public function checkMarkerOwnership($markerId, $userId) {
        $query = "SELECT id FROM markers WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $markerId, $userId);
        $stmt->execute();
        $stmt->store_result();
        $isOwner = $stmt->num_rows > 0;
        $stmt->close();
        return $isOwner;
    }

    public function updateMarker($id, $user_id, $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson = null) {
        if ($imagePathJson !== null) {
            $stmt = $this->db->prepare("UPDATE markers SET name=?, description=?, lat=?, lng=?, price=?, date_from=?, address=?, image_path=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssdddsssii", $name, $desc, $lat, $lng, $price, $date_from, $address, $imagePathJson, $id, $user_id);
        } else {
            $stmt = $this->db->prepare("UPDATE markers SET name=?, description=?, lat=?, lng=?, price=?, date_from=?, address=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssdddssii", $name, $desc, $lat, $lng, $price, $date_from, $address, $id, $user_id);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function deleteMarker($id, $userId, $isAdminAction = false) {
        if ($isAdminAction) {
            $stmt = $this->db->prepare("SELECT image_path FROM markers WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $this->db->prepare("SELECT image_path FROM markers WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $userId);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        if ($row = $result->fetch_assoc()) {
            if (!empty($row['image_path'])) {
                $images = json_decode($row['image_path'], true);
                if (is_array($images)) {
                    $uploadsDir = realpath(__DIR__ . '/../uploads');
                    foreach ($images as $img) {
                        $filePath = realpath(__DIR__ . '/../' . ltrim($img, '/'));
                        if ($filePath && $uploadsDir && str_starts_with($filePath, $uploadsDir . DIRECTORY_SEPARATOR) && is_file($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }
            }
        }
        $stmt->close();

        $stmtLike = $this->db->prepare("DELETE FROM liked WHERE marker_id = ?");
        if ($stmtLike) {
            $stmtLike->bind_param("i", $id);
            $stmtLike->execute();
            $stmtLike->close();
        }

        if ($isAdminAction) {
            $stmtDelete = $this->db->prepare("DELETE FROM markers WHERE id = ?");
            $stmtDelete->bind_param("i", $id);
        } else {
            $stmtDelete = $this->db->prepare("DELETE FROM markers WHERE id = ? AND user_id = ?");
            $stmtDelete->bind_param("ii", $id, $userId);
        }

        $success = $stmtDelete->execute();
        $stmtDelete->close();

        return $success;
    }
    public function isAdmin($userId) {
        $stmt = $this->db->prepare("SELECT isadmin FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $role = (int)$row['isadmin'];
            return $role === 1 || $role === 2;
        }
        return false;
    }

    public function getTotalAdminMarkersCount() {
        $query = "SELECT COUNT(*) as total FROM markers";
        $result = $this->db->query($query);
        $row = $result ? $result->fetch_assoc() : null;
        return (int)($row['total'] ?? 0);
    }

    public function getAdminMarkers($limit = 20, $offset = 0) {
        $query = "
            SELECT m.id, m.name AS offer_name, m.address, m.price, m.date_from, 
                   u.name AS user_name, u.surname AS user_surname, u.email AS user_email
            FROM markers m
            LEFT JOIN users u ON m.user_id = u.id
            ORDER BY m.id DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $markers = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $markers[] = $row;
            }
        }
        $stmt->close();
        return $markers;
    }

    public function getTotalAdminUsersCount() {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = $this->db->query($query);
        $row = $result ? $result->fetch_assoc() : null;
        return (int)($row['total'] ?? 0);
    }

    public function getAdminUsers($limit = 20, $offset = 0) {
        $query = "SELECT id, name, surname, email, phone, isadmin, status FROM users ORDER BY isadmin DESC, id DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        $stmt->close();
        return $users;
    }


    public function isSuperAdmin($userId) {
        $stmt = $this->db->prepare("SELECT isadmin FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            return (int)$row['isadmin'] === 2;
        }
        return false;
    }



    public function updateUserRole($targetId, $makeAdmin) {
        $newRole = $makeAdmin ? 1 : 0;
        $stmt = $this->db->prepare("UPDATE users SET isadmin = ? WHERE id = ? AND isadmin != 2");
        $stmt->bind_param("ii", $newRole, $targetId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateUserBan($targetId, $ban) {
        $newStatus = $ban ? 2 : 1; // 2 = Zablokowany, 1 = Aktywny
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ? AND isadmin != 2");
        $stmt->bind_param("ii", $newStatus, $targetId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteUserComplete($userId) {
        $stmt = $this->db->prepare("SELECT id FROM markers WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $this->deleteMarker($row['id'], $userId, true);
        }
        $stmt->close();

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND isadmin != 2");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function getSettings() {
        $query = "SELECT setting_key, setting_value FROM settings";
        $result = $this->db->query($query);
        $settings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }

    public function updateSetting($key, $value) {
        $stmt = $this->db->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
        $stmt->bind_param("sss", $key, $value, $value);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}