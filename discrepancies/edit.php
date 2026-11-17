<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT d.*, a.registration
     FROM discrepancies d
     JOIN aircraft a ON a.id = d.aircraft_id
     WHERE d.id = ?'
);
$stmt->execute([$id]);
$discrepancy = $stmt->fetch();

if (!$discrepancy) {
    http_response_code(404);
    $pageTitle = 'Discrepancy not found';
    $active = 'discrepancies';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="panel"><p class="empty">Discrepancy not found.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$errors = [];
$data = $discrepancy;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'aircraft_id' => $discrepancy['aircraft_id'],
        'registration' => $discrepancy['registration'],
        'title' => post_string('title'),
        'description' => post_string('description'),
        'severity' => post_string('severity'),
        'status' => post_string('status'),
        'closed_at' => $discrepancy['closed_at'],
    ];

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

    if (!valid_choice($data['status'], discrepancy_statuses())) {
        $errors['status'] = 'Choose a valid status.';
    }

    if ($errors === []) {
        if ($data['status'] === 'closed' && $discrepancy['status'] !== 'closed') {
            $data['closed_at'] = date('Y-m-d H:i:s');
        } elseif ($data['status'] !== 'closed') {
            $data['closed_at'] = null;
        }

        $stmt = db()->prepare(
            'UPDATE discrepancies
             SET title = ?, description = ?, severity = ?, status = ?, closed_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['severity'],
            $data['status'],
            $data['closed_at'],
            $id,
        ]);

        flash('success', 'Discrepancy updated.');
        redirect('discrepancies/index.php');
    }
}

$aircraftOptions = db()->query('SELECT id, registration, aircraft_type FROM aircraft ORDER BY registration')->fetchAll();

$formAction = url('discrepancies/edit.php?id=' . $id);
$submitLabel = 'Save changes';
$lockAircraft = true;
$showStatus = true;

$pageTitle = 'Edit discrepancy';
$active = 'discrepancies';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <p class="muted">Reported <?= e(fmt_datetime($discrepancy['reported_at'])) ?></p>
    <?php require __DIR__ . '/form.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
