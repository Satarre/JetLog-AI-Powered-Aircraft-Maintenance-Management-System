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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $result = validate_aircraft($_POST, $id);
    $aircraft = array_merge($aircraft, $result['data']);
    $errors = $result['errors'];

    if ($errors === []) {
        $stmt = db()->prepare(
            'UPDATE aircraft
             SET registration = ?, aircraft_type = ?, msn = ?, base = ?, status = ?, total_hours = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $aircraft['registration'],
            $aircraft['aircraft_type'],
            $aircraft['msn'],
            $aircraft['base'],
            $aircraft['status'],
            $aircraft['total_hours'],
            $id,
        ]);

        flash('success', 'Aircraft updated.');
        redirect('aircraft/view.php?id=' . $id);
    }
}

$formAction = url('aircraft/edit.php?id=' . $id);
$submitLabel = 'Save changes';

$pageTitle = 'Edit ' . $aircraft['registration'];
$active = 'aircraft';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <?php require __DIR__ . '/form.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
