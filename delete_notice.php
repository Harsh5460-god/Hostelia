<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

$notice_id = filter_input(INPUT_POST, "notice_id", FILTER_VALIDATE_INT);

if (!$notice_id || $notice_id < 1) {
    header("Location: admin.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
$stmt->bind_param("i", $notice_id);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: admin.php");
exit;
?>