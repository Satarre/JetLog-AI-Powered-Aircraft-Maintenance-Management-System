<?php
$user = current_user();
$nav = [
    'dashboard' => [
        'label' => 'Dashboard',
        'path' => 'index.php',
        'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    ],
    'aircraft' => [
        'label' => 'Aircraft',
        'path' => 'aircraft/index.php',
        'icon' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    ],
    'discrepancies' => [
        'label' => 'Discrepancies',
        'path' => 'discrepancies/index.php',
        'icon' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    ],
    'work-orders' => [
        'label' => 'Work orders',
        'path' => 'work-orders/index.php',
        'icon' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    ],
    'schedule' => [
        'label' => 'Schedule',
        'path' => 'schedule/index.php',
        'icon' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    ],
    'logbook' => [
        'label' => 'Logbook',
        'path' => 'logbook/index.php',
        'icon' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
    ],
    'reports' => [
        'label' => 'Reports',
        'path' => 'reports/index.php',
        'icon' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'JetLog') ?> | JetLog</title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link rel="icon" type="image/png" href="<?= url('assets/img/favicon.png') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <a class="brand" href="<?= url('index.php') ?>">
                <img class="brand-mark" src="<?= url('assets/img/favicon.png') ?>" alt="" width="24" height="24" decoding="async">
                <span class="brand-text">
                    <span class="brand-name">JetLog</span>
                    <span class="brand-sub">Maintenance tracking</span>
                </span>
            </a>
            <button type="button" class="sidebar-toggle" data-sidebar-close aria-label="Close sidebar" title="Close sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <nav class="nav" id="sidebar-nav">
            <?php foreach ($nav as $key => $item): ?>
                <a href="<?= url($item['path']) ?>"<?= ($active ?? '') === $key ? ' class="active" aria-current="page"' : '' ?>>
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $item['icon'] ?></svg>
                    </span>
                    <span class="nav-label"><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
            <div class="sidebar-user-role"><?= e(status_label($user['role'])) ?></div>
            <form method="post" action="<?= url('logout.php') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="link-button sidebar-logout" aria-label="Log out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    <span class="link-button-text">Log out</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-close aria-hidden="true"></div>

    <main class="main">
        <header class="page-header">
            <button type="button" class="menu-button" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Open sidebar">
                <img class="menu-mark" src="<?= url('assets/img/favicon.png') ?>" alt="" width="28" height="28" decoding="async">
            </button>
            <h1><?= e($pageTitle ?? 'JetLog') ?></h1>
        </header>

        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
