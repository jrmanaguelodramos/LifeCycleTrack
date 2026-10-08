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

function fetch_assets(PDO $pdo, string $type, string $search = '', string $condition = ''): array {
    $sql = 'SELECT * FROM assets WHERE asset_type = :type';
    $params = [':type' => $type];

    if ($search !== '') {
        $sql .= ' AND (name ILIKE :search OR location ILIKE :search OR brand ILIKE :search OR model ILIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($condition !== '') {
        $sql .= ' AND condition = :condition';
        $params[':condition'] = $condition;
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
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
