<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

// ยกเลิกการดึงข้อมูลจาก INVS แล้ว
echo json_encode(['status' => 'success', 'data' => [], 'count' => 0, 'message' => 'INVS connection disabled'], JSON_UNESCAPED_UNICODE);
