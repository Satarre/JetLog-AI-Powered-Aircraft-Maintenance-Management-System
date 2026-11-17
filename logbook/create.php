<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftOptions = db()->query('SELECT id, registration, aircraft_type FROM aircraft ORDER BY registration')->fetchAll();
$workOrderOptions = db()->query(
    'SELECT wo.id, wo.wo_number, wo.aircraft_id, a.registration
     FROM work_orders wo
     JOIN aircraft a ON a.id = wo.aircraft_id
     ORDER BY wo.id DESC'
)->fetchAll();

$user = current_user();
$data = [
    'aircraft_id' => (int) ($_GET['aircraft_id'] ?? 0),
    'work_order_id' => (int) ($_GET['work_order_id'] ?? 0),
    'entry_date' => date('Y-m-d'),
    'entry_type' => 'note',
    'description' => '',
    'performed_by' => $user['full_name'],
    'flight_hours' => '',
];
$errors = [];

if ($data['work_order_id'] > 0) {
    $stmt = db()->prepare('SELECT aircraft_id FROM work_orders WHERE id = ?');
    $stmt->execute([$data['work_order_id']]);
    $workOrder = $stmt->fetch();

    if ($workOrder) {
        $data['aircraft_id'] = (int) $workOrder['aircraft_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'aircraft_id' => (int) ($_POST['aircraft_id'] ?? 0),
        'work_order_id' => (int) ($_POST['work_order_id'] ?? 0),
        'entry_date' => post_string('entry_date'),
        'entry_type' => post_string('entry_type'),
        'description' => post_string('description'),
        'performed_by' => post_string('performed_by'),
        'flight_hours' => post_string('flight_hours'),
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

    if ($data['work_order_id'] > 0) {
        $stmt = db()->prepare('SELECT aircraft_id FROM work_orders WHERE id = ?');
        $stmt->execute([$data['work_order_id']]);
        $workOrder = $stmt->fetch();

        if (!$workOrder) {
            $errors['work_order_id'] = 'Choose a valid work order.';
        } elseif ((int) $workOrder['aircraft_id'] !== $data['aircraft_id']) {
            $errors['work_order_id'] = 'The work order belongs to another aircraft.';
        }
    }

    if (!valid_date($data['entry_date'])) {
        $errors['entry_date'] = 'Enter a valid date.';
    }

    if (!valid_choice($data['entry_type'], logbook_types())) {
        $errors['entry_type'] = 'Choose a valid entry type.';
    }

    if ($data['description'] === '') {
        $errors['description'] = 'Enter a description.';
    }

    if ($data['performed_by'] === '') {
        $errors['performed_by'] = 'Enter who performed the work.';
    } elseif (mb_strlen($data['performed_by']) > 100) {
        $errors['performed_by'] = 'Keep the name under 100 characters.';
    }

    if ($data['flight_hours'] !== '' && !valid_number($data['flight_hours'])) {
        $errors['flight_hours'] = 'Enter flight hours as a number.';
    }

    if ($errors === []) {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO logbook_entries
                    (aircraft_id, work_order_id, created_by, entry_date, entry_type, description, performed_by, flight_hours)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['aircraft_id'],
                $data['work_order_id'] > 0 ? $data['work_order_id'] : null,
                $user['id'],
                $data['entry_date'],
                $data['entry_type'],
                $data['description'],
                $data['performed_by'],
                $data['flight_hours'] !== '' ? $data['flight_hours'] : null,
            ]);

            if ($data['flight_hours'] !== '') {
                $stmt = $pdo->prepare('UPDATE aircraft SET total_hours = GREATEST(total_hours, ?) WHERE id = ?');
                $stmt->execute([$data['flight_hours'], $data['aircraft_id']]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        flash('success', 'Logbook entry added.');
        redirect('logbook/index.php?aircraft_id=' . $data['aircraft_id']);
    }
}

$pageTitle = 'Add logbook entry';
$active = 'logbook';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <form method="post" action="<?= url('logbook/create.php') ?>">
        <?= csrf_field() ?>

        <div class="form-row">
            <label for="aircraft_id">Aircraft</label>
            <select id="aircraft_id" name="aircraft_id" required>
                <option value="">Select aircraft</option>
                <?php foreach ($aircraftOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $data['aircraft_id'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['registration']) ?> (<?= e($option['aircraft_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['aircraft_id'])): ?>
                <div class="error-text"><?= e($errors['aircraft_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="work_order_id">Work order</label>
            <select id="work_order_id" name="work_order_id">
                <option value="0">None</option>
                <?php foreach ($workOrderOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $data['work_order_id'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['wo_number']) ?> (<?= e($option['registration']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['work_order_id'])): ?>
                <div class="error-text"><?= e($errors['work_order_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="entry_date">Date</label>
            <input type="date" id="entry_date" name="entry_date" value="<?= e($data['entry_date']) ?>" required>
            <?php if (isset($errors['entry_date'])): ?>
                <div class="error-text"><?= e($errors['entry_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="entry_type">Type</label>
            <select id="entry_type" name="entry_type">
                <?php foreach (logbook_types() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $data['entry_type'] === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['entry_type'])): ?>
                <div class="error-text"><?= e($errors['entry_type']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="description">Entry</label>
            <textarea id="description" name="description" required><?= e($data['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <div class="error-text"><?= e($errors['description']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="performed_by">Performed by</label>
            <input type="text" id="performed_by" name="performed_by" value="<?= e($data['performed_by']) ?>" maxlength="100" required>
            <?php if (isset($errors['performed_by'])): ?>
                <div class="error-text"><?= e($errors['performed_by']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="flight_hours">Flight hours</label>
            <input type="number" id="flight_hours" name="flight_hours" step="0.1" min="0" value="<?= e((string) $data['flight_hours']) ?>">
            <span class="hint">Updates the aircraft total when higher.</span>
            <?php if (isset($errors['flight_hours'])): ?>
                <div class="error-text"><?= e($errors['flight_hours']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Add entry</button>
            <a class="btn btn-secondary" href="<?= url('logbook/index.php') ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
