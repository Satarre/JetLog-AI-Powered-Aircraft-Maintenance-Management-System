<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$pdo = db();

$aircraftByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total FROM aircraft GROUP BY status ORDER BY total DESC'
)->fetchAll();

$openByPriority = $pdo->query(
    "SELECT priority, COUNT(*) AS total
     FROM work_orders
     WHERE status IN ('open', 'in_progress')
     GROUP BY priority
     ORDER BY FIELD(priority, 'high', 'medium', 'low')"
)->fetchAll();

$completedByMonth = $pdo->query(
    "SELECT DATE_FORMAT(completed_at, '%Y-%m') AS month, COUNT(*) AS total
     FROM work_orders
     WHERE status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
     GROUP BY month
     ORDER BY month DESC"
)->fetchAll();

$discrepanciesByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total FROM discrepancies GROUP BY status ORDER BY total DESC'
)->fetchAll();

$dueByAircraft = $pdo->query(
    'SELECT a.registration, COUNT(*) AS total
     FROM maintenance_schedule s
     JOIN aircraft a ON a.id = s.aircraft_id
     WHERE s.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     GROUP BY a.id, a.registration
     ORDER BY total DESC, a.registration'
)->fetchAll();

$logbookByType = $pdo->query(
    'SELECT entry_type, COUNT(*) AS total
     FROM logbook_entries
     WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY entry_type
     ORDER BY total DESC'
)->fetchAll();

$aircraftMax = $aircraftByStatus ? max(array_map('intval', array_column($aircraftByStatus, 'total'))) : 0;
$priorityMax = $openByPriority ? max(array_map('intval', array_column($openByPriority, 'total'))) : 0;

$pageTitle = 'Reports';
$active = 'reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="report-grid report-grid-wide">
    <div class="panel">
        <h2>Aircraft by status</h2>
        <?php if ($aircraftByStatus): ?>
            <?php foreach ($aircraftByStatus as $row): ?>
                <?php $total = (int) $row['total']; ?>
                <div class="bar-row">
                    <div class="bar-label"><?= e(option_label($row['status'])) ?></div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= $aircraftMax > 0 ? (int) round($total / $aircraftMax * 100) : 0 ?>%"></div>
                    </div>
                    <div class="bar-value"><?= $total ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No aircraft records.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Open work orders by priority</h2>
        <?php if ($openByPriority): ?>
            <?php foreach ($openByPriority as $row): ?>
                <?php $total = (int) $row['total']; ?>
                <div class="bar-row">
                    <div class="bar-label"><?= e(option_label($row['priority'])) ?></div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= $priorityMax > 0 ? (int) round($total / $priorityMax * 100) : 0 ?>%"></div>
                    </div>
                    <div class="bar-value"><?= $total ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No open work orders.</p>
        <?php endif; ?>
    </div>
</div>

<div class="report-grid">
    <div class="panel">
        <h2>Completed work orders, last 90 days</h2>
        <?php if ($completedByMonth): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedByMonth as $row): ?>
                            <tr>
                                <td><?= e(date('M Y', strtotime($row['month'] . '-01'))) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">No completed work orders in this period.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Discrepancies by status</h2>
        <?php if ($discrepanciesByStatus): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discrepanciesByStatus as $row): ?>
                            <tr>
                                <td><?= pill($row['status']) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">No discrepancies.</p>
        <?php endif; ?>
    </div>
</div>

<div class="report-grid">
    <div class="panel">
        <h2>Tasks due within 30 days by aircraft</h2>
        <?php if ($dueByAircraft): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Aircraft</th>
                            <th>Tasks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dueByAircraft as $row): ?>
                            <tr>
                                <td class="mono"><?= e($row['registration']) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">No tasks due in this period.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Logbook entries, last 30 days</h2>
        <?php if ($logbookByType): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Entries</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logbookByType as $row): ?>
                            <tr>
                                <td><?= e(option_label($row['entry_type'])) ?></td>
                                <td><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">No logbook entries in this period.</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
