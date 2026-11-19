<?php

declare(strict_types=1);
require_once __DIR__ . '/_app.php';
require_once __DIR__ . '/_analytics.php';

require_login();
if (empty($_SESSION['access_token'])) {
  header('Location: ../auth/login.php');
  exit;
}

$rows = [];
$pageError = '';
try {
  $sql = "SELECT u.source_id AS update_id, u.payload->>'request_id' AS request_key,
                   COALESCE(r.payload->>'display_id', u.payload->>'request_id') AS request_number,
                   COALESCE(eq.name, f.name, r.payload->>'external_equipment_name', r.payload->>'external_facility_name', 'External asset') AS asset_name,
                   COALESCE(eq.asset_tag, '') AS asset_tag,
                   u.payload->>'title' AS title, u.payload->>'status' AS status,
                   u.payload->>'completion_outcome' AS completion_outcome,
                   u.source_updated_at
              FROM public.central_external_snapshots u
              LEFT JOIN public.central_external_snapshots r
                ON r.source_system='MaintainTrack' AND r.record_type='maintenance_request'
               AND r.source_id=u.payload->>'request_id' AND r.deleted_at IS NULL
              LEFT JOIN public.equipment eq
                ON eq.id::text=COALESCE(r.payload->>'external_equipment_id', '')
              LEFT JOIN public.facilities f
                ON f.id::text=COALESCE(r.payload->>'external_facility_id', '')
             WHERE u.source_system='MaintainTrack' AND u.record_type='maintenance_update'
               AND u.deleted_at IS NULL
             ORDER BY u.source_updated_at DESC LIMIT 300";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
  error_log('MaintainTrack outcome snapshot lookup failed: ' . $error->getMessage());
  $pageError = 'Maintenance outcomes are not available yet. Confirm the local integration migrations and inbox worker are running.';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MaintainTrack Outcomes · LifeCycleTrack</title>
  <link rel="stylesheet" href="../dist/output.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: #f3f6fa;
      color: #193b59;
      font-family: Arial, sans-serif
    }

    .outcome-wrap {
      margin: 30px auto;
      padding: 0 22px;
      max-width: 1200px
    }

    .outcome-card {
      background: #fff;
      border: 1px solid #dbe5ee;
      border-radius: 14px;
      padding: 22px;
      box-shadow: 0 8px 28px #193b5912
    }

    .outcome-card h1 {
      margin: 0 0 8px
    }

    .muted {
      color: #657b90
    }

    .outcome-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 18px
    }

    .outcome-table th,
    .outcome-table td {
      text-align: left;
      padding: 12px;
      border-bottom: 1px solid #e8eef3;
      vertical-align: top
    }

    .outcome-table th {
      font-size: 12px;
      color: #48647c
    }

    .outcome-table td {
      font-size: 14px;
      white-space: pre-wrap
    }

    .notice {
      padding: 13px;
      border-radius: 9px;
      background: #fff5dd;
      color: #755310
    }

    .empty {
      text-align: center;
      padding: 32px;
      color: #657b90
    }

    @media(max-width:760px) {
      .outcome-table {
        display: block;
        overflow: auto
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="ml-64 p-8">
    <section class="outcome-card">
      <h1>MaintainTrack Completion Outcomes</h1>
      <p class="muted">Read-only completion notes received through Central. MaintainTrack remains the source of these records.</p>
      <?php if ($pageError !== ''): ?><p class="notice"><?= e($pageError) ?></p>
      <?php elseif (!$rows): ?><p class="empty">No MaintainTrack updates have arrived yet. Completed tasks will appear after the Central publisher and LifeCycleTrack inbox worker process their events.</p>
      <?php else: ?><table class="outcome-table">
          <thead>
            <tr>
              <th>Request</th>
              <th>LifeCycleTrack asset</th>
              <th>Update</th>
              <th>Status</th>
              <th>Completion outcome</th>
              <th>Received</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?><tr>
                <td><?= e($row['request_number'] ?? '—') ?></td>
                <td><?= e($row['asset_name'] ?? 'External asset') ?><?= !empty($row['asset_tag']) ? '<br><small>' . e($row['asset_tag']) . '</small>' : '' ?></td>
                <td><?= e($row['title'] ?? '—') ?></td>
                <td><?= e($row['status'] ?? '—') ?></td>
                <td><?= e($row['completion_outcome'] ?? '—') ?></td>
                <td><?= e(date('M j, Y g:i A', strtotime((string)$row['source_updated_at']))) ?></td>
              </tr><?php endforeach; ?>
          </tbody>
        </table><?php endif; ?>
    </section>
  </main>
</body>

</html>