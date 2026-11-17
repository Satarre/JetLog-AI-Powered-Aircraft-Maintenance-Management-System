<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftId = (int) ($_GET['aircraft_id'] ?? 0);
$state = (string) ($_GET['state'] ?? '');

$where = [];
$params = [];

if ($aircraftId > 0) {
    $where[] = 's.aircraft_id = ?';
    $params[] = $aircraftId;
}

if ($state === 'overdue') {
    $where[] = 's.next_due_date < CURDATE()';
} elseif ($state === 'due_soon') {
    $where[] = 's.next_due_date >= CURDATE() AND s.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
} elseif ($state === 'upcoming') {
    $where[] = 's.next_due_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
}

$sql = 'SELECT s.*, a.registration
        FROM maintenance_schedule s
        JOIN aircraft a ON a.id = s.aircraft_id';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY s.next_due_date, s.task_code';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$aircraftOptions = db()->query('SELECT id, registration FROM aircraft ORDER BY registration')->fetchAll();

$pageTitle = 'Schedule';
$active = 'schedule';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="get" action="<?= url('schedule/index.php') ?>" class="filter-bar">
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
        <div class="filter-item">
            <label for="state">State</label>
            <select id="state" name="state">
                <option value="">All</option>
                <option value="overdue"<?= $state === 'overdue' ? ' selected' : '' ?>>Overdue</option>
                <option value="due_soon"<?= $state === 'due_soon' ? ' selected' : '' ?>>Due soon</option>
                <option value="upcoming"<?= $state === 'upcoming' ? ' selected' : '' ?>>Upcoming</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-small">Filter</button>
        <?php if ($aircraftId > 0 || $state !== ''): ?>
            <a class="btn btn-secondary btn-small" href="<?= url('schedule/index.php') ?>">Clear</a>
        <?php endif; ?>
        <a class="btn btn-small" href="<?= url('schedule/create.php') ?>">Add task</a>
    </form>

    <?php if ($list): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Aircraft</th>
                        <th>Task</th>
                        <th>Interval</th>
                        <th>Next due</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $item): ?>
                        <tr>
                            <td class="nowrap mono"><?= e($item['registration']) ?></td>
                            <td>
                                <strong class="mono"><?= e($item['task_code']) ?></strong><br>
                                <?= e($item['title']) ?>
                            </td>
                            <td class="nowrap"><?= (int) $item['interval_days'] ?> days</td>
                            <td class="nowrap">
                                <?= e(fmt_date($item['next_due_date'])) ?>
                                <div><?= due_pill($item['next_due_date']) ?></div>
                                <div class="muted">
                                    <?= $item['last_done_date'] ? 'Last ' . e(fmt_date($item['last_done_date'])) : 'No record' ?>
                                </div>
                            </td>
                            <td class="actions">
                                <form method="post" action="<?= url('schedule/complete.php?id=' . $item['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-small">Mark done</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No scheduled tasks match this filter.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
