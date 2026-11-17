<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftId = (int) ($_GET['aircraft_id'] ?? 0);
$entryType = (string) ($_GET['entry_type'] ?? '');
$workOrderId = (int) ($_GET['work_order_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];

if ($aircraftId > 0) {
    $where[] = 'l.aircraft_id = ?';
    $params[] = $aircraftId;
}

if (in_array($entryType, logbook_types(), true)) {
    $where[] = 'l.entry_type = ?';
    $params[] = $entryType;
}

if ($workOrderId > 0) {
    $where[] = 'l.work_order_id = ?';
    $params[] = $workOrderId;
}

$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare('SELECT COUNT(*) FROM logbook_entries l' . $whereSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT l.*, a.registration, u.full_name AS author, wo.wo_number
        FROM logbook_entries l
        JOIN aircraft a ON a.id = l.aircraft_id
        JOIN users u ON u.id = l.created_by
        LEFT JOIN work_orders wo ON wo.id = l.work_order_id'
        . $whereSql
        . ' ORDER BY l.entry_date DESC, l.id DESC'
        . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

$aircraftOptions = db()->query('SELECT id, registration FROM aircraft ORDER BY registration')->fetchAll();

if ($aircraftId > 0) {
    $stmt = db()->prepare(
        "SELECT s.*, u.full_name
         FROM ai_summaries s
         JOIN users u ON u.id = s.created_by
         WHERE s.scope = 'logbook' AND s.aircraft_id = ?
         ORDER BY s.id DESC
         LIMIT 1"
    );
    $stmt->execute([$aircraftId]);
} else {
    $stmt = db()->query(
        "SELECT s.*, u.full_name
         FROM ai_summaries s
         JOIN users u ON u.id = s.created_by
         WHERE s.scope = 'logbook' AND s.aircraft_id IS NULL
         ORDER BY s.id DESC
         LIMIT 1"
    );
}
$summary = $stmt->fetch();

$filterQuery = [];
if ($aircraftId > 0) {
    $filterQuery['aircraft_id'] = $aircraftId;
}
if ($entryType !== '') {
    $filterQuery['entry_type'] = $entryType;
}
if ($workOrderId > 0) {
    $filterQuery['work_order_id'] = $workOrderId;
}

$pageTitle = 'Logbook';
$active = 'logbook';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="get" action="<?= url('logbook/index.php') ?>" class="filter-bar">
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
            <label for="entry_type">Type</label>
            <select id="entry_type" name="entry_type">
                <option value="">All</option>
                <?php foreach (logbook_types() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $entryType === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($workOrderId > 0): ?>
            <input type="hidden" name="work_order_id" value="<?= $workOrderId ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-secondary btn-small">Filter</button>
        <?php if ($filterQuery !== []): ?>
            <a class="btn btn-secondary btn-small" href="<?= url('logbook/index.php') ?>">Clear</a>
        <?php endif; ?>
        <a class="btn btn-small" href="<?= url('logbook/create.php' . ($aircraftId > 0 ? '?aircraft_id=' . $aircraftId : '')) ?>">Add entry</a>
    </form>

    <?php if ($entries): ?>
        <div class="log-list">
            <?php foreach ($entries as $entry): ?>
                <article class="log-entry">
                    <div class="log-entry-head">
                        <span class="log-entry-date"><?= e(fmt_date($entry['entry_date'])) ?></span>
                        <a href="<?= url('aircraft/view.php?id=' . $entry['aircraft_id']) ?>" class="mono"><?= e($entry['registration']) ?></a>
                        <?= pill($entry['entry_type']) ?>
                    </div>
                    <div class="log-entry-text"><?= e($entry['description']) ?></div>
                    <div class="log-entry-meta">
                        <span>By <?= e($entry['performed_by']) ?></span>
                        <?php if ($entry['flight_hours'] !== null): ?>
                            <span><?= e(number_format((float) $entry['flight_hours'], 1)) ?> hours</span>
                        <?php endif; ?>
                        <?php if ($entry['work_order_id']): ?>
                            <span><a href="<?= url('work-orders/view.php?id=' . $entry['work_order_id']) ?>" class="mono"><?= e($entry['wo_number']) ?></a></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= url('logbook/index.php?' . http_build_query(array_merge($filterQuery, ['page' => $page - 1]))) ?>">Previous</a>
                <?php endif; ?>
                <span class="current"><?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= url('logbook/index.php?' . http_build_query(array_merge($filterQuery, ['page' => $page + 1]))) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="empty">No logbook entries match this filter.</p>
    <?php endif; ?>
</div>

<div class="panel panel-summary">
    <div class="panel-top">
        <h2>Summary</h2>
        <?php if (gemini_configured()): ?>
            <form method="post" action="<?= url('summaries/generate.php') ?>" data-summary-form>
                <?= csrf_field() ?>
                <input type="hidden" name="scope" value="logbook">
                <?php if ($aircraftId > 0): ?>
                    <input type="hidden" name="aircraft_id" value="<?= $aircraftId ?>">
                <?php endif; ?>
                <input type="hidden" name="return" value="logbook">
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
