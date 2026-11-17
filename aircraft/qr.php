<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM aircraft WHERE id = ?');
$stmt->execute([$id]);
$aircraft = $stmt->fetch();

if (!$aircraft) {
    http_response_code(404);
    $pageTitle = 'Aircraft not found';
    $active = 'aircraft';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="panel"><p class="empty">Aircraft not found.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$target = $scheme . '://' . $host . url('aircraft/view.php?id=' . $id);
$isLocal = str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1');

$pageTitle = 'QR code';
$active = 'aircraft';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="qr-box" id="qrcode"></div>
    <h2 style="margin-top: 16px;"><?= e($aircraft['registration']) ?></h2>
    <p class="muted"><?= e($aircraft['aircraft_type']) ?>, <?= e($aircraft['base']) ?></p>
    <div class="qr-url"><?= e($target) ?></div>
    <?php if ($isLocal): ?>
        <p class="muted">On a phone, replace localhost with this computer's LAN address.</p>
    <?php endif; ?>
    <div class="button-row" style="margin-top: 14px;">
        <button type="button" class="btn" data-print>Print</button>
        <a class="btn btn-secondary" href="<?= url('aircraft/view.php?id=' . $id) ?>">Back to aircraft</a>
    </div>
</div>

<script src="<?= url('assets/js/qr.min.js') ?>"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode($target, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        width: 220,
        height: 220,
        correctLevel: QRCode.CorrectLevel.M
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
