<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftOptions = db()->query('SELECT id, registration, aircraft_type FROM aircraft ORDER BY registration')->fetchAll();

$data = [
    'aircraft_id' => (int) ($_GET['aircraft_id'] ?? 0),
    'title' => '',
    'description' => '',
    'severity' => 'medium',
    'status' => 'open',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'aircraft_id' => (int) ($_POST['aircraft_id'] ?? 0),
        'title' => post_string('title'),
        'description' => post_string('description'),
        'severity' => post_string('severity'),
        'status' => 'open',
    ];

    if ($data['aircraft_id'] <= 0) {
        $errors['aircraft_id'] = 'Choose an aircraft.';
    } else {
        $stmt = db()->prepare('SELECT id FROM aircraft WHERE id = ?');
        $stmt->execute([$data['aircraft_id']]);
        if (!$stmt->fetch()) {
            $errors['aircraft_id'] = 'Choose a valid aircraft.';
        }
    }

    if ($data['title'] === '') {
        $errors['title'] = 'Enter a title.';
    } elseif (mb_strlen($data['title']) > 150) {
        $errors['title'] = 'Keep the title under 150 characters.';
    }

    if ($data['description'] === '') {
        $errors['description'] = 'Enter a description.';
    }

    if (!valid_choice($data['severity'], discrepancy_severities())) {
        $errors['severity'] = 'Choose a valid severity.';
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO discrepancies (aircraft_id, reported_by, title, description, severity, status, reported_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['aircraft_id'],
            current_user()['id'],
            $data['title'],
            $data['description'],
            $data['severity'],
            'open',
        ]);

        flash('success', 'Discrepancy reported.');
        redirect('discrepancies/index.php?aircraft_id=' . $data['aircraft_id']);
    }
}

$formAction = url('discrepancies/create.php');
$submitLabel = 'Report discrepancy';

$pageTitle = 'Report discrepancy';
$active = 'discrepancies';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <?php require __DIR__ . '/form.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
