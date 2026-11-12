<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/central_sync.php';

$payload = lifecycle_central_payload([
    'id' => 42,
    'name' => 'Generator',
    'brand' => 'Example',
    'condition' => 'Good',
    'status' => 'Serviceable',
    'acquisition_cost' => '12000.00',
    'cost_type' => 'Purchase',
    'usage_schedule' => 'Daily',
    'image_url' => '../uploads/private.jpg',
    'remarks' => 'contains personal notes',
], 'equipment');

if (($payload['id'] ?? null) !== '42' || ($payload['entity_type'] ?? '') !== 'equipment'
    || ($payload['lifecycle_status'] ?? '') !== 'Serviceable' || ($payload['cost'] ?? '') !== '12000.00'
    || ($payload['basis'] ?? '') !== 'Purchase' || ($payload['schedule'] ?? '') !== 'Daily'
    || isset($payload['image_url']) || isset($payload['remarks']) || empty($payload['source_updated_at'])) {
    fwrite(STDERR, "LifeCycleTrack central payload mapping/sanitization failed.\n");
    exit(1);
}

echo "All LifeCycleTrack Central sync payload checks passed.\n";
