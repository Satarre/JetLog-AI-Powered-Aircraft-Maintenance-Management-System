<?php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../lib/summaries.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

$scope = post_string('scope');
$aircraftId = (int) ($_POST['aircraft_id'] ?? 0);

if (!in_array($scope, ['dashboard', 'aircraft', 'logbook'], true)) {
    flash('error', 'Choose a valid summary type.');
    redirect('index.php');
}

if ($scope === 'aircraft' && $aircraftId <= 0) {
    flash('error', 'Aircraft not found.');
    redirect('index.php');
}

if ($aircraftId > 0) {
    $stmt = db()->prepare('SELECT id FROM aircraft WHERE id = ?');
    $stmt->execute([$aircraftId]);

    if (!$stmt->fetch()) {
        flash('error', 'Aircraft not found.');
        redirect('index.php');
    }
}

if ($scope === 'dashboard') {
    $aircraftId = 0;
}

$target = 'index.php';

if ($scope === 'aircraft') {
    $target = 'aircraft/view.php?id=' . $aircraftId;
} elseif ($scope === 'logbook') {
    $target = 'logbook/index.php' . ($aircraftId > 0 ? '?aircraft_id=' . $aircraftId : '');
}

if (!gemini_configured()) {
    flash('error', 'Gemini API key is not set.');
    redirect($target);
}

if ($scope === 'dashboard') {
    $records = dashboard_summary_records();
} elseif ($scope === 'aircraft') {
    $records = aircraft_summary_records($aircraftId);
} else {
    $records = logbook_summary_records($aircraftId > 0 ? $aircraftId : null);
}

if (trim($records) === '') {
    flash('error', 'No records to summarize.');
    redirect($target);
}

$instruction = 'You summarize aircraft maintenance records for a demo application. '
    . 'Use only the records below. Write 3 to 5 short sentences in plain text. '
    . 'Do not use lists, headings, or markdown. '
    . 'Do not diagnose faults, recommend maintenance actions, or make airworthiness or safety judgments. '
    . 'Do not invent details.';

$result = gemini_summarize($instruction, mb_substr($records, 0, 8000));

if (!$result['ok']) {
    flash('error', $result['error']);
    redirect($target);
}

$stmt = db()->prepare(
    'INSERT INTO ai_summaries (scope, aircraft_id, summary_text, model, created_by)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
    $scope,
    $aircraftId > 0 ? $aircraftId : null,
    $result['text'],
    GEMINI_MODEL,
    current_user()['id'],
]);

flash('success', 'Summary generated.');
redirect($target);
