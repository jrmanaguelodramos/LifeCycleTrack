<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/central_sync.php';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
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

function asset_type_label(string $type): string {
    return ucfirst($type);
}

function log_activity(PDO $pdo, ?int $assetId, string $action, string $details = ''): void {
    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (asset_id, action, details, actor) VALUES (:asset_id, :action, :details, :actor)'
    );
    $stmt->execute([
        ':asset_id' => $assetId,
        ':action' => $action,
        ':details' => $details,
        ':actor' => current_user_label(),
    ]);
}

function fetch_equipment(PDO $pdo, string $search = '', string $condition = ''): array
{
    $sql = 'SELECT e.*, f.name AS facility_name
            FROM equipment e
            LEFT JOIN facilities f ON f.id = e.facility_id';
    $params = [];

    $filters = [];

    if ($search !== '') {
        $filters[] = '(e.name ILIKE :name_search OR e.brand ILIKE :brand_search OR e.model ILIKE :model_search)';
        $pattern = '%' . $search . '%';
        $params['name_search'] = $pattern;
        $params['brand_search'] = $pattern;
        $params['model_search'] = $pattern;
    }

    if ($condition !== '') {
        $filters[] = 'e.condition = :condition';
        $params['condition'] = $condition;
    }

    if ($filters) {
        $sql .= ' WHERE ' . implode(' AND ', $filters);
    }

    $sql .= ' ORDER BY e.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_equipment_page(PDO $pdo, string $search, string $condition, string $floor, string $location, string $power, int $page, int $pageSize = 25): array
{
    $filters = [];
    $params = [];
    if ($search !== '') {
        $filters[] = '(e.name ILIKE :search_name OR e.brand ILIKE :search_brand OR e.model ILIKE :search_model OR e.id::text ILIKE :search_id OR (\'EQ-\' || LPAD(e.id::text, 3, \'0\')) ILIKE :search_code)';
        $pattern = '%' . $search . '%';
        foreach (['search_name', 'search_brand', 'search_model', 'search_id', 'search_code'] as $key) $params[$key] = $pattern;
    }
    if ($condition !== '') { $filters[] = 'LOWER(e.condition) = LOWER(:condition)'; $params['condition'] = $condition; }
    if ($floor !== '') {
        $filters[] = "CASE WHEN e.location ILIKE 'Ground - %' THEN 'Ground Floor' WHEN e.location ILIKE '% Floor - %' THEN split_part(e.location, ' - ', 1) ELSE 'N/A' END ILIKE :floor";
        $params['floor'] = $floor;
    }
    if ($location !== '') {
        $locationExpression = "CASE WHEN e.location ILIKE 'Ground - %' OR e.location ILIKE '2nd Floor - %' OR e.location ILIKE '3rd Floor - %' OR e.location ILIKE '4th Floor - %' THEN split_part(e.location, ' - ', 2) ELSE e.location END";
        $filters[] = $locationExpression . ' ILIKE :location';
        $params['location'] = $location;
    }
    if ($power !== '') { $filters[] = "e.power_source ILIKE :power"; $params['power'] = $power; }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';

    $count = $pdo->prepare('SELECT COUNT(*) FROM public.equipment e' . $where);
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pageSize = max(1, min(100, $pageSize));
    $totalPages = max(1, (int) ceil($total / $pageSize));
    $page = max(1, min($page, $totalPages));
    $stmt = $pdo->prepare('SELECT e.*, f.name AS facility_name FROM public.equipment e LEFT JOIN public.facilities f ON f.id=e.facility_id' . $where . ' ORDER BY e.id DESC LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'total_pages' => $totalPages];
}

function asset_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM assets WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function save_asset(PDO $pdo, string $type, array $data): int {
    $stmt = $pdo->prepare(
        'INSERT INTO assets
        (asset_type, name, brand, model, category, location, date_acquired, condition, details_name, remarks, image_url)
        VALUES (:asset_type, :name, :brand, :model, :category, :location, :date_acquired, :condition, :details_name, :remarks, :image_url)
        RETURNING id'
    );

    $stmt->execute([
        ':asset_type' => $type,
        ':name' => trim((string)$data['name']),
        ':brand' => trim((string)($data['brand'] ?? '')) ?: null,
        ':model' => trim((string)($data['model'] ?? '')) ?: null,
        ':category' => trim((string)($data['category'] ?? '')) ?: null,
        ':location' => trim((string)$data['location']),
        ':date_acquired' => $data['date_acquired'] ?: null,
        ':condition' => $data['condition'],
        ':details_name' => trim((string)($data['details_name'] ?? '')) ?: null,
        ':remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
        ':image_url' => null,
    ]);

    $id = (int)$stmt->fetchColumn();
    log_activity($pdo, $id, 'Added ' . $type, trim((string)$data['name']));
    return $id;
}

function update_condition(PDO $pdo, int $assetId, string $condition, string $notes = ''): void {
    $asset = asset_by_id($pdo, $assetId);
    if (!$asset) {
        throw new RuntimeException('Asset not found.');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('UPDATE assets SET condition = :condition, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':condition' => $condition, ':id' => $assetId]);

        $stmt = $pdo->prepare(
            'INSERT INTO condition_assessments (asset_id, condition, notes, assessed_by)
             VALUES (:asset_id, :condition, :notes, :assessed_by)'
        );
        $stmt->execute([
            ':asset_id' => $assetId,
            ':condition' => $condition,
            ':notes' => trim($notes) ?: null,
            ':assessed_by' => current_user_label(),
        ]);

        log_activity($pdo, $assetId, 'Updated Condition', $asset['name'] . ' → ' . $condition);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function facilities_list(PDO $pdo, string $search = '', string $condition = ''): array
{
    $sql = 'SELECT * FROM facilities';
    $params = [];
    $filters = [];

    if ($search !== '') {
        $filters[] = '(name ILIKE :name_search OR location ILIKE :location_search)';
        $pattern = '%' . $search . '%';
        $params['name_search'] = $pattern;
        $params['location_search'] = $pattern;
    }

    if ($condition !== '') {
        $filters[] = 'condition = :condition';
        $params['condition'] = $condition;
    }

    if ($filters) {
        $sql .= ' WHERE ' . implode(' AND ', $filters);
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function facilities_list_page(PDO $pdo, string $search, string $condition, string $floor, int $page, int $pageSize = 25): array
{
    $filters = [];
    $params = [];
    if ($search !== '') {
        $filters[] = '(name ILIKE :name_search OR location ILIKE :location_search OR id::text ILIKE :id_search OR (\'FA-\' || LPAD(id::text, 3, \'0\')) ILIKE :id_code_search)';
        $pattern = '%' . $search . '%';
        $params = ['name_search' => $pattern, 'location_search' => $pattern, 'id_search' => $pattern, 'id_code_search' => $pattern];
    }
    if ($condition !== '') { $filters[] = 'LOWER(condition) = LOWER(:condition)'; $params['condition'] = $condition; }
    if ($floor !== '') { $filters[] = 'location ILIKE :floor'; $params['floor'] = '%' . $floor . '%'; }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
    $count = $pdo->prepare('SELECT COUNT(*) FROM public.facilities' . $where);
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pageSize = max(1, min(100, $pageSize));
    $totalPages = max(1, (int) ceil($total / $pageSize));
    $page = max(1, min($page, $totalPages));
    $stmt = $pdo->prepare('SELECT * FROM public.facilities' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'total_pages' => $totalPages];
}

function save_facility(PDO $pdo, array $data): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO public.facilities (name, condition, date_acquired, remarks)
             VALUES (:name, :condition, :date_acquired, :remarks)
             RETURNING id'
        );
        $stmt->execute([
            ':name' => trim((string)$data['name']),
            ':condition' => (string)$data['condition'],
            ':date_acquired' => ($data['date_acquired'] ?? '') !== '' ? $data['date_acquired'] : null,
            ':remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
        ]);
        $id = (int) $stmt->fetchColumn();
        lifecycle_central_enqueue($pdo, lifecycle_central_payload([
            'id' => $id, 'name' => trim((string) $data['name']), 'condition' => (string) $data['condition'],
            'date_acquired' => ($data['date_acquired'] ?? '') !== '' ? $data['date_acquired'] : null,
        ], 'facility'));
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    lifecycle_central_flush($pdo);
    return $id;
}

function save_equipment(PDO $pdo, array $data): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO public.equipment
                (name, brand, model, category, location, date_acquired, condition, details_name, remarks)
             VALUES
                (:name, :brand, :model, :category, :location, :date_acquired, :condition, :details_name, :remarks)
             RETURNING id'
        );
        $stmt->execute([
            ':name' => trim((string)$data['name']),
            ':brand' => trim((string)($data['brand'] ?? '')) ?: null,
            ':model' => trim((string)($data['model'] ?? '')) ?: null,
            ':category' => trim((string)($data['category'] ?? '')) ?: null,
            ':location' => trim((string)$data['location']),
            ':date_acquired' => ($data['date_acquired'] ?? '') !== '' ? $data['date_acquired'] : null,
            ':condition' => (string)$data['condition'],
            ':details_name' => trim((string)($data['details_name'] ?? '')) ?: null,
            ':remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
        ]);
        $id = (int) $stmt->fetchColumn();
        lifecycle_central_enqueue($pdo, lifecycle_central_payload([
            'id' => $id, 'name' => trim((string) $data['name']), 'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null, 'category' => $data['category'] ?? null,
            'location' => $data['location'] ?? null, 'date_acquired' => $data['date_acquired'] ?? null,
            'condition' => $data['condition'] ?? null,
        ], 'equipment'));
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    lifecycle_central_flush($pdo);
    return $id;
}

function facility_condition(PDO $pdo, string $search = '', string $condition = ''): array
{
    $sql = 'SELECT * FROM public.facilities';
    $filters = [];
    $params = [];

    if ($search !== '') {
        $filters[] = '(name ILIKE :name OR location ILIKE :location)';
        $params[':name'] = '%' . $search . '%';
        $params[':location'] = '%' . $search . '%';
    }

    if ($condition !== '') {
        $filters[] = 'condition = :condition';
        $params[':condition'] = $condition;
    }

    if ($filters) {
        $sql .= ' WHERE ' . implode(' AND ', $filters);
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function equipment_condition(PDO $pdo, string $search = '', string $condition = ''): array
{
    $sql = 'SELECT * FROM public.equipment';
    $filters = [];
    $params = [];

    if ($search !== '') {
        $filters[] = '(name ILIKE :name OR location ILIKE :location OR asset_tag ILIKE :asset_tag)';
        $pattern = '%' . $search . '%';
        $params[':name'] = $pattern;
        $params[':location'] = $pattern;
        $params[':asset_tag'] = $pattern;
    }

    if ($condition !== '') {
        $filters[] = 'condition = :condition';
        $params[':condition'] = $condition;
    }

    if ($filters) {
        $sql .= ' WHERE ' . implode(' AND ', $filters);
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
