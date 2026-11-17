-- Resumable source checkpoint for lifecycle snapshot bootstrap.
CREATE TABLE IF NOT EXISTS public.central_sync_backfill_checkpoint (
    job_name TEXT PRIMARY KEY,
    last_source_id BIGINT NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','running','completed','failed')),
    scanned_count BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    completed_at TIMESTAMPTZ
);
ALTER TABLE public.central_sync_backfill_checkpoint ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.central_sync_backfill_checkpoint FROM anon,authenticated;
GRANT ALL ON public.central_sync_backfill_checkpoint TO service_role;
