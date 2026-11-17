<?php

declare(strict_types=1);

/**
 * Cleans and validates aircraft form input.
 *
 * @return array{data: array, errors: array}
 */
function validate_aircraft(array $input, ?int $ignoreId = null): array
{
    $data = [
        'registration' => strtoupper(trim((string) ($input['registration'] ?? ''))),
        'aircraft_type' => trim((string) ($input['aircraft_type'] ?? '')),
        'msn' => strtoupper(trim((string) ($input['msn'] ?? ''))),
        'base' => strtoupper(trim((string) ($input['base'] ?? ''))),
        'status' => (string) ($input['status'] ?? 'in_service'),
        'total_hours' => trim((string) ($input['total_hours'] ?? '')),
    ];

    $errors = [];

    if ($data['registration'] === '') {
        $errors['registration'] = 'Enter a registration.';
    } elseif (!preg_match('/^[A-Z0-9-]{3,20}$/', $data['registration'])) {
        $errors['registration'] = 'Use 3 to 20 letters, numbers, or hyphens.';
    }

    if ($data['aircraft_type'] === '') {
        $errors['aircraft_type'] = 'Enter an aircraft type.';
    } elseif (mb_strlen($data['aircraft_type']) > 60) {
        $errors['aircraft_type'] = 'Keep the type under 60 characters.';
    }

    if ($data['msn'] === '') {
        $errors['msn'] = 'Enter the manufacturer serial number.';
    } elseif (mb_strlen($data['msn']) > 30) {
        $errors['msn'] = 'Keep the serial number under 30 characters.';
    }

    if ($data['base'] === '') {
        $errors['base'] = 'Enter a base.';
    } elseif (mb_strlen($data['base']) > 10) {
        $errors['base'] = 'Keep the base under 10 characters.';
    }

    if (!valid_choice($data['status'], aircraft_statuses())) {
        $errors['status'] = 'Choose a valid status.';
    }

    if (!valid_number($data['total_hours'])) {
        $errors['total_hours'] = 'Enter total hours as a number.';
    }

    if ($errors === []) {
        $sql = 'SELECT id FROM aircraft WHERE registration = ?';
        $params = [$data['registration']];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            $errors['registration'] = 'This registration already exists.';
        }

        $sql = 'SELECT id FROM aircraft WHERE msn = ?';
        $params = [$data['msn']];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            $errors['msn'] = 'This serial number already exists.';
        }
    }

    return ['data' => $data, 'errors' => $errors];
}

/**
 * Returns the demo photo for an aircraft type, or null when there is none.
 */
function aircraft_type_image(string $type): ?string
{
    $key = (string) preg_replace('/[^a-z0-9]/', '', strtolower($type));

    $images = [
        'a321neo' => 'assets/img/airbus_a321neo-web.jpg',
        'a330300' => 'assets/img/airbus_a330-300.jpg',
        'a350900' => 'assets/img/airbus_a350-900.jpg',
        '777300er' => 'assets/img/boeing_777-300er.jpg',
    ];

    foreach ($images as $needle => $path) {
        if (str_contains($key, $needle)) {
            return $path;
        }
    }

    return null;
}
