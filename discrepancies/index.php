<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$status = (string) ($_GET['status'] ?? '');
$severity = (string) ($_GET['severity'] ?? '');
$aircraftId = (int) ($_GET['aircraft_id'] ?? 0);

$where = [];
$params = [];

if (in_array($status, discrepancy_statuses(), true)) {
    $where[] = 'd.status = ?';
    $params[] = $status;
}

if (in_array($severity, discrepancy_severities(), true)) {
    $where[] = 'd.severity = ?';
    $params[] = $severity;
}

if ($aircraftId > 0) {
    $where[] = 'd.aircraft_id = ?';
    $params[] = $aircraftId;
}

$sql = 'SELECT d.*, a.registration, u.full_name AS reporter,
            (SELECT wo.id FROM work_orders wo WHERE wo.discrepancy_id = d.id ORDER BY wo.id DESC LIMIT 1) AS work_order_id
        FROM discrepancies d
        JOIN aircraft a ON a.id = d.aircraft_id
        JOIN users u ON u.id = d.reported_by';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY FIELD(d.severity, 'high', 'medium', 'low'), d.reported_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$aircraftOptions = db()->query('SELECT id, registration FROM aircraft ORDER BY registration')->fetchAll();

$pageTitle = 'Discrepancies';
$active = 'discrepancies';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="get" action="<?= url('discrepancies/index.php') ?>" class="filter-bar">
        <div class="filter-item">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (discrepancy_statuses() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="severity">Severity</label>
            <select id="severity" name="severity">
                <option value="">All</option>
                <?php foreach (discrepancy_severities() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $severity === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="aircraft_id">Aircraft</label>
            <select id="aircraft_id" name="aircraft_id">
                <option value="">All</option>
                <?php foreach ($aircraftOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $aircraftId === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['registration']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-small">Filter</button>
        <?php if ($status !== '' || $severity !== '' || $aircraftId > 0): ?>
            <a class="btn btn-secondary btn-small" href="<?= url('discrepancies/index.php') ?>">Clear</a>
        <?php endif; ?>
        <a class="btn btn-small" href="<?= url('discrepancies/create.php') ?>">Report discrepancy</a>
    </form>

    <?php if ($list): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Aircraft</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reported</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $item): ?>
                        <tr>
                            <td class="nowrap mono"><?= e($item['registration']) ?></td>
                            <td><a href="<?= url('discrepancies/edit.php?id=' . $item['id']) ?>"><?= e($item['title']) ?></a></td>
                            <td><?= pill($item['severity']) ?></td>
                            <td><?= pill($item['status']) ?></td>
                            <td class="nowrap">
                                <?= e(fmt_datetime($item['reported_at'])) ?>
                                <div class="muted">by <?= e($item['reporter']) ?></div>
                            </td>
                            <td class="actions">
                                <a href="<?= url('discrepancies/edit.php?id=' . $item['id']) ?>">Edit</a>
                                <?php if ($item['work_order_id']): ?>
                                    <a href="<?= url('work-orders/view.php?id=' . $item['work_order_id']) ?>">Work order</a>
                                <?php elseif ($item['status'] !== 'closed'): ?>
                                    <a href="<?= url('work-orders/create.php?discrepancy_id=' . $item['id']) ?>">Create WO</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No discrepancies match this filter.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
