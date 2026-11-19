<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/central_sync.php';

function mtConditionJson(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mtConditionText($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function mtConditionB64UrlDecode(string $value): string|false
{
    $value = strtr($value, '-_', '+/');
    $padding = strlen($value) % 4;
    if ($padding) $value .= str_repeat('=', 4 - $padding);
    return base64_decode($value, true);
}

function mtConditionRuntime(): array
{
    static $runtime = null;
    if ($runtime !== null) return $runtime;
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'SIA103_Central-Server' . DIRECTORY_SEPARATOR . '.local-staging' . DIRECTORY_SEPARATOR . 'runtime.json';
    $decoded = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
    $runtime = is_array($decoded) ? $decoded : [];
    return $runtime;
}

function mtConditionSecret(): string
{
    $runtimeSecret = trim((string)(mtConditionRuntime()['api_tokens']['MAINTAINTRACK_CONDITION_HMAC_SECRET'] ?? ''));
    if ($runtimeSecret !== '') return $runtimeSecret;
    return trim((string)getenv('MAINTAINTRACK_CONDITION_HMAC_SECRET'));
}

function mtConditionExpectedReturnUrl(): string
{
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (preg_match('/^(localhost|127\.0\.0\.1|\[::1\]):8082$/i', $host, $m)) {
        return 'http://' . $m[1] . ':8083/pages/admin/admin_assignment.php';
    }
    // Local XAMPP layout: LifeCycleTrack is under the :80 document root,
    // and MaintainTrack is served from its own :8080 document root.
    if (preg_match('/^(localhost|127\.0\.0\.1)(?::80)?$/i', $host, $m)) {
        return 'http://' . $m[1] . ':8080/pages/admin/admin_assignment.php';
    }
    if (preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/i', $host)) {
        // Preserve the XAMPP port so the handoff returns to the same app origin.
        return 'http://' . $host . '/MaintainTrack/pages/admin/admin_assignment.php';
    }
    $configured = trim((string) (getenv('MAINTAINTRACK_ADMIN_RETURN_URL') ?: ''));
    if ($configured !== '') return $configured;
    $runtimePort = (int)(mtConditionRuntime()['ports']['maintain'] ?? 0);
    if ($runtimePort > 0) return 'http://127.0.0.1:' . $runtimePort . '/pages/admin/admin_assignment.php';
    if (strtolower(trim((string) getenv('LOCAL_STAGING_ALLOW_HTTP'))) === 'true') {
        return 'http://127.0.0.1:8083/pages/admin/admin_assignment.php';
    }
    return '';
}

function mtConditionConsumeHandoff(PDO $pdo): array
{
    $token = trim((string) ($_GET['token'] ?? ''));
    $secret = mtConditionSecret();
    if ($token === '' || $secret === '') throw new RuntimeException('This MaintainTrack assessment link is missing or expired. Reopen it from MaintainTrack Assignments.');
    $parts = explode('.', $token);
    if (count($parts) !== 2) throw new RuntimeException('Invalid MaintainTrack assessment link.');
    $encoded = $parts[0];
    $expected = hash_hmac('sha256', $encoded, $secret);
    if (!hash_equals($expected, $parts[1])) throw new RuntimeException('Invalid MaintainTrack assessment link.');
    $json = mtConditionB64UrlDecode($encoded);
    $claims = $json === false ? null : json_decode($json, true);
    if (!is_array($claims) || ($claims['iss'] ?? '') !== 'MaintainTrack' || ($claims['aud'] ?? '') !== 'LifeCycleTrack'
        || (int) ($claims['exp'] ?? 0) < time() || (int) ($claims['nbf'] ?? PHP_INT_MAX) > time()
        || !in_array(($claims['entity'] ?? ''), ['equipment', 'facility'], true)
        || !preg_match('/^[0-9a-f-]{36}$/i', (string) ($claims['jti'] ?? ''))
        || !preg_match('/^[A-Za-z0-9_-]{1,80}$/', (string) ($claims['request_id'] ?? ''))
        || (isset($claims['request_reference']) && !preg_match('/^[A-Za-z0-9_-]{1,80}$/', (string)$claims['request_reference']))
        || !ctype_digit((string) ($claims['asset_id'] ?? '')) || (int) $claims['asset_id'] < 1
        || !preg_match('/^[0-9a-f-]{36}$/i', (string) ($claims['admin_user_id'] ?? ''))
        || !filter_var((string) ($claims['return_url'] ?? ''), FILTER_VALIDATE_URL)
        || !hash_equals(mtConditionExpectedReturnUrl(), (string) ($claims['return_url'] ?? ''))) {
        throw new RuntimeException('This MaintainTrack assessment link is invalid or expired. Reopen it from MaintainTrack Assignments.');
    }

    $usedTokens = $_SESSION['maintaintrack_condition_used_tokens'] ?? [];
    if (!is_array($usedTokens)) $usedTokens = [];
    foreach ($usedTokens as $usedId => $expiresAt) if ((int)$expiresAt < time()) unset($usedTokens[$usedId]);
    if (isset($usedTokens[(string)$claims['jti']])) throw new RuntimeException('This MaintainTrack assessment link has already been used. Reopen it from MaintainTrack Assignments.');

    $entity = (string) $claims['entity'];
    $assetId = (int) $claims['asset_id'];
    $table = $entity === 'equipment' ? 'equipment' : 'facilities';
    $check = $pdo->prepare("SELECT id FROM public.{$table} WHERE id=:id");
    $check->execute(['id' => $assetId]);
    if (!$check->fetchColumn()) throw new RuntimeException('The referenced LifeCycleTrack record no longer exists.');

    $usedTokens[(string)$claims['jti']] = (int)$claims['exp'];
    $_SESSION['maintaintrack_condition_used_tokens'] = $usedTokens;
    return $claims;
}

function mtConditionCreateReceipt(array $scope): ?string
{
    $secret = mtConditionSecret();
    if ($secret === '' || !preg_match('/^[0-9a-f-]{36}$/i', (string)($scope['request_id'] ?? ''))
        || !in_array(($scope['entity'] ?? ''), ['equipment','facility'], true)) return null;
    $claims = [
        'iss'=>'LifeCycleTrack','aud'=>'MaintainTrack','request_id'=>(string)$scope['request_id'],
        'entity'=>(string)$scope['entity'],'asset_id'=>(string)$scope['asset_id'],
        'admin_user_id'=>(string)$scope['admin_user_id'],'exp'=>time()+600,
    ];
    $encoded = rtrim(strtr(base64_encode(json_encode($claims, JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    return $encoded . '.' . hash_hmac('sha256', $encoded, $secret);
}

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');

$scope = $_SESSION['maintaintrack_condition_scope'] ?? null;
if (isset($_GET['token'])) {
    try {
        $scope = mtConditionConsumeHandoff($pdo);
        $scope['session_expires_at'] = time() + 1200;
        $_SESSION['maintaintrack_condition_scope'] = $scope;
        session_regenerate_id(true);
        $params = [
            'entity' => $scope['entity'], 'asset_id' => (string) $scope['asset_id'],
            'request_id' => $scope['request_id'],
        ];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($params), true, 303);
        exit;
    } catch (Throwable $error) {
        http_response_code(403);
        exit(mtConditionText($error->getMessage()));
    }
}

$queryEntity = (string) ($_GET['entity'] ?? '');
$queryAssetId = filter_var($_GET['asset_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
if (!is_array($scope) || (int) ($scope['session_expires_at'] ?? 0) < time()
    || $queryEntity !== (string) ($scope['entity'] ?? '') || $queryAssetId !== (int) ($scope['asset_id'] ?? 0)
    || (string) ($_GET['request_id'] ?? '') !== (string) ($scope['request_id'] ?? '')) {
    unset($_SESSION['maintaintrack_condition_scope']);
    http_response_code(403);
    exit('This scoped assessment session expired. Reopen the request from MaintainTrack Assignments.');
}

$entity = (string) $scope['entity'];
$assetId = (int) $scope['asset_id'];
$requestReference = (string) ($scope['request_reference'] ?? $scope['request_id']);
$returnUrl = (string) $scope['return_url'];
$adminLabel = trim((string) ($scope['admin_username'] ?? 'MaintainTrack Admin'));
$table = $entity === 'equipment' ? 'equipment' : 'facilities';
$recordStmt = $pdo->prepare("SELECT id,name,condition,status,location FROM public.{$table} WHERE id=:id");
$recordStmt->execute(['id' => $assetId]);
$asset = $recordStmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) { http_response_code(404); exit('LifeCycleTrack record not found.'); }

$allowedStatuses = [
    'Serviceable (functioning)', 'Unserviceable: for maintenance', 'Unserviceable: for repair',
    'Unserviceable: for inspection', 'Beyond repair (irreparable)',
    'Subject for disposal or condemnation', 'Disposed',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $condition = (string) ($input['condition'] ?? '');
    $status = trim((string) ($input['status'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? ''));
    if ((int) ($input['asset_id'] ?? 0) !== $assetId || !in_array($condition, ['Good', 'Fair', 'Poor'], true)
        || !in_array($status, $allowedStatuses, true)) mtConditionJson(422, ['ok' => false, 'message' => 'Select a valid condition and status.']);

    $requestMarker = 'MaintainTrack request ' . $requestReference . ' condition assessment by ' . $adminLabel . '.';
    if (!str_contains($notes, $requestReference)) $notes = trim($notes . ($notes !== '' ? "\n" : '') . $requestMarker);
    $assessmentTable = $entity === 'equipment' ? 'equipment_condition_assessments' : 'facility_condition_assessments';
    $foreignKey = $entity === 'equipment' ? 'equipment_id' : 'facility_id';
    $pdo->beginTransaction();
    try {
        $assessment = $pdo->prepare("INSERT INTO public.{$assessmentTable}({$foreignKey},condition,status,notes,assessed_by) VALUES(:id,:condition,:status,:notes,:actor)");
        $assessment->execute(['id' => $assetId, 'condition' => $condition, 'status' => $status, 'notes' => $notes, 'actor' => 'MaintainTrack Admin: ' . $adminLabel]);
        $update = $pdo->prepare("UPDATE public.{$table} SET condition=:condition,status=:status,disposed_at=CASE WHEN CAST(:status2 AS text)='Disposed' THEN COALESCE(disposed_at,now()) ELSE disposed_at END,updated_at=now() WHERE id=:id");
        $update->execute(['condition' => $condition, 'status' => $status, 'status2' => $status, 'id' => $assetId]);
        $row = $pdo->prepare("SELECT * FROM public.{$table} WHERE id=:id");
        $row->execute(['id' => $assetId]);
        $saved = $row->fetch(PDO::FETCH_ASSOC);
        if (is_array($saved)) lifecycle_central_enqueue($pdo, lifecycle_central_payload($saved, $entity));
        $activity = $pdo->prepare("INSERT INTO public.activity_log({$foreignKey},action,details,actor) VALUES(:id,'condition_assessment',:details,:actor)");
        $activity->execute(['id' => $assetId, 'details' => 'Condition: ' . $condition . '; Status: ' . $status . '; MaintainTrack request: ' . $requestReference, 'actor' => 'MaintainTrack Admin: ' . $adminLabel]);
        $pdo->commit();
        mtConditionJson(200, ['ok' => true, 'condition' => $condition, 'status' => $status, 'condition_receipt'=>mtConditionCreateReceipt($scope)]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('MaintainTrack condition handoff failed: ' . $error->getMessage());
        mtConditionJson(500, ['ok' => false, 'message' => 'Unable to save the condition assessment.']);
    }
}

$title = $entity === 'equipment' ? 'Equipment' : 'Facility';
$currentCondition = (string) ($asset['condition'] ?? '');
$currentStatus = (string) ($asset['status'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>MaintainTrack Condition Assessment</title>
    <style>
        body{margin:0;background:#f1f5f9;color:#153653;font:15px Arial,sans-serif}.wrap{max-width:760px;margin:5vh auto;padding:24px}.card{background:#fff;border:1px solid #d7e2ec;border-radius:16px;padding:26px;box-shadow:0 12px 36px #15365318}.tag{color:#55718b;font-size:13px}.asset{margin:8px 0 4px;font-size:24px}.section{margin:22px 0 8px;font-weight:700}.conditions{display:flex;gap:10px;flex-wrap:wrap}.conditions label{border:1px solid #ccd9e5;border-radius:9px;padding:11px 15px;cursor:pointer}.conditions input{margin-right:7px}select,textarea{width:100%;box-sizing:border-box;border:1px solid #ccd9e5;border-radius:9px;padding:11px;font:inherit}textarea{min-height:105px;resize:vertical}.hint{color:#637b91;font-size:13px;line-height:1.5}.buttons{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}.btn{display:inline-block;border:0;border-radius:9px;padding:11px 16px;background:#1767a8;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.btn.secondary{background:#edf4fa;color:#24516f}.btn[hidden]{display:none}.banner{background:#edf6fc;border:1px solid #cfe0ef;padding:12px;border-radius:9px;margin:18px 0}.error{color:#a32020;margin-top:14px}
    </style>
</head>
<body>
<main class="wrap"><section class="card">
    <div class="tag">MaintainTrack request <?= mtConditionText($requestReference) ?> · <?= mtConditionText($title) ?> assessment</div>
    <h1 class="asset"><?= mtConditionText($asset['name'] ?? ($title . ' #' . $assetId)) ?></h1>
    <div class="tag">Current condition: <?= mtConditionText($currentCondition ?: 'Not recorded') ?> · Status: <?= mtConditionText($currentStatus ?: 'Not recorded') ?></div>
    <div class="banner">This assessment is authorized for this request and asset only. Saving updates LifeCycleTrack and records the request in condition history.</div>
    <form id="conditionForm">
        <div class="section">Condition</div>
        <div class="conditions">
            <?php foreach (['Good','Fair','Poor'] as $condition): ?>
                <label><input type="radio" name="condition" value="<?= mtConditionText($condition) ?>" <?= $currentCondition === $condition ? 'checked' : '' ?> required><?= mtConditionText($condition) ?></label>
            <?php endforeach; ?>
        </div>
        <div class="section">Operational status</div>
        <select name="status" required><option value="">Select status</option>
            <?php foreach ($allowedStatuses as $option): ?><option value="<?= mtConditionText($option) ?>" <?= $currentStatus === $option ? 'selected' : '' ?>><?= mtConditionText($option) ?></option><?php endforeach; ?>
        </select>
        <div class="section">Assessment notes</div>
        <textarea name="notes" placeholder="Describe the inspection and condition found.">MaintainTrack request <?= mtConditionText($requestReference) ?> condition assessment.</textarea>
        <p class="hint">The request number and MaintainTrack Admin identity are saved with this condition-history entry.</p>
        <div id="formError" class="error" role="alert"></div>
        <div class="buttons">
            <a class="btn secondary" href="<?= mtConditionText($returnUrl) ?>">Cancel and return</a>
            <button class="btn" id="saveCondition" type="submit">Save assessment</button>
            <a class="btn" id="returnToMaintainTrack" href="<?= mtConditionText($returnUrl) ?>" hidden>Return to MaintainTrack Admin</a>
        </div>
    </form>
</section></main>
<script>
document.getElementById('conditionForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget, button = document.getElementById('saveCondition'), error = document.getElementById('formError');
    const condition = form.querySelector('input[name="condition"]:checked')?.value || '';
    const status = form.elements.status.value;
    if (!condition || !status) { error.textContent = 'Select both condition and status.'; return; }
    button.disabled = true; button.textContent = 'Saving…'; error.textContent = '';
    try {
        const response = await fetch(window.location.pathname + window.location.search, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({asset_id: <?= json_encode($assetId) ?>, condition, status, notes: form.elements.notes.value})
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.ok) throw new Error(result.message || 'Could not save the assessment.');
        button.hidden = true;
        const returnLink = document.getElementById('returnToMaintainTrack');
        if (result.condition_receipt) {
            const returnUrl = new URL(returnLink.href);
            returnUrl.searchParams.set('condition_receipt', result.condition_receipt);
            returnLink.href = returnUrl.toString();
        }
        returnLink.hidden = false;
        error.className = 'hint'; error.textContent = 'Assessment saved and recorded in LifeCycleTrack condition history.';
    } catch (e) {
        error.className = 'error'; error.textContent = e.message;
        button.disabled = false; button.textContent = 'Save assessment';
    }
});
</script>
</body>
</html>
