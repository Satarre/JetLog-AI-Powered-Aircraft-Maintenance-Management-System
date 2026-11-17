<?php

declare(strict_types=1);

function dashboard_summary_records(): string
{
    $pdo = db();
    $lines = [];

    $total = (int) $pdo->query('SELECT COUNT(*) FROM aircraft')->fetchColumn();
    $inService = (int) $pdo->query("SELECT COUNT(*) FROM aircraft WHERE status = 'in_service'")->fetchColumn();
    $inMaintenance = (int) $pdo->query("SELECT COUNT(*) FROM aircraft WHERE status = 'maintenance'")->fetchColumn();
    $grounded = (int) $pdo->query("SELECT COUNT(*) FROM aircraft WHERE status = 'grounded'")->fetchColumn();
    $openDiscrepancies = (int) $pdo->query("SELECT COUNT(*) FROM discrepancies WHERE status <> 'closed'")->fetchColumn();
    $openWorkOrders = (int) $pdo->query("SELECT COUNT(*) FROM work_orders WHERE status IN ('open', 'in_progress')")->fetchColumn();
    $dueSoon = (int) $pdo->query(
        'SELECT COUNT(*) FROM maintenance_schedule WHERE next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
    )->fetchColumn();

    $lines[] = "Aircraft: {$total} total, {$inService} in service, {$inMaintenance} in maintenance, {$grounded} grounded.";
    $lines[] = "Open discrepancies: {$openDiscrepancies}. Open work orders: {$openWorkOrders}. Tasks due within 30 days: {$dueSoon}.";

    $items = $pdo->query(
        'SELECT s.title, s.next_due_date, a.registration
         FROM maintenance_schedule s
         JOIN aircraft a ON a.id = s.aircraft_id
         WHERE s.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY s.next_due_date
         LIMIT 8'
    )->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Due {$item['next_due_date']}: {$item['registration']} {$item['title']}.";
    }

    $items = $pdo->query(
        "SELECT d.title, d.severity, d.status, a.registration
         FROM discrepancies d
         JOIN aircraft a ON a.id = d.aircraft_id
         WHERE d.status <> 'closed'
         ORDER BY FIELD(d.severity, 'high', 'medium', 'low'), d.reported_at DESC
         LIMIT 8"
    )->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Open discrepancy: {$item['registration']} {$item['title']}, severity {$item['severity']}, status {$item['status']}.";
    }

    $items = $pdo->query(
        'SELECT l.entry_date, l.description, l.performed_by, a.registration
         FROM logbook_entries l
         JOIN aircraft a ON a.id = l.aircraft_id
         ORDER BY l.entry_date DESC, l.id DESC
         LIMIT 5'
    )->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Logbook {$item['entry_date']}: {$item['registration']} {$item['description']} By {$item['performed_by']}.";
    }

    return implode("\n", $lines);
}

function aircraft_summary_records(int $aircraftId): string
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM aircraft WHERE id = ?');
    $stmt->execute([$aircraftId]);
    $aircraft = $stmt->fetch();

    if (!$aircraft) {
        return '';
    }

    $lines = [];
    $lines[] = "Aircraft {$aircraft['registration']}, {$aircraft['aircraft_type']}, base {$aircraft['base']}, status {$aircraft['status']}, total hours {$aircraft['total_hours']}.";

    $stmt = $pdo->prepare(
        'SELECT title, severity, status, reported_at
         FROM discrepancies
         WHERE aircraft_id = ?
         ORDER BY reported_at DESC
         LIMIT 8'
    );
    $stmt->execute([$aircraftId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Discrepancy: {$item['title']}, severity {$item['severity']}, status {$item['status']}.";
    }

    $stmt = $pdo->prepare(
        'SELECT wo_number, title, priority, status
         FROM work_orders
         WHERE aircraft_id = ?
         ORDER BY opened_at DESC
         LIMIT 8'
    );
    $stmt->execute([$aircraftId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Work order {$item['wo_number']}: {$item['title']}, priority {$item['priority']}, status {$item['status']}.";
    }

    $stmt = $pdo->prepare(
        'SELECT task_code, title, next_due_date
         FROM maintenance_schedule
         WHERE aircraft_id = ?
         ORDER BY next_due_date
         LIMIT 8'
    );
    $stmt->execute([$aircraftId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Scheduled {$item['task_code']} {$item['title']}, next due {$item['next_due_date']}.";
    }

    $stmt = $pdo->prepare(
        'SELECT entry_date, entry_type, description, performed_by
         FROM logbook_entries
         WHERE aircraft_id = ?
         ORDER BY entry_date DESC, id DESC
         LIMIT 8'
    );
    $stmt->execute([$aircraftId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $lines[] = "Logbook {$item['entry_date']} ({$item['entry_type']}): {$item['description']} By {$item['performed_by']}.";
    }

    return implode("\n", $lines);
}

function logbook_summary_records(?int $aircraftId): string
{
    $pdo = db();
    $lines = [];

    if ($aircraftId !== null) {
        $stmt = $pdo->prepare('SELECT registration FROM aircraft WHERE id = ?');
        $stmt->execute([$aircraftId]);
        $aircraft = $stmt->fetch();

        if (!$aircraft) {
            return '';
        }

        $lines[] = "Logbook entries for {$aircraft['registration']}.";

        $stmt = $pdo->prepare(
            'SELECT l.entry_date, l.entry_type, l.description, l.performed_by, l.flight_hours, a.registration
             FROM logbook_entries l
             JOIN aircraft a ON a.id = l.aircraft_id
             WHERE l.aircraft_id = ?
             ORDER BY l.entry_date DESC, l.id DESC
             LIMIT 30'
        );
        $stmt->execute([$aircraftId]);
    } else {
        $lines[] = 'Logbook entries for all aircraft.';

        $stmt = $pdo->query(
            'SELECT l.entry_date, l.entry_type, l.description, l.performed_by, l.flight_hours, a.registration
             FROM logbook_entries l
             JOIN aircraft a ON a.id = l.aircraft_id
             ORDER BY l.entry_date DESC, l.id DESC
             LIMIT 30'
        );
    }

    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $lines[] = "{$item['entry_date']} {$item['registration']} {$item['entry_type']}: {$item['description']} By {$item['performed_by']}.";
    }

    return implode("\n", $lines);
}
