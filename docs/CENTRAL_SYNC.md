# Central Server synchronization

LifeCycleTrack owns its equipment and facility rows. When enabled, creates and edits write a sanitized snapshot to `central_sync_outbox` inside the same source database transaction. The application attempts delivery after commit; failures remain in the outbox and are retried on a later equipment/facility save with bounded backoff up to 15 minutes.

## Setup

1. Run `database/central_sync_outbox.sql` in the LifeCycleTrack database.
2. Configure `CENTRAL_API_BASE_URL`, `CENTRAL_API_TOKEN`, `CENTRAL_API_TIMEOUT=8`, and `CENTRAL_API_ENABLED=true` in the LifeCycleTrack `.env`. The token must match `LIFECYCLETRACK_API_TOKEN` on the Central Server.
3. Use a reachable Central Server LAN address, not `localhost` from another computer. Use HTTPS when sending bearer tokens over a network.

The sender omits images and free-form remarks. HTTP 202 marks an outbox row delivered because the Central Server has durably accepted its event into Supabase Queues. The Central Server worker applies it asynchronously. See the Central Server API contract and README for queue setup and retry behavior.
