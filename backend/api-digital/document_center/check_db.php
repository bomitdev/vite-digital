<?php
require '../../config.php';
$stmt = $pdo2->query('DESCRIBE pdf_files');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
