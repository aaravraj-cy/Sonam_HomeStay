<?php
// Search page redirector - Single Homestay Model
require_once __DIR__ . '/../includes/functions.php';

$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests = max(1, (int)($_GET['guests'] ?? 1));
$roomType = trim($_GET['room_type'] ?? '');

$params = [];
if ($checkIn) $params['check_in'] = $checkIn;
if ($checkOut) $params['check_out'] = $checkOut;
if ($guests > 1) $params['guests'] = $guests;
if ($roomType) $params['room_type'] = $roomType;

$queryString = !empty($params) ? '?' . http_build_query($params) : '';
redirect(BASE_URL . $queryString . '#rooms');
