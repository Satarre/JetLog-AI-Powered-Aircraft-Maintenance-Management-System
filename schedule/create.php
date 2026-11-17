<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

$aircraftOptions = db()->query('SELECT id, registration, aircraft_type FROM aircraft ORDER BY registration')->fetchAll();

$data = [
    'aircraft_id' => (int) ($_GET['aircraft_id'] ?? 0),
    'task_code' => '',
    'title' => '',
    'task_type' => 'inspection',
    'interval_days' => '',
    'last_done_date' => '',
    'next_due_date' => '',
    'notes' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'aircraft_id' => (int) ($_POST['aircraft_id'] ?? 0),
        'task_code' => strtoupper(post_string('task_code')),
        'title' => post_string('title'),
        'task_type' => post_string('task_type'),
        'interval_days' => post_string('interval_days'),
        'last_done_date' => post_string('last_done_date'),
        'next_due_date' => post_string('next_due_date'),
        'notes' => post_string('notes'),
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

    if ($data['task_code'] === '') {
        $errors['task_code'] = 'Enter a task code.';
    } elseif (mb_strlen($data['task_code']) > 30) {
        $errors['task_code'] = 'Keep the task code under 30 characters.';
    }

    if ($data['title'] === '') {
        $errors['title'] = 'Enter a title.';
    } elseif (mb_strlen($data['title']) > 150) {
        $errors['title'] = 'Keep the title under 150 characters.';
    }

    if (!valid_choice($data['task_type'], task_types())) {
        $errors['task_type'] = 'Choose a valid task type.';
    }

    if (!valid_number($data['interval_days']) || (int) $data['interval_days'] <= 0) {
        $errors['interval_days'] = 'Enter the interval in days.';
    }

    if ($data['last_done_date'] !== '' && !valid_date($data['last_done_date'])) {
        $errors['last_done_date'] = 'Enter a valid date.';
    }

    if ($data['next_due_date'] === '' && $data['last_done_date'] !== '' && $errors === []) {
        $data['next_due_date'] = date(
            'Y-m-d',
            strtotime($data['last_done_date'] . ' + ' . (int) $data['interval_days'] . ' days')
        );
    }

    if ($data['next_due_date'] === '' || !valid_date($data['next_due_date'])) {
        $errors['next_due_date'] = 'Enter a next due date.';
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO maintenance_schedule
                (aircraft_id, task_code, title, task_type, interval_days, last_done_date, next_due_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['aircraft_id'],
            $data['task_code'],
            $data['title'],
            $data['task_type'],
            (int) $data['interval_days'],
            $data['last_done_date'] !== '' ? $data['last_done_date'] : null,
            $data['next_due_date'],
            $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        flash('success', 'Scheduled task added.');
        redirect('schedule/index.php?aircraft_id=' . $data['aircraft_id']);
    }
}

$pageTitle = 'Add scheduled task';
$active = 'schedule';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel panel-form">
    <form method="post" action="<?= url('schedule/create.php') ?>">
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
            <label for="task_code">Task code</label>
            <input type="text" id="task_code" name="task_code" value="<?= e($data['task_code']) ?>" maxlength="30" required>
            <?php if (isset($errors['task_code'])): ?>
                <div class="error-text"><?= e($errors['task_code']) ?></div>
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
            <label for="task_type">Type</label>
            <select id="task_type" name="task_type">
                <?php foreach (task_types() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $data['task_type'] === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['task_type'])): ?>
                <div class="error-text"><?= e($errors['task_type']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="interval_days">Interval in days</label>
            <input type="number" id="interval_days" name="interval_days" min="1" value="<?= e((string) $data['interval_days']) ?>" required>
            <?php if (isset($errors['interval_days'])): ?>
                <div class="error-text"><?= e($errors['interval_days']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="last_done_date">Last done</label>
            <input type="date" id="last_done_date" name="last_done_date" value="<?= e((string) $data['last_done_date']) ?>">
            <?php if (isset($errors['last_done_date'])): ?>
                <div class="error-text"><?= e($errors['last_done_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="next_due_date">Next due</label>
            <input type="date" id="next_due_date" name="next_due_date" value="<?= e((string) $data['next_due_date']) ?>">
            <span class="hint">Leave empty to calculate from last done and interval.</span>
            <?php if (isset($errors['next_due_date'])): ?>
                <div class="error-text"><?= e($errors['next_due_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= e($data['notes']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Add task</button>
            <a class="btn btn-secondary" href="<?= url('schedule/index.php') ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
