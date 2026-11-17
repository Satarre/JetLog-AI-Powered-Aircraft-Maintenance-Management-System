<?php

declare(strict_types=1);

function post_string(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function valid_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function valid_choice(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

function valid_number(string $value): bool
{
    return $value !== '' && is_numeric($value) && (float) $value >= 0;
}

function show_errors(array $errors): void
{
    if ($errors === []) {
        return;
    }

    echo '<div class="alert alert-error"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></div>';
}
