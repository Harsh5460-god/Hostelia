<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $complaint_id = $_POST["complaint_id"];
    $status = $_POST["status"];

    $sql = "UPDATE complaints SET status = ? WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("si", $status, $complaint_id);

    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: admin.php");
    exit;
}

?>