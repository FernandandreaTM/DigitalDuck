<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['dd_admin'])) {
    header('Location: login.php');
    exit;
}
