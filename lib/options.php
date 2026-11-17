<?php

declare(strict_types=1);

function aircraft_statuses(): array
{
    return ['in_service', 'maintenance', 'grounded'];
}

function discrepancy_severities(): array
{
    return ['low', 'medium', 'high'];
}

function discrepancy_statuses(): array
{
    return ['open', 'in_progress', 'closed'];
}

function work_order_priorities(): array
{
    return ['low', 'medium', 'high'];
}

function work_order_statuses(): array
{
    return ['open', 'in_progress', 'completed', 'cancelled'];
}

function logbook_types(): array
{
    return ['inspection', 'repair', 'component', 'note'];
}

function task_types(): array
{
    return ['inspection', 'check'];
}

function option_label(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
