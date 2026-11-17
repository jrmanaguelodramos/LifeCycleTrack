<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){header('Allow: GET');http_response_code(405);echo json_encode(['error'=>'method_not_allowed']);exit;}
try{
    require __DIR__.'/../../config/database.php';
    $stmt=$pdo->query("SELECT COUNT(*) FILTER(WHERE delivered_at IS NULL) AS pending,COUNT(*) FILTER(WHERE delivered_at IS NOT NULL) AS delivered,COUNT(*) FILTER(WHERE delivered_at IS NULL AND attempts>0) AS retrying,MIN(created_at) FILTER(WHERE delivered_at IS NULL) AS oldest_pending FROM public.central_sync_outbox");
    $outbox=$stmt->fetch(PDO::FETCH_ASSOC)?:[];
    http_response_code(200);echo json_encode(['data'=>['service'=>'LifeCycleTrack integration','status'=>'online','outbox'=>['pending'=>(int)($outbox['pending']??0),'delivered'=>(int)($outbox['delivered']??0),'retrying'=>(int)($outbox['retrying']??0),'oldest_pending_at'=>$outbox['oldest_pending']??null]],'meta'=>['checked_at'=>gmdate('c')]],JSON_UNESCAPED_SLASHES);
}catch(Throwable $error){error_log('LifecycleTrack sync status failed: '.$error->getMessage());http_response_code(503);echo json_encode(['error'=>'integration_status_unavailable','meta'=>['checked_at'=>gmdate('c')]]);}
