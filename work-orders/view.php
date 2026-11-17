<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT wo.*, a.registration, a.aircraft_type,
            u.full_name AS assignee, c.full_name AS creator,
            d.title AS discrepancy_title
     FROM work_orders wo
     JOIN aircraft a ON a.id = wo.aircraft_id
     JOIN users c ON c.id = wo.created_by
     LEFT JOIN users u ON u.id = wo.assigned_to
     LEFT JOIN discrepancies d ON d.id = wo.discrepancy_id
     WHERE wo.id = ?'
);
$stmt->execute([$id]);
$workOrder = $stmt->fetch();

if (!$workOrder) {
    http_response_code(404);
    $pageTitle = 'Work order not found';
    $active = 'work-orders';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="panel"><p class="empty">Work order not found.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$userOptions = db()->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();

$stmt = db()->prepare(
    'SELECT * FROM logbook_entries
     WHERE work_order_id = ?
     ORDER BY entry_date DESC, id DESC'
);
$stmt->execute([$id]);
$entries = $stmt->fetchAll();

$pageTitle = $workOrder['wo_number'];
$active = 'work-orders';
require __DIR__ . '/../includes/header.php';
?>

<div class="button-row page-actions">
    <a class="btn btn-secondary" href="<?= url('work-orders/index.php') ?>">Back to work orders</a>
    <a class="btn" href="<?= url('logbook/create.php?work_order_id=' . $id . '&aircraft_id=' . $workOrder['aircraft_id']) ?>">Add logbook entry</a>
</div>

<div class="panel">
    <h2>Work order details</h2>
    <div class="detail-list">
        <div class="detail-row">
            <div class="detail-label">Number</div>
            <div class="detail-value mono"><?= e($workOrder['wo_number']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Aircraft</div>
            <div class="detail-value">
                <a href="<?= url('aircraft/view.php?id=' . $workOrder['aircraft_id']) ?>" class="mono"><?= e($workOrder['registration']) ?></a>
                (<?= e($workOrder['aircraft_type']) ?>)
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Title</div>
            <div class="detail-value"><?= e($workOrder['title']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Description</div>
            <div class="detail-value"><?= nl2br(e($workOrder['description'])) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Priority</div>
            <div class="detail-value"><?= pill($workOrder['priority']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status</div>
            <div class="detail-value"><?= pill($workOrder['status']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Assigned to</div>
            <div class="detail-value"><?= e($workOrder['assignee'] ?? 'Unassigned') ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Discrepancy</div>
            <div class="detail-value">
                <?php if ($workOrder['discrepancy_id']): ?>
                    <a href="<?= url('discrepancies/edit.php?id=' . $workOrder['discrepancy_id']) ?>"><?= e($workOrder['discrepancy_title']) ?></a>
                <?php else: ?>
                    None
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Opened</div>
            <div class="detail-value"><?= e(fmt_datetime($workOrder['opened_at'])) ?> by <?= e($workOrder['creator']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Completed</div>
            <div class="detail-value"><?= e(fmt_datetime($workOrder['completed_at'])) ?></div>
        </div>
    </div>
</div>

<div class="panel panel-form">
    <h2>Update</h2>
    <form method="post" action="<?= url('work-orders/update.php?id=' . $id) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (work_order_statuses() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $workOrder['status'] === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="assigned_to">Assigned to</label>
            <select id="assigned_to" name="assigned_to">
                <option value="0">Unassigned</option>
                <?php foreach ($userOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= (int) $workOrder['assigned_to'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Save</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-top">
        <h2>Logbook entries</h2>
        <a href="<?= url('logbook/index.php?work_order_id=' . $id) ?>">View in logbook</a>
    </div>
    <?php if ($entries): ?>
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
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="nowrap"><?= e(fmt_date($entry['entry_date'])) ?></td>
                            <td><?= e(option_label($entry['entry_type'])) ?></td>
                            <td><?= e($entry['description']) ?></td>
                            <td><?= e($entry['performed_by']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No logbook entries yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
