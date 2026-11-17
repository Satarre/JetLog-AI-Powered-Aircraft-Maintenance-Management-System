<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('schedule/index.php');
}

verify_csrf();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM maintenance_schedule WHERE id = ?');
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    flash('error', 'Scheduled task not found.');
    redirect('schedule/index.php');
}

$stmt = db()->prepare(
    'UPDATE maintenance_schedule
     SET last_done_date = CURDATE(),
         next_due_date = DATE_ADD(CURDATE(), INTERVAL interval_days DAY)
     WHERE id = ?'
);
$stmt->execute([$id]);

flash('success', $task['task_code'] . ' marked as done.');
redirect('schedule/index.php?aircraft_id=' . $task['aircraft_id']);
