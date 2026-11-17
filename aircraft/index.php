<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(a.registration LIKE ? OR a.aircraft_type LIKE ? OR a.msn LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (in_array($status, aircraft_statuses(), true)) {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

$sql = 'SELECT a.*,
            (SELECT COUNT(*) FROM discrepancies d WHERE d.aircraft_id = a.id AND d.status <> \'closed\') AS open_discrepancies
        FROM aircraft a';

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY a.registration';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$aircraftList = $stmt->fetchAll();

$pageTitle = 'Aircraft';
$active = 'aircraft';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="get" action="<?= url('aircraft/index.php') ?>" class="filter-bar">
        <div class="filter-item">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Registration, type, or MSN">
        </div>
        <div class="filter-item">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (aircraft_statuses() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-small">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?>
            <a class="btn btn-secondary btn-small" href="<?= url('aircraft/index.php') ?>">Clear</a>
        <?php endif; ?>
        <a class="btn btn-small" href="<?= url('aircraft/create.php') ?>">Add aircraft</a>
    </form>

    <?php if ($aircraftList): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Registration</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Open discrepancies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aircraftList as $item): ?>
                        <tr>
                            <td class="nowrap mono"><a href="<?= url('aircraft/view.php?id=' . $item['id']) ?>"><?= e($item['registration']) ?></a></td>
                            <td class="nowrap"><?= e($item['aircraft_type']) ?></td>
                            <td><?= pill($item['status']) ?></td>
                            <td><?= (int) $item['open_discrepancies'] ?></td>
                            <td class="actions">
                                <a href="<?= url('aircraft/view.php?id=' . $item['id']) ?>">View</a>
                                <a href="<?= url('aircraft/edit.php?id=' . $item['id']) ?>">Edit</a>
                                <a href="<?= url('aircraft/qr.php?id=' . $item['id']) ?>">QR</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No aircraft match this filter.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
