<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$status = (string) ($_GET['status'] ?? '');
$aircraftId = (int) ($_GET['aircraft_id'] ?? 0);

$where = [];
$params = [];

if (in_array($status, work_order_statuses(), true)) {
    $where[] = 'wo.status = ?';
    $params[] = $status;
}

if ($aircraftId > 0) {
    $where[] = 'wo.aircraft_id = ?';
    $params[] = $aircraftId;
}

$sql = 'SELECT wo.*, a.registration, u.full_name AS assignee
        FROM work_orders wo
        JOIN aircraft a ON a.id = wo.aircraft_id
        LEFT JOIN users u ON u.id = wo.assigned_to';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY FIELD(wo.status, 'open', 'in_progress', 'completed', 'cancelled'), wo.opened_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$aircraftOptions = db()->query('SELECT id, registration FROM aircraft ORDER BY registration')->fetchAll();

$pageTitle = 'Work orders';
$active = 'work-orders';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="get" action="<?= url('work-orders/index.php') ?>" class="filter-bar">
        <div class="filter-item">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (work_order_statuses() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>>
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
        <?php if ($status !== '' || $aircraftId > 0): ?>
            <a class="btn btn-secondary btn-small" href="<?= url('work-orders/index.php') ?>">Clear</a>
        <?php endif; ?>
        <a class="btn btn-small" href="<?= url('work-orders/create.php') ?>">Create work order</a>
    </form>

    <?php if ($list): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Aircraft</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $item): ?>
                        <tr>
                            <td class="nowrap mono"><a href="<?= url('work-orders/view.php?id=' . $item['id']) ?>"><?= e($item['wo_number']) ?></a></td>
                            <td class="nowrap mono"><?= e($item['registration']) ?></td>
                            <td>
                                <?= e($item['title']) ?>
                                <div class="muted"><?= e($item['assignee'] ?? 'Unassigned') ?></div>
                            </td>
                            <td><?= pill($item['priority']) ?></td>
                            <td><?= pill($item['status']) ?></td>
                            <td class="actions">
                                <a href="<?= url('work-orders/view.php?id=' . $item['id']) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No work orders match this filter.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
