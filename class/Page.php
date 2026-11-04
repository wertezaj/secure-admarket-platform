<?php

class Page
{
    public $id;
    public $title;
    public $description;
    public $link;
    public $img;
    public $status;
    public $meta_title;
    public $meta_img;
    public $meta_description;

    public function __construct($pageLink, $conn, $id = null)
    {
        if ($pageLink !== false) {
            $stmt = $conn->prepare("SELECT * FROM pages WHERE link = ?");
            if (!$stmt) {
                error_log("Database error in Page.php: " . $conn->error);
                throw new Exception("Wystąpił błąd wewnętrzny serwera.");
            }
            $stmt->bind_param("s", $pageLink);

        } elseif ($id !== null) {
            $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
            if (!$stmt) {
                error_log("Database error in Page.php: " . $conn->error);
                throw new Exception("Wystąpił błąd wewnętrzny serwera.");
            }
            $stmt->bind_param("i", $id);
        } else {
            throw new Exception('Musisz podać link lub id.');
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $page = $result->fetch_assoc();
            $this->id = $page['id'];
            $this->title = $page['title'];
            $this->description = $page['description'];
            $this->link = $page['link'];
            $this->img = $page['img'];
            $this->status = $page['status'];
            $this->meta_title = $page['meta_title'];
            $this->meta_img = $page['meta_img'];
            $this->meta_description = $page['meta_description'];
        } else {
            throw new Exception('Nie ma takiej strony');
        }
    }

    public function get_link($pageId, $conn)
    {
        $stmt = $conn->prepare("SELECT link FROM pages WHERE id = ?");
        if (!$stmt) {
            error_log("Database error in Page.php: " . $conn->error);
            throw new Exception("Wystąpił błąd wewnętrzny serwera.");
        }

        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['link'];
        } else {
            throw new Exception('Nie ma takiej strony');
        }
    }
}
