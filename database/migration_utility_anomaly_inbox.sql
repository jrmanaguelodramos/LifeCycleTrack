-- Allow LifeCycleTrack to retain read-only UtilityTrack anomaly snapshots.
ALTER TABLE public.central_external_snapshots
    DROP CONSTRAINT IF EXISTS central_external_snapshots_record_type_check;
ALTER TABLE public.central_external_snapshots
    ADD CONSTRAINT central_external_snapshots_record_type_check
    CHECK (record_type IN ('utility_bill','utility_anomaly','maintenance_request','maintenance_update'));
NOTIFY pgrst,'reload schema';
