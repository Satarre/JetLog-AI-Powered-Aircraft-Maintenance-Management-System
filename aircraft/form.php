<?php
if (!isset($aircraft, $formAction)) {
    http_response_code(404);
    exit('Not found.');
}
?>
<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <label for="registration">Registration</label>
        <input type="text" id="registration" name="registration" value="<?= e((string) $aircraft['registration']) ?>" required>
        <?php if (isset($errors['registration'])): ?>
            <div class="error-text"><?= e($errors['registration']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="aircraft_type">Type</label>
        <input type="text" id="aircraft_type" name="aircraft_type" value="<?= e((string) $aircraft['aircraft_type']) ?>" required>
        <?php if (isset($errors['aircraft_type'])): ?>
            <div class="error-text"><?= e($errors['aircraft_type']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="msn">MSN</label>
        <input type="text" id="msn" name="msn" value="<?= e((string) $aircraft['msn']) ?>" required>
        <?php if (isset($errors['msn'])): ?>
            <div class="error-text"><?= e($errors['msn']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="base">Base</label>
        <input type="text" id="base" name="base" value="<?= e((string) $aircraft['base']) ?>" required>
        <?php if (isset($errors['base'])): ?>
            <div class="error-text"><?= e($errors['base']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach (aircraft_statuses() as $option): ?>
                <option value="<?= e($option) ?>"<?= $aircraft['status'] === $option ? ' selected' : '' ?>>
                    <?= e(option_label($option)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['status'])): ?>
            <div class="error-text"><?= e($errors['status']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="total_hours">Total hours</label>
        <input type="number" id="total_hours" name="total_hours" step="0.1" min="0" value="<?= e((string) $aircraft['total_hours']) ?>" required>
        <?php if (isset($errors['total_hours'])): ?>
            <div class="error-text"><?= e($errors['total_hours']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a class="btn btn-secondary" href="<?= url('aircraft/index.php') ?>">Cancel</a>
    </div>
</form>
