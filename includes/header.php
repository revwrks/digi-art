<?php
if (!defined('DIGIART_APP')) {
    require_once __DIR__ . '/../config/config.php';
}
require_once __DIR__ . '/../classes/Auth.php';

$pageTitle = $pageTitle ?? 'Digi-Art — Pembelajaran Seni Rupa';
$currentPage = $_GET['page'] ?? 'home';
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Digi-Art: Media pembelajaran digital Seni Rupa kelas VIII untuk materi Menggambar Ilustrasi untuk Mata Pelajaran Lain.">
    <meta name="theme-color" content="#1f4be0">

    <!-- CSS Modules -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?= @filemtime(__DIR__ . '/../assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/materi.css?v=<?= @filemtime(__DIR__ . '/../assets/css/materi.css') ?>">
    <link rel="stylesheet" href="assets/css/quiz.css?v=<?= @filemtime(__DIR__ . '/../assets/css/quiz.css') ?>">
    <link rel="stylesheet" href="assets/css/studio.css?v=<?= @filemtime(__DIR__ . '/../assets/css/studio.css') ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= @filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">
</head>
<body>
    <!-- Top Navigation -->
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="main-wrapper">
