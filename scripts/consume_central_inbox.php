<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';

function lifecycle_worker_env(string $key):string
{
    static $fileValues = null;
    if ($fileValues === null) {
        $parsed = @parse_ini_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env', false, INI_SCANNER_RAW);
        $fileValues = is_array($parsed) ? $parsed : [];
    }
    $value = getenv($key);
    if ($value !== false && trim((string)$value) !== '') return trim((string)$value);
    return trim((string)($fileValues[$key] ?? ''));
}
function lifecycle_worker_http(string $url,array $body,string $token):array
{
    if(!function_exists('curl_init'))throw new RuntimeException('PHP cURL is required');
    $h=curl_init($url);curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/json','Authorization: Bearer '.$token],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $raw=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);$error=curl_error($h);curl_close($h);
    if($raw===false||$status<200||$status>=300)throw new RuntimeException($raw===false?$error:'HTTP '.$status);
    $data=json_decode($raw,true);return is_array($data)?$data:[];
}
function lifecycle_apply_incoming(PDO $pdo,array $event):string
{
    $source=(string)($event['source']??'');$type=(string)($event['record_type']??'');$id=(string)($event['source_id']??'');$eventId=(string)($event['event_id']??'');$occurred=(string)($event['occurred_at']??'');
    $allowed=in_array([$source,$type],[['UtilityTrack','utility_bill'],['UtilityTrack','utility_anomaly'],['MaintainTrack','maintenance_request'],['MaintainTrack','maintenance_update']],true);
    if(!$allowed||$id===''||strtotime($occurred)===false||!is_array($event['payload']??null)||(string)($event['payload']['id']??'')!==$id)throw new RuntimeException('Invalid incoming event');
    $table=$pdo->prepare("INSERT INTO public.integration_processed_events(event_id,event_type,source_system,source_id,record_type,source_updated_at,processing_status,correlation_id) VALUES(:event,:type,:source,:id,:record,:time,'applied',NULLIF(:correlation,'')::uuid) ON CONFLICT(event_id) DO NOTHING");
    $table->execute(['event'=>$eventId,'type'=>$event['event_type'],'source'=>$source,'id'=>$id,'record'=>$type,'time'=>$occurred,'correlation'=>(string)($event['correlation_id']??'')]);
    if($table->rowCount()===0)return 'duplicate';
    $deleted=str_ends_with((string)($event['event_type']??''),'.deleted');
    $versionComparison=$type==='maintenance_update'?'<=':'<';
    $stmt=$pdo->prepare("INSERT INTO public.central_external_snapshots AS s(source_system,record_type,source_id,event_id,payload,source_updated_at,received_at,deleted_at) VALUES(:source,:type,:id,:event,CAST(:payload AS jsonb),:source_time,now(),CASE WHEN :is_deleted THEN CAST(:deleted_time AS timestamptz) ELSE NULL::timestamptz END) ON CONFLICT(source_system,record_type,source_id) DO UPDATE SET event_id=EXCLUDED.event_id,payload=CASE WHEN EXCLUDED.deleted_at IS NULL THEN EXCLUDED.payload ELSE s.payload END,source_updated_at=EXCLUDED.source_updated_at,received_at=now(),deleted_at=EXCLUDED.deleted_at WHERE s.source_updated_at{$versionComparison}EXCLUDED.source_updated_at");
    $stmt->execute(['source'=>$source,'type'=>$type,'id'=>$id,'event'=>$eventId,'payload'=>json_encode($event['payload'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'source_time'=>$occurred,'is_deleted'=>$deleted?'true':'false','deleted_time'=>$occurred]);
    if($stmt->rowCount()===0){$pdo->prepare("UPDATE public.integration_processed_events SET processing_status='stale' WHERE event_id=:event")->execute(['event'=>$eventId]);return 'stale';}
    return 'applied';
}
function lifecycle_inbox_once(PDO $pdo,int $count):int
{
    $base=rtrim(lifecycle_worker_env('CENTRAL_API_BASE_URL'),'/');$token=lifecycle_worker_env('LIFECYCLETRACK_INBOX_TOKEN');
    $parts=parse_url($base);$localHttp=strtolower(trim((string)getenv('LOCAL_STAGING_ALLOW_HTTP')))==='true'&&is_array($parts)&&strtolower((string)($parts['scheme']??''))==='http'&&in_array(strtolower((string)($parts['host']??'')),['127.0.0.1','localhost','::1'],true);
    if((!str_starts_with($base,'https://')&&!$localHttp)||$token==='')throw new RuntimeException('Central HTTPS URL and LifecycleTrack inbox token are required');
    $batch=lifecycle_worker_http($base.'/api/v1/inbox/LifeCycleTrack/read',['count'=>max(1,min(10,$count)),'visibility_seconds'=>600],$token);$done=0;
    foreach($batch['messages']??[] as $item){$msg=(int)($item['msg_id']??0);$event=$item['event']??null;if($msg<1||!is_array($event))continue;
        try{$pdo->beginTransaction();$result=lifecycle_apply_incoming($pdo,$event);$pdo->commit();$ack=lifecycle_worker_http($base.'/api/v1/inbox/LifeCycleTrack/ack',['msg_id'=>$msg],$token);if(($ack['acknowledged']??false)!==true)throw new RuntimeException('Central did not acknowledge event');$done++;}
        catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();error_log('LifecycleTrack inbox event '.$msg.' failed: '.$error->getMessage());}
    }return $done;
}
try{$count=lifecycle_inbox_once($pdo,isset($argv[1])?(int)$argv[1]:10);fwrite(STDOUT,'Processed '.$count." LifecycleTrack inbox event(s).\n");}
catch(Throwable $error){error_log('LifecycleTrack inbox worker failed: '.$error->getMessage());fwrite(STDERR,"LifecycleTrack inbox worker failed. Check the PHP error log.\n");exit(1);}
