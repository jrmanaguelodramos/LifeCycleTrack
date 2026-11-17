-- Read-only remote event snapshots consumed by the LifecycleTrack inbox worker.
CREATE TABLE IF NOT EXISTS public.central_external_snapshots (
    source_system TEXT NOT NULL CHECK(source_system IN ('UtilityTrack','MaintainTrack')),
    record_type TEXT NOT NULL CHECK(record_type IN ('utility_bill','maintenance_request','maintenance_update')),
    source_id TEXT NOT NULL,
    event_id UUID NOT NULL,
    payload JSONB NOT NULL CHECK(jsonb_typeof(payload)='object'),
    source_updated_at TIMESTAMPTZ NOT NULL,
    received_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    PRIMARY KEY(source_system,record_type,source_id)
);
CREATE INDEX IF NOT EXISTS central_external_snapshots_active_idx
    ON public.central_external_snapshots(source_system,record_type,source_id) WHERE deleted_at IS NULL;
ALTER TABLE public.central_external_snapshots ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.central_external_snapshots FROM anon,authenticated;
GRANT ALL ON public.central_external_snapshots TO service_role;
