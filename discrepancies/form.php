<?php
if (!isset($data, $formAction, $aircraftOptions)) {
    http_response_code(404);
    exit('Not found.');
}
$lockAircraft = $lockAircraft ?? false;
$showStatus = $showStatus ?? false;
?>
<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>

    <?php if ($lockAircraft): ?>
        <input type="hidden" name="aircraft_id" value="<?= (int) $data['aircraft_id'] ?>">
        <div class="form-row">
            <label>Aircraft</label>
            <div class="muted mono"><?= e($data['registration']) ?></div>
        </div>
    <?php else: ?>
        <div class="form-row">
            <label for="aircraft_id">Aircraft</label>
            <select id="aircraft_id" name="aircraft_id" required>
                <option value="">Select aircraft</option>
                <?php foreach ($aircraftOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"<?= (int) $data['aircraft_id'] === (int) $option['id'] ? ' selected' : '' ?>>
                        <?= e($option['registration']) ?> (<?= e($option['aircraft_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['aircraft_id'])): ?>
                <div class="error-text"><?= e($errors['aircraft_id']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-row">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e((string) $data['title']) ?>" maxlength="150" required>
        <?php if (isset($errors['title'])): ?>
            <div class="error-text"><?= e($errors['title']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="description">Description</label>
        <textarea id="description" name="description" required><?= e((string) $data['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?>
            <div class="error-text"><?= e($errors['description']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label for="severity">Severity</label>
        <select id="severity" name="severity">
            <?php foreach (discrepancy_severities() as $option): ?>
                <option value="<?= e($option) ?>"<?= $data['severity'] === $option ? ' selected' : '' ?>>
                    <?= e(option_label($option)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['severity'])): ?>
            <div class="error-text"><?= e($errors['severity']) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($showStatus): ?>
        <div class="form-row">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (discrepancy_statuses() as $option): ?>
                    <option value="<?= e($option) ?>"<?= $data['status'] === $option ? ' selected' : '' ?>>
                        <?= e(option_label($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['status'])): ?>
                <div class="error-text"><?= e($errors['status']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a class="btn btn-secondary" href="<?= url('discrepancies/index.php') ?>">Cancel</a>
    </div>
</form>
