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

$stmt = db()->prepare(
    'SELECT d.*, u.full_name AS reporter
     FROM discrepancies d
     JOIN users u ON u.id = d.reported_by
     WHERE d.aircraft_id = ?
     ORDER BY d.reported_at DESC
     LIMIT 5'
);
$stmt->execute([$id]);
$discrepancies = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT wo.*, u.full_name AS assignee
     FROM work_orders wo
     LEFT JOIN users u ON u.id = wo.assigned_to
     WHERE wo.aircraft_id = ?
     ORDER BY wo.opened_at DESC
     LIMIT 5'
);
$stmt->execute([$id]);
$workOrders = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT * FROM maintenance_schedule
     WHERE aircraft_id = ?
     ORDER BY next_due_date
     LIMIT 5'
);
$stmt->execute([$id]);
$schedule = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT * FROM logbook_entries
     WHERE aircraft_id = ?
     ORDER BY entry_date DESC, id DESC
     LIMIT 5'
);
$stmt->execute([$id]);
$logbook = $stmt->fetchAll();

$stmt = db()->prepare(
    "SELECT s.*, u.full_name
     FROM ai_summaries s
     JOIN users u ON u.id = s.created_by
     WHERE s.scope = 'aircraft' AND s.aircraft_id = ?
     ORDER BY s.id DESC
     LIMIT 1"
);
$stmt->execute([$id]);
$summary = $stmt->fetch();

$pageTitle = $aircraft['registration'];
$active = 'aircraft';
require __DIR__ . '/../includes/header.php';
?>

<div class="button-row page-actions">
    <a class="btn btn-secondary" href="<?= url('aircraft/index.php') ?>">Back to aircraft</a>
    <a class="btn" href="<?= url('aircraft/edit.php?id=' . $id) ?>">Edit</a>
    <a class="btn btn-secondary" href="<?= url('aircraft/qr.php?id=' . $id) ?>">QR code</a>
</div>

<?php $typeImage = aircraft_type_image($aircraft['aircraft_type']); ?>
<div class="panel">
    <h2>Aircraft details</h2>
    <div class="detail-layout<?= $typeImage ? ' has-photo' : '' ?>">
        <div class="detail-list">
            <div class="detail-row">
                <div class="detail-label">Registration</div>
                <div class="detail-value mono"><?= e($aircraft['registration']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type</div>
                <div class="detail-value"><?= e($aircraft['aircraft_type']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">MSN</div>
                <div class="detail-value mono"><?= e($aircraft['msn']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Base</div>
                <div class="detail-value"><?= e($aircraft['base']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value"><?= pill($aircraft['status']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total hours</div>
                <div class="detail-value"><?= e(number_format((float) $aircraft['total_hours'], 1)) ?></div>
            </div>
        </div>
        <?php if ($typeImage): ?>
            <figure class="aircraft-photo">
                <img src="<?= url($typeImage) ?>" alt="<?= e($aircraft['aircraft_type']) ?>" loading="lazy" decoding="async">
            </figure>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-summary">
    <div class="panel-top">
        <h2>Summary</h2>
        <?php if (gemini_configured()): ?>
            <form method="post" action="<?= url('summaries/generate.php') ?>" data-summary-form>
                <?= csrf_field() ?>
                <input type="hidden" name="scope" value="aircraft">
                <input type="hidden" name="aircraft_id" value="<?= $id ?>">
                <input type="hidden" name="return" value="aircraft">
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
        <h2>Discrepancies</h2>
        <a href="<?= url('discrepancies/create.php?aircraft_id=' . $id) ?>">Report discrepancy</a>
    </div>
    <?php if ($discrepancies): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reported</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discrepancies as $item): ?>
                        <tr>
                            <td><a href="<?= url('discrepancies/edit.php?id=' . $item['id']) ?>"><?= e($item['title']) ?></a></td>
                            <td><?= pill($item['severity']) ?></td>
                            <td><?= pill($item['status']) ?></td>
                            <td class="nowrap"><?= e(fmt_datetime($item['reported_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No discrepancies.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Work orders</h2>
        <a href="<?= url('work-orders/create.php?aircraft_id=' . $id) ?>">Create work order</a>
    </div>
    <?php if ($workOrders): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workOrders as $item): ?>
                        <tr>
                            <td class="mono"><a href="<?= url('work-orders/view.php?id=' . $item['id']) ?>"><?= e($item['wo_number']) ?></a></td>
                            <td><?= e($item['title']) ?></td>
                            <td><?= pill($item['priority']) ?></td>
                            <td><?= pill($item['status']) ?></td>
                            <td><?= e($item['assignee'] ?? 'Unassigned') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No work orders.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Schedule</h2>
        <a href="<?= url('schedule/index.php?aircraft_id=' . $id) ?>">View schedule</a>
    </div>
    <?php if ($schedule): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Type</th>
                        <th>Next due</th>
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedule as $item): ?>
                        <tr>
                            <td><?= e($item['title']) ?></td>
                            <td><?= e(option_label($item['task_type'])) ?></td>
                            <td class="nowrap"><?= e(fmt_date($item['next_due_date'])) ?></td>
                            <td><?= due_pill($item['next_due_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No scheduled tasks.</p>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Logbook</h2>
        <a href="<?= url('logbook/index.php?aircraft_id=' . $id) ?>">View logbook</a>
    </div>
    <?php if ($logbook): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Entry</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logbook as $entry): ?>
                        <tr>
                            <td class="nowrap"><?= e(fmt_date($entry['entry_date'])) ?></td>
                            <td><?= e(option_label($entry['entry_type'])) ?></td>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
