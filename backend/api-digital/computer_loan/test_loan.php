<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$json = '{"asset_id":1, "borrower_name":"test", "department":"IT", "objective":"test", "expected_return_date":"2026-12-31"}';
file_put_contents('php://input', $json);
$_POST = json_decode($json, true);
require "request_loan.php";
