-- Additive LifecycleTrack integration foundation.
-- central_sync_outbox is already used by asset/facility write paths; retain it
-- as the existing outbox and add only event metadata needed for migration.
ALTER TABLE public.central_sync_outbox
    ADD COLUMN IF NOT EXISTS event_id UUID,
    ADD COLUMN IF NOT EXISTS event_type TEXT,
    ADD COLUMN IF NOT EXISTS event_version TEXT,
    ADD COLUMN IF NOT EXISTS correlation_id UUID,
    ADD COLUMN IF NOT EXISTS record_type TEXT,
    ADD COLUMN IF NOT EXISTS source_updated_at TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS lease_until TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS locked_by TEXT;

DO $outbox_event_ids$
BEGIN
    IF EXISTS (
        SELECT 1 FROM public.central_sync_outbox
        WHERE event_id IS NOT NULL
        GROUP BY event_id HAVING count(*) > 1
    ) THEN
        RAISE EXCEPTION 'central_sync_outbox has duplicate event_id values; resolve duplicates before adding the idempotency index';
    END IF;
END
$outbox_event_ids$;
CREATE UNIQUE INDEX IF NOT EXISTS central_sync_outbox_event_id_uidx
    ON public.central_sync_outbox (event_id) WHERE event_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS central_sync_outbox_due_idx
    ON public.central_sync_outbox (next_attempt_at, created_at, id)
    WHERE delivered_at IS NULL;

CREATE TABLE IF NOT EXISTS public.integration_processed_events (
    event_id UUID PRIMARY KEY,
    event_type TEXT NOT NULL,
    source_system TEXT NOT NULL,
    source_id TEXT NOT NULL,
    record_type TEXT NOT NULL,
    source_updated_at TIMESTAMPTZ,
    processing_status TEXT NOT NULL CHECK (processing_status IN ('applied','duplicate','stale','rejected')),
    processed_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    correlation_id UUID
);
CREATE INDEX IF NOT EXISTS integration_processed_events_source_idx
    ON public.integration_processed_events (source_system, record_type, source_id, source_updated_at DESC);

CREATE TABLE IF NOT EXISTS public.integration_errors (
    error_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_id UUID,
    event_type TEXT,
    correlation_id UUID,
    error_code TEXT NOT NULL,
    error_message TEXT NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    resolved_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS integration_errors_unresolved_idx
    ON public.integration_errors (occurred_at DESC) WHERE resolved_at IS NULL;
