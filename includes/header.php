<?php
if (!defined('DIGIART_APP')) { http_response_code(403); exit; }
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Auth.php';

$pageTitle = $pageTitle ?? 'Digi-Art — Pembelajaran Seni Rupa';
$currentPage = $_GET['page'] ?? 'home';
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="<?= htmlspecialchars(Security::csrfToken()) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Digi-Art: Media pembelajaran digital Seni Rupa kelas VIII untuk materi Menggambar Ilustrasi untuk Mata Pelajaran Lain.">
    <meta name="theme-color" content="#1D4ED8">

    <!-- CSS Modules -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?= @filemtime(__DIR__ . '/../assets/css/main.css') ?>">
    <?php if (in_array($currentPage, ['materi', 'dashboard'], true)): ?>
    <link rel="stylesheet" href="assets/css/materi.css?v=<?= @filemtime(__DIR__ . '/../assets/css/materi.css') ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'kuis'): ?>
    <link rel="stylesheet" href="assets/css/quiz.css?v=<?= @filemtime(__DIR__ . '/../assets/css/quiz.css') ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'studio'): ?>
    <link rel="stylesheet" href="assets/css/studio.css?v=<?= @filemtime(__DIR__ . '/../assets/css/studio.css') ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'dashboard'): ?>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= @filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">
    <?php endif; ?>
</head>
<body>
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <!-- Top Navigation -->
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="main-wrapper" id="main-content" tabindex="-1">
