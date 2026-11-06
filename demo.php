<?php

declare(strict_types=1);

// $lifecycleTrackRoot = dirname(__DIR__) . '/LifeCycleTrack';
// $envFile = $lifecycleTrackRoot . '/.env';
// $env = is_file($envFile)
//     ? parse_ini_file($envFile, false, INI_SCANNER_RAW)
//     : false;
$token = 'maintenance_api_token_123';
$endpoint = 'http://10.211.46.87:8000/api/v1/maintenance-work-orders.php';
$result = null;
$error = '';
$httpStatus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workOrder = [
        'work_order_id' => trim((string) ($_POST['work_order_id'] ?? '')),
        'asset_id' => trim((string) ($_POST['asset_id'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    if ($token === '') {
        $error = 'Add MAINTENANCE_API_TOKEN to LifeCycleTrack/.env before sending.';
    } elseif (!function_exists('curl_init')) {
        $error = 'PHP cURL is not enabled. Enable the cURL extension in XAMPP.';
    } else {
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($workOrder, JSON_UNESCAPED_SLASHES),
        ]);

        $responseBody = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($responseBody === false) {
            $error = 'Could not contact LifecycleTrack: ' . $curlError;
        } else {
            $result = json_decode($responseBody, true);
            if (!is_array($result)) {
                $error = 'LifecycleTrack returned a response that was not JSON.';
            } elseif ($httpStatus < 200 || $httpStatus >= 300) {
                $error = $result['error']['message'] ?? 'LifecycleTrack rejected the request.';
            }
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Management Demo</title>
    <style>
        body {
            margin: 0;
            padding: 32px 16px;
            background: #f3f6fa;
            color: #182230;
            font: 16px/1.5 Arial, sans-serif;
        }

        main {
            max-width: 680px;
            margin: 0 auto;
            padding: 28px;
            background: white;
            border: 1px solid #dce3eb;
            border-radius: 12px;
        }

        h1 {
            margin-top: 0;
            font-size: 1.6rem;
        }

        p {
            color: #475467;
        }

        label {
            display: block;
            margin: 16px 0 5px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b8c3d1;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            margin-top: 20px;
            padding: 11px 18px;
            border: 0;
            border-radius: 7px;
            background: #155eef;
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .notice {
            margin-top: 20px;
            padding: 14px;
            border-radius: 7px;
            background: #ecfdf3;
            border: 1px solid #abefc6;
        }

        .error {
            background: #fef3f2;
            border-color: #fecdca;
        }

        code,
        pre {
            font-family: Consolas, monospace;
        }

        pre {
            overflow-x: auto;
            padding: 14px;
            background: #f8fafc;
            border-radius: 7px;
        }
    </style>
</head>

<body>
    <main>
        <h1>Maintenance Management</h1>
        <p>Enter a work order and send it to LifecycleTrack. Submitting the same work order ID again updates its saved copy.</p>

        <?php if ($error !== ''): ?>
            <div class="notice error"><strong>Not sent:</strong> <?= escapeHtml($error) ?></div>
        <?php elseif (is_array($result)): ?>
            <div class="notice">
                <strong>LifecycleTrack replied (HTTP <?= (int) $httpStatus ?>).</strong>
                <?php if (!empty($result['data']['saved_to_database'])): ?>
                    The work order was saved to its database.
                <?php endif; ?>
            </div>
            <pre><?= escapeHtml(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '') ?></pre>
        <?php endif; ?>

        <form method="post">
            <label for="work_order_id">Work order ID</label>
            <input id="work_order_id" name="work_order_id" value="<?= escapeHtml((string) ($_POST['work_order_id'] ?? 'WO-2026-0042')) ?>" required>

            <label for="asset_id">Shared asset ID</label>
            <input id="asset_id" name="asset_id" value="<?= escapeHtml((string) ($_POST['asset_id'] ?? 'AST-0015')) ?>" required>

            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['open', 'in_progress', 'completed', 'cancelled'] as $status): ?>
                    <option value="<?= escapeHtml($status) ?>" <?= (($_POST['status'] ?? 'in_progress') === $status) ? 'selected' : '' ?>><?= escapeHtml($status) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="description">Work description</label>
            <textarea id="description" name="description" required><?= escapeHtml((string) ($_POST['description'] ?? 'Inspect the air conditioner')) ?></textarea>

            <button type="submit">Send work order to LifecycleTrack</button>
        </form>
    </main>
</body>

</html>