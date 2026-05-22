<?php
session_start();



require_once 'config.php';


session_destroy();

echo json_encode(["success" => true]);
exit;
