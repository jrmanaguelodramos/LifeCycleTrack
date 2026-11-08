-- Run once in the LifeCycleTrack PostgreSQL/Supabase database before enabling delivery.
CREATE TABLE IF NOT EXISTS public.central_sync_outbox (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    endpoint TEXT NOT NULL,
    source_id TEXT NOT NULL,
    payload JSONB NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    next_attempt_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    delivered_at TIMESTAMPTZ NULL
);
ALTER TABLE public.central_sync_outbox ADD COLUMN IF NOT EXISTS next_attempt_at TIMESTAMPTZ NULL;
CREATE INDEX IF NOT EXISTS central_sync_outbox_pending_idx
    ON public.central_sync_outbox (created_at, id)
    WHERE delivered_at IS NULL;
