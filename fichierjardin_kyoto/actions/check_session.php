<?php
// actions/check_session.php — appelé en AJAX
require_once '../includes/session.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['actif' => isset($_SESSION['user'])]);
