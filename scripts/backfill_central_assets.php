<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/central_sync.php';

function lifecycle_backfill(PDO $pdo,int $limit): int
{
    if(!lifecycle_central_config()['enabled']) throw new RuntimeException('Enable Central synchronization before backfill.');
    if(in_array('--reconcile',$GLOBALS['argv']??[],true)){
        $pdo->exec("UPDATE public.central_sync_backfill_checkpoint SET last_source_id=0,status='pending',completed_at=NULL,updated_at=now()");
    }
    $jobs=[
        'facility'=>['facilities','SELECT * FROM public.facilities WHERE id>:after ORDER BY id LIMIT :limit'],
        'equipment'=>['equipment','SELECT * FROM public.equipment WHERE id>:after ORDER BY id LIMIT :limit'],
    ];
    $queued=0;
    foreach($jobs as $entity=>$job){
        [$table,$sql]=$job;
        $jobName='lifecycle_'.$entity;
        $pdo->prepare("INSERT INTO public.central_sync_backfill_checkpoint(job_name) VALUES(:job) ON CONFLICT(job_name) DO NOTHING")->execute(['job'=>$jobName]);
        $pdo->beginTransaction();
        try{
            $checkpoint=$pdo->prepare('SELECT last_source_id,status FROM public.central_sync_backfill_checkpoint WHERE job_name=:job FOR UPDATE');
            $checkpoint->execute(['job'=>$jobName]);$state=$checkpoint->fetch(PDO::FETCH_ASSOC);
            if(($state['status']??'')==='completed'){$pdo->commit();continue;}
            $read=$pdo->prepare($sql);$read->bindValue(':after',(int)$state['last_source_id'],PDO::PARAM_INT);$read->bindValue(':limit',max(1,min(500,$limit)),PDO::PARAM_INT);$read->execute();
            $rows=$read->fetchAll(PDO::FETCH_ASSOC);$last=(int)$state['last_source_id'];
            foreach($rows as $row){$last=(int)$row['id'];lifecycle_central_enqueue($pdo,lifecycle_central_payload($row,$entity));$queued++;}
            $done=count($rows)<max(1,min(500,$limit));
            $write=$pdo->prepare("UPDATE public.central_sync_backfill_checkpoint SET last_source_id=:last,status=:status,scanned_count=scanned_count+:count,updated_at=now(),completed_at=CASE WHEN :done THEN now() ELSE NULL END WHERE job_name=:job");
            $write->execute(['last'=>$last,'status'=>$done?'completed':'running','count'=>count($rows),'done'=>$done,'job'=>$jobName]);
            $pdo->commit();
        }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
    }
    return $queued;
}
try{$queued=lifecycle_backfill($pdo,isset($argv[1])?(int)$argv[1]:100);fwrite(STDOUT,'Queued '.$queued." lifecycle snapshot event(s). Re-run until both checkpoints complete.\n");}
catch(Throwable $error){error_log('LifecycleTrack Central backfill failed: '.$error->getMessage());fwrite(STDERR,"Backfill failed; checkpoint was retained. Check the PHP error log.\n");exit(1);}
