<?php

require_once __DIR__ . '/includes/init.php';
require_login();

$pdo = db();

$totalAircraft = (int) $pdo->query('SELECT COUNT(*) FROM aircraft')->fetchColumn();
$inService = (int) $pdo->query("SELECT COUNT(*) FROM aircraft WHERE status = 'in_service'")->fetchColumn();
$inMaintenance = (int) $pdo->query("SELECT COUNT(*) FROM aircraft WHERE status = 'maintenance'")->fetchColumn();
$openDiscrepancies = (int) $pdo->query("SELECT COUNT(*) FROM discrepancies WHERE status <> 'closed'")->fetchColumn();
$openWorkOrders = (int) $pdo->query("SELECT COUNT(*) FROM work_orders WHERE status IN ('open', 'in_progress')")->fetchColumn();
$dueSoonCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM maintenance_schedule WHERE next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
)->fetchColumn();

$dueSoon = $pdo->query(
    'SELECT s.*, a.registration
     FROM maintenance_schedule s
     JOIN aircraft a ON a.id = s.aircraft_id
     WHERE s.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY s.next_due_date
     LIMIT 6'
)->fetchAll();

$discrepancyList = $pdo->query(
    "SELECT d.*, a.registration
     FROM discrepancies d
     JOIN aircraft a ON a.id = d.aircraft_id
     WHERE d.status <> 'closed'
     ORDER BY FIELD(d.severity, 'high', 'medium', 'low'), d.reported_at DESC
     LIMIT 5"
)->fetchAll();

$recentLogbook = $pdo->query(
    'SELECT l.*, a.registration
     FROM logbook_entries l
     JOIN aircraft a ON a.id = l.aircraft_id
     ORDER BY l.entry_date DESC, l.id DESC
     LIMIT 5'
)->fetchAll();

$summary = $pdo->query(
    "SELECT s.*, u.full_name
     FROM ai_summaries s
     JOIN users u ON u.id = s.created_by
     WHERE s.scope = 'dashboard'
     ORDER BY s.id DESC
     LIMIT 1"
)->fetch();

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
    <a class="stat" href="<?= url('aircraft/index.php') ?>">
        <div class="stat-label">Aircraft</div>
        <div class="stat-value"><?= $totalAircraft ?></div>
    </a>
    <a class="stat" href="<?= url('aircraft/index.php?status=in_service') ?>">
        <div class="stat-label">In service</div>
        <div class="stat-value"><?= $inService ?></div>
    </a>
    <a class="stat" href="<?= url('aircraft/index.php?status=maintenance') ?>">
        <div class="stat-label">In maintenance</div>
        <div class="stat-value"><?= $inMaintenance ?></div>
    </a>
    <a class="stat" href="<?= url('discrepancies/index.php') ?>">
        <div class="stat-label">Open discrepancies</div>
        <div class="stat-value"><?= $openDiscrepancies ?></div>
    </a>
    <a class="stat" href="<?= url('work-orders/index.php') ?>">
        <div class="stat-label">Open work orders</div>
        <div class="stat-value"><?= $openWorkOrders ?></div>
    </a>
    <a class="stat stat-accent" href="<?= url('schedule/index.php') ?>">
        <div class="stat-label">Due in 30 days</div>
        <div class="stat-value"><?= $dueSoonCount ?></div>
    </a>
</div>

<div class="panel panel-summary">
    <div class="panel-top">
        <h2>Summary</h2>
        <?php if (gemini_configured()): ?>
            <form method="post" action="<?= url('summaries/generate.php') ?>" data-summary-form>
                <?= csrf_field() ?>
                <input type="hidden" name="scope" value="dashboard">
                <input type="hidden" name="return" value="dashboard">
                <button type="submit" class="btn btn-small">Generate summary</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($summary): ?>
        <div class="summary-text"><?= e($summary['summary_text']) ?></div>
        <div class="summary-meta">
            Gemini, <?= e(fmt_datetime($summary['created_at'])) ?>, by <?= e($summary['full_name']) ?>
        </div>
    <?php else: ?>
        <p class="empty">No summary yet.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Due soon</h2>
        <a href="<?= url('schedule/index.php') ?>">View schedule</a>
    </div>
    <?php if ($dueSoon): ?>
        <div class="table-wrap table-due">
            <table>
                <thead>
                    <tr>
                        <th>Aircraft</th>
                        <th>Task</th>
                        <th>Due</th>
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dueSoon as $item): ?>
                        <tr>
                            <td class="mono"><?= e($item['registration']) ?></td>
                            <td><?= e($item['title']) ?></td>
                            <td class="nowrap"><?= e(fmt_date($item['next_due_date'])) ?></td>
                            <td><?= due_pill($item['next_due_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No tasks due in the next 30 days.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Open discrepancies</h2>
        <a href="<?= url('discrepancies/index.php') ?>">View discrepancies</a>
    </div>
    <?php if ($discrepancyList): ?>
        <div class="table-wrap table-discrepancies">
            <table>
                <thead>
                    <tr>
                        <th>Aircraft</th>
                        <th>Discrepancy</th>
                        <th>Severity</th>
                        <th>Reported</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discrepancyList as $item): ?>
                        <tr>
                            <td class="mono"><?= e($item['registration']) ?></td>
                            <td><a href="<?= url('discrepancies/edit.php?id=' . $item['id']) ?>"><?= e($item['title']) ?></a></td>
                            <td><?= pill($item['severity']) ?></td>
                            <td class="nowrap"><?= e(fmt_datetime($item['reported_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No open discrepancies.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Recent logbook</h2>
        <a href="<?= url('logbook/index.php') ?>">View logbook</a>
    </div>
    <?php if ($recentLogbook): ?>
        <div class="table-wrap table-logbook">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Aircraft</th>
                        <th>Entry</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogbook as $entry): ?>
                        <tr>
                            <td class="nowrap"><?= e(fmt_date($entry['entry_date'])) ?></td>
                            <td class="nowrap mono"><?= e($entry['registration']) ?></td>
                            <td><?= e(excerpt($entry['description'], 90)) ?></td>
                            <td><?= e($entry['performed_by']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No logbook entries.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
