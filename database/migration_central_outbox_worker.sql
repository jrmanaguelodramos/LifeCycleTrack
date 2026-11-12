-- Lease columns let independent CLI workers claim outbox rows safely.
ALTER TABLE public.central_sync_outbox
    ADD COLUMN IF NOT EXISTS lease_until TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS locked_by TEXT;
CREATE INDEX IF NOT EXISTS central_sync_outbox_lease_idx
    ON public.central_sync_outbox (lease_until, next_attempt_at, created_at, id)
    WHERE delivered_at IS NULL;
