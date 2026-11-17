-- Consume short-lived MaintainTrack Admin condition-assessment handoffs once.
CREATE TABLE IF NOT EXISTS public.maintaintrack_condition_access_tokens (
    token_id UUID PRIMARY KEY,
    request_id TEXT NOT NULL,
    entity_type TEXT NOT NULL CHECK (entity_type IN ('equipment','facility')),
    asset_id BIGINT NOT NULL CHECK (asset_id > 0),
    admin_user_id UUID NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    consumed_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS maintaintrack_condition_access_tokens_expiry_idx
    ON public.maintaintrack_condition_access_tokens (expires_at);

ALTER TABLE public.maintaintrack_condition_access_tokens ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.maintaintrack_condition_access_tokens FROM PUBLIC,anon,authenticated;
GRANT ALL ON TABLE public.maintaintrack_condition_access_tokens TO service_role;
