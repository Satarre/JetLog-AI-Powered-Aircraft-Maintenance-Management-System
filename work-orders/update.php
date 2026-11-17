<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('work-orders/index.php');
}

verify_csrf();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM work_orders WHERE id = ?');
$stmt->execute([$id]);
$workOrder = $stmt->fetch();

if (!$workOrder) {
    flash('error', 'Work order not found.');
    redirect('work-orders/index.php');
}

$status = post_string('status');
$assignedTo = (int) ($_POST['assigned_to'] ?? 0);

if (!valid_choice($status, work_order_statuses())) {
    flash('error', 'Choose a valid status.');
    redirect('work-orders/view.php?id=' . $id);
}

if ($assignedTo > 0) {
    $stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$assignedTo]);

    if (!$stmt->fetch()) {
        flash('error', 'Choose a valid user.');
        redirect('work-orders/view.php?id=' . $id);
    }
}

$completedAt = $workOrder['completed_at'];

if ($status === 'completed' && $workOrder['status'] !== 'completed') {
    $completedAt = date('Y-m-d H:i:s');
} elseif ($status !== 'completed') {
    $completedAt = null;
}

$stmt = db()->prepare('UPDATE work_orders SET status = ?, assigned_to = ?, completed_at = ? WHERE id = ?');
$stmt->execute([$status, $assignedTo > 0 ? $assignedTo : null, $completedAt, $id]);

flash('success', 'Work order updated.');
redirect('work-orders/view.php?id=' . $id);
