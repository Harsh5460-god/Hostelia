<?php
require_once "admin_check.php";
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST["title"];
    $notice_text = $_POST["notice_text"];

    $sql = "INSERT INTO notices (title, notice_text)
            VALUES (?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ss", $title, $notice_text);

    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: admin.php");
    exit;
}

?>