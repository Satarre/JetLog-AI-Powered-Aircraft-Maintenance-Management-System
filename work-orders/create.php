<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftOptions = db()->query('SELECT id, registration, aircraft_type FROM aircraft ORDER BY registration')->fetchAll();
$userOptions = db()->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();
$discrepancyOptions = db()->query(
    "SELECT d.id, d.title, d.aircraft_id, a.registration
     FROM discrepancies d
     JOIN aircraft a ON a.id = d.aircraft_id
     WHERE d.status <> 'closed'
     ORDER BY d.reported_at DESC"
)->fetchAll();

$data = [
    'aircraft_id' => (int) ($_GET['aircraft_id'] ?? 0),
    'discrepancy_id' => (int) ($_GET['discrepancy_id'] ?? 0),
    'title' => '',
    'description' => '',
    'priority' => 'medium',
    'assigned_to' => 0,
];
$errors = [];

if ($data['discrepancy_id'] > 0) {
    $stmt = db()->prepare('SELECT * FROM discrepancies WHERE id = ?');
    $stmt->execute([$data['discrepancy_id']]);
    $linked = $stmt->fetch();

    if ($linked) {
        $data['aircraft_id'] = (int) $linked['aircraft_id'];
        $data['title'] = $linked['title'];
        $data['description'] = $linked['description'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'aircraft_id' => (int) ($_POST['aircraft_id'] ?? 0),
        'discrepancy_id' => (int) ($_POST['discrepancy_id'] ?? 0),
        'title' => post_string('title'),
        'description' => post_string('description'),
        'priority' => post_string('priority'),
        'assigned_to' => (int) ($_POST['assigned_to'] ?? 0),
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

    if ($data['discrepancy_id'] > 0) {
        $stmt = db()->prepare('SELECT aircraft_id FROM discrepancies WHERE id = ?');
        $stmt->execute([$data['discrepancy_id']]);
        $discrepancy = $stmt->fetch();

        if (!$discrepancy) {
            $errors['discrepancy_id'] = 'Choose a valid discrepancy.';
        } elseif ((int) $discrepancy['aircraft_id'] !== $data['aircraft_id']) {
            $errors['discrepancy_id'] = 'The discrepancy belongs to another aircraft.';
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

    if (!valid_choice($data['priority'], work_order_priorities())) {
        $errors['priority'] = 'Choose a valid priority.';
    }

    if ($data['assigned_to'] > 0) {
        $stmt = db()->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$data['assigned_to']]);
        if (!$stmt->fetch()) {
            $errors['assigned_to'] = 'Choose a valid user.';
        }
    }

    if ($errors === []) {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO work_orders
                    (aircraft_id, discrepancy_id, assigned_to, created_by, title, description, priority, status, opened_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $data['aircraft_id'],
                $data['discrepancy_id'] > 0 ? $data['discrepancy_id'] : null,
                $data['assigned_to'] > 0 ? $data['assigned_to'] : null,
                current_user()['id'],
                $data['title'],
                $data['description'],
                $data['priority'],
                'open',
            ]);

            $id = (int) $pdo->lastInsertId();
            $number = 'WO-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare('UPDATE work_orders SET wo_number = ? WHERE id = ?');
            $stmt->execute([$number, $id]);

            if ($data['discrepancy_id'] > 0) {
                $stmt = $pdo->prepare("UPDATE discrepancies SET status = 'in_progress' WHERE id = ? AND status = 'open'");
                $stmt->execute([$data['discrepancy_id']]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        flash('success', 'Work order ' . $number . ' created.');
        redirect('work-orders/view.php?id=' . $id);
    }
}

$pageTitle = 'Create work order';
$active = 'work-orders';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <form method="post" action="<?= url('work-orders/create.php') ?>">
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
            <label for="discrepancy_id">Linked discrepancy</label>
            <select id="discrepancy_id" name="discrepancy_id">
                <option value="0">None</option>
                <?php foreach ($discrepancyOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $data['discrepancy_id'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['registration']) ?>: <?= e($option['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['discrepancy_id'])): ?>
                <div class="error-text"><?= e($errors['discrepancy_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" maxlength="150" required>
            <?php if (isset($errors['title'])): ?>
                <div class="error-text"><?= e($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= e($data['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <div class="error-text"><?= e($errors['description']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <?php foreach (work_order_priorities() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $data['priority'] === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['priority'])): ?>
                <div class="error-text"><?= e($errors['priority']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="assigned_to">Assigned to</label>
            <select id="assigned_to" name="assigned_to">
                <option value="0">Unassigned</option>
                <?php foreach ($userOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= $data['assigned_to'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['assigned_to'])): ?>
                <div class="error-text"><?= e($errors['assigned_to']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Create work order</button>
            <a class="btn btn-secondary" href="<?= url('work-orders/index.php') ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
