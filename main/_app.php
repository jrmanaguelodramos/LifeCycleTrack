<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: {$url}");
    exit;
}

function require_login(): void {
    if (empty($_SESSION['access_token'])) {
        redirect('../auth/login.php');
    }
}

function current_user_label(): string {
    return (string)(
        $_SESSION['user']['email']
        ?? $_SESSION['user']['user_metadata']['full_name']
        ?? 'System User'
    );
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('Invalid request token. Please refresh the page and try again.');
    }
}

function condition_class(string $condition): string {
    return match (strtolower($condition)) {
        'good' => 'good',
        'fair' => 'fair',
        'poor' => 'poor',
        default => 'fair',
    };
}

function condition_badge(?string $condition): string {
    $condition = $condition ?: 'Unknown';
    $class = condition_class($condition);
    return '<span class="condition ' . e($class) . '"><span></span>' . e($condition) . '</span>';
}

function log_activity(
    PDO $pdo,
    string $entityType,
    int $entityId,
    string $action,
    string $details = ''
): void {
    if ($entityType === 'facility') {
        $sql = 'INSERT INTO activity_log
                    (facility_id, action, details, actor)
                VALUES (:id, :action, :details, :actor)';
    } elseif ($entityType === 'equipment') {
        $sql = 'INSERT INTO activity_log
                    (equipment_id, action, details, actor)
                VALUES (:id, :action, :details, :actor)';
    } else {
        throw new InvalidArgumentException('Unknown entity type.');
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $entityId,
        ':action' => $action,
        ':details' => $details,
        ':actor' => current_user_label(),
    ]);
}

function fetch_facilities(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    $sql = 'SELECT *
              FROM facilities
             WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (
            name ILIKE :search
            OR location ILIKE :search
            OR CAST(id AS TEXT) ILIKE :search
        )';
        $params[':search'] = '%' . $search . '%';
    }

    if ($condition !== '') {
        $sql .= ' AND condition = :condition';
        $params[':condition'] = $condition;
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_equipment(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    $sql = 'SELECT e.*, f.name AS facility_name, f.location AS facility_location
              FROM equipment AS e
              LEFT JOIN facilities AS f ON f.id = e.facility_id
             WHERE 1 = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (
            e.name ILIKE :search
            OR e.location ILIKE :search
            OR e.brand ILIKE :search
            OR e.model ILIKE :search
            OR e.asset_tag ILIKE :search
            OR f.name ILIKE :search
            OR CAST(e.id AS TEXT) ILIKE :search
        )';
        $params[':search'] = '%' . $search . '%';
    }

    if ($condition !== '') {
        $sql .= ' AND e.condition = :condition';
        $params[':condition'] = $condition;
    }

    $sql .= ' ORDER BY e.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function facility_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        'SELECT * FROM facilities WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function equipment_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        'SELECT e.*, f.name AS facility_name, f.location AS facility_location
           FROM equipment AS e
           LEFT JOIN facilities AS f ON f.id = e.facility_id
          WHERE e.id = :id'
    );
    $stmt->execute([':id' => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function save_facility(PDO $pdo, array $data): int {
    $name = trim((string)($data['name'] ?? ''));
    $condition = (string)($data['condition'] ?? '');

    if ($name === '' || !in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
        throw new InvalidArgumentException('Facility name and condition are required.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO facilities
            (name, location, category, date_acquired, acquisition_year,
             is_estimated_year, condition, status, remarks, image_url)
         VALUES
            (:name, :location, :category, :date_acquired, :acquisition_year,
             :is_estimated_year, :condition, :status, :remarks, :image_url)
         RETURNING id'
    );

    $stmt->execute([
        ':name' => $name,
        ':location' => trim((string)($data['location'] ?? '')),
        ':category' => trim((string)($data['category'] ?? '')) ?: null,
        ':date_acquired' => ($data['date_acquired'] ?? '') ?: null,
        ':acquisition_year' => ($data['acquisition_year'] ?? '') !== ''
            ? (int)$data['acquisition_year']
            : null,
        ':is_estimated_year' => !empty($data['is_estimated_year']),
        ':condition' => $condition,
        ':status' => trim((string)($data['status'] ?? '')) ?: null,
        ':remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
        ':image_url' => trim((string)($data['image_url'] ?? '')) ?: null,
    ]);

    $id = (int)$stmt->fetchColumn();
    log_activity($pdo, 'facility', $id, 'Added Facility', $name);

    return $id;
}

function save_equipment(PDO $pdo, array $data): int {
    $name = trim((string)($data['name'] ?? ''));
    $location = trim((string)($data['location'] ?? ''));
    $condition = (string)($data['condition'] ?? '');

    if ($name === '' || !in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
        throw new InvalidArgumentException('Equipment name and condition are required.');
    }

    $facilityId = ($data['facility_id'] ?? '') !== ''
        ? (int)$data['facility_id']
        : null;

    $stmt = $pdo->prepare(
        'INSERT INTO equipment
            (facility_id, name, asset_tag, brand, model, category, location,
             date_acquired, acquisition_year, is_estimated_year, condition,
             status, lifecycle_stage, useful_life_years, acquisition_cost,
             replacement_cost_estimate, criticality, warranty_expiry,
             department, quantity, cost_type, usage_schedule, hours_per_day,
             details_name, remarks, image_url)
         VALUES
            (:facility_id, :name, :asset_tag, :brand, :model, :category, :location,
             :date_acquired, :acquisition_year, :is_estimated_year, :condition,
             :status, :lifecycle_stage, :useful_life_years, :acquisition_cost,
             :replacement_cost_estimate, :criticality, :warranty_expiry,
             :department, :quantity, :cost_type, :usage_schedule, :hours_per_day,
             :details_name, :remarks, :image_url)
         RETURNING id'
    );

    $stmt->execute([
        ':facility_id' => $facilityId,
        ':name' => $name,
        ':asset_tag' => trim((string)($data['asset_tag'] ?? '')) ?: null,
        ':brand' => trim((string)($data['brand'] ?? '')) ?: null,
        ':model' => trim((string)($data['model'] ?? '')) ?: null,
        ':category' => trim((string)($data['category'] ?? '')) ?: null,
        ':location' => $location,
        ':date_acquired' => ($data['date_acquired'] ?? '') ?: null,
        ':acquisition_year' => ($data['acquisition_year'] ?? '') !== ''
            ? (int)$data['acquisition_year']
            : null,
        ':is_estimated_year' => !empty($data['is_estimated_year']),
        ':condition' => $condition,
        ':status' => trim((string)($data['status'] ?? '')) ?: null,
        ':lifecycle_stage' => trim((string)($data['lifecycle_stage'] ?? '')) ?: null,
        ':useful_life_years' => ($data['useful_life_years'] ?? '') !== ''
            ? (float)$data['useful_life_years']
            : null,
        ':acquisition_cost' => ($data['acquisition_cost'] ?? '') !== ''
            ? (float)$data['acquisition_cost']
            : null,
        ':replacement_cost_estimate' => ($data['replacement_cost_estimate'] ?? '') !== ''
            ? (float)$data['replacement_cost_estimate']
            : null,
        ':criticality' => trim((string)($data['criticality'] ?? '')) ?: null,
        ':warranty_expiry' => ($data['warranty_expiry'] ?? '') ?: null,
        ':department' => trim((string)($data['department'] ?? '')) ?: null,
        ':quantity' => max(1, (int)($data['quantity'] ?? 1)),
        ':cost_type' => trim((string)($data['cost_type'] ?? '')) ?: null,
        ':usage_schedule' => trim((string)($data['usage_schedule'] ?? '')) ?: null,
        ':hours_per_day' => ($data['hours_per_day'] ?? '') !== ''
            ? (float)$data['hours_per_day']
            : null,
        ':details_name' => trim((string)($data['details_name'] ?? '')) ?: null,
        ':remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
        ':image_url' => trim((string)($data['image_url'] ?? '')) ?: null,
    ]);

    $id = (int)$stmt->fetchColumn();
    log_activity($pdo, 'equipment', $id, 'Added Equipment', $name);

    return $id;
}

function update_facility_condition(
    PDO $pdo,
    int $facilityId,
    string $condition,
    string $status,
    string $notes = ''
): void {
    if (!in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
        throw new InvalidArgumentException('Invalid facility condition.');
    }

    $facility = facility_by_id($pdo, $facilityId);
    if (!$facility) {
        throw new RuntimeException('Facility not found.');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE facilities
                SET condition = :condition, status = :status, updated_at = NOW()
              WHERE id = :id'
        );
        $stmt->execute([
            ':condition' => $condition,
            ':status' => $status,
            ':id' => $facilityId,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO facility_condition_assessments
                (facility_id, condition, status, notes, assessed_by)
             VALUES (:id, :condition, :status, :notes, :actor)'
        );
        $stmt->execute([
            ':id' => $facilityId,
            ':condition' => $condition,
            ':status' => $status,
            ':notes' => trim($notes) ?: null,
            ':actor' => current_user_label(),
        ]);

        log_activity(
            $pdo,
            'facility',
            $facilityId,
            'Updated Condition',
            $facility['name'] . ' → ' . $condition
        );

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function update_equipment_condition(
    PDO $pdo,
    int $equipmentId,
    string $condition,
    string $status,
    string $notes = ''
): void {
    if (!in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
        throw new InvalidArgumentException('Invalid equipment condition.');
    }

    $item = equipment_by_id($pdo, $equipmentId);
    if (!$item) {
        throw new RuntimeException('Equipment not found.');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE equipment
                SET condition = :condition, status = :status, updated_at = NOW()
              WHERE id = :id'
        );
        $stmt->execute([
            ':condition' => $condition,
            ':status' => $status,
            ':id' => $equipmentId,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO equipment_condition_assessments
                (equipment_id, condition, status, notes, assessed_by)
             VALUES (:id, :condition, :status, :notes, :actor)'
        );
        $stmt->execute([
            ':id' => $equipmentId,
            ':condition' => $condition,
            ':status' => $status,
            ':notes' => trim($notes) ?: null,
            ':actor' => current_user_label(),
        ]);

        log_activity(
            $pdo,
            'equipment',
            $equipmentId,
            'Updated Condition',
            $item['name'] . ' → ' . $condition
        );

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
/**
 * Return registered facilities.
 */
function facilities_list(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    return fetch_facilities($pdo, $search, $condition);
}

/**
 * Return registered equipment.
 */
function equipments_list(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    return fetch_equipment($pdo, $search, $condition);
}

/**
 * Return facilities with their latest condition assessment.
 */
function facility_condition(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    return fetch_condition_assets(
        $pdo,
        'facility',
        $search,
        $condition
    );
}

/**
 * Return equipment with its latest condition assessment.
 */
function equipment_condition(
    PDO $pdo,
    string $search = '',
    string $condition = ''
): array {
    return fetch_condition_assets(
        $pdo,
        'equipment',
        $search,
        $condition
    );
}

/**
 * Shared query for condition-assessment pages.
 */
function fetch_condition_assets(
    PDO $pdo,
    string $type,
    string $search = '',
    string $condition = ''
): array {
    $entities = [
        'facility' => [
            'table' => 'facilities',
            'history' => 'facility_condition_assessments',
            'foreign_key' => 'facility_id',
        ],
        'equipment' => [
            'table' => 'equipment',
            'history' => 'equipment_condition_assessments',
            'foreign_key' => 'equipment_id',
        ],
    ];

    if (!isset($entities[$type])) {
        throw new InvalidArgumentException('Invalid entity type.');
    }

    $entity = $entities[$type];

    $sql = "
        SELECT
            a.id,
            a.name,
            a.location,
            a.condition,
            COALESCE(ca.status, a.status) AS status,
            a.remarks,
            a.image_url,
            a.updated_at,
            ca.notes AS assessment_notes,
            ca.assessed_by,
            ca.assessed_at
        FROM {$entity['table']} AS a
        LEFT JOIN LATERAL (
            SELECT status, notes, assessed_by, assessed_at
            FROM {$entity['history']}
            WHERE {$entity['foreign_key']} = a.id
            ORDER BY assessed_at DESC, id DESC
            LIMIT 1
        ) AS ca ON TRUE
        WHERE 1 = 1
    ";

    $params = [];

    if ($search !== '') {
        $sql .= "
            AND (
                CAST(a.id AS TEXT) ILIKE :search_id
                OR a.name ILIKE :search_name
                OR a.location ILIKE :search_location
            )
        ";

        $pattern = '%' . $search . '%';
        $params[':search_id'] = $pattern;
        $params[':search_name'] = $pattern;
        $params[':search_location'] = $pattern;
    }

    if ($condition !== '') {
        $sql .= ' AND a.condition = :condition';
        $params[':condition'] = $condition;
    }

    $sql .= ' ORDER BY a.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}