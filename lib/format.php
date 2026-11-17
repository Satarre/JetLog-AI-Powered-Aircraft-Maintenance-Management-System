<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function pill(string $status): string
{
    $class = 'status-' . str_replace('_', '-', $status);

    return '<span class="status ' . e($class) . '">' . e(status_label($status)) . '</span>';
}

function fmt_date(?string $date): string
{
    if (!$date) {
        return 'None';
    }

    return date('j M Y', strtotime($date));
}

function fmt_datetime(?string $value): string
{
    if (!$value) {
        return 'None';
    }

    return date('j M Y H:i', strtotime($value));
}

function due_state(string $nextDue): string
{
    $today = new DateTimeImmutable('today');
    $due = new DateTimeImmutable($nextDue);

    if ($due < $today) {
        return 'overdue';
    }

    if ($due <= $today->modify('+30 days')) {
        return 'due_soon';
    }

    return 'upcoming';
}

function due_pill(string $nextDue): string
{
    $state = due_state($nextDue);

    return '<span class="status status-' . str_replace('_', '-', $state) . '">'
        . e(status_label($state)) . '</span>';
}

function excerpt(?string $text, int $limit = 110): string
{
    $text = trim((string) $text);

    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return mb_substr($text, 0, $limit) . '...';
}
