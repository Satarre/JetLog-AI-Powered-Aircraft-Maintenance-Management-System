<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraft = [
    'registration' => '',
    'aircraft_type' => '',
    'msn' => '',
    'base' => '',
    'status' => 'in_service',
    'total_hours' => '0',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $result = validate_aircraft($_POST);
    $aircraft = $result['data'];
    $errors = $result['errors'];

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO aircraft (registration, aircraft_type, msn, base, status, total_hours)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $aircraft['registration'],
            $aircraft['aircraft_type'],
            $aircraft['msn'],
            $aircraft['base'],
            $aircraft['status'],
            $aircraft['total_hours'],
        ]);

        $id = (int) db()->lastInsertId();
        flash('success', 'Aircraft added.');
        redirect('aircraft/view.php?id=' . $id);
    }
}

$formAction = url('aircraft/create.php');
$submitLabel = 'Add aircraft';

$pageTitle = 'Add aircraft';
$active = 'aircraft';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <?php require __DIR__ . '/form.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
