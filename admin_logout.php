<?php
require_once "admin_check.php";
require_once "db.php";
unset($_SESSION["admin_id"]);
unset($_SESSION["admin_username"]);

session_regenerate_id(true);

header("Location: admin_login.php");
exit;