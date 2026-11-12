# Central Server synchronization

LifeCycleTrack owns its equipment and facility rows. When enabled, creates and edits write a versioned, sanitized event to `central_sync_outbox` inside the same source database transaction. Web requests do not wait for Central. Run `php scripts/publish_central_outbox.php` from the LifeCycleTrack project root as a scheduled/background worker; failures remain leased/retryable in the outbox.

## Setup

1. Apply `database/integration_foundation.sql` and `database/migration_central_outbox_worker.sql` in the LifeCycleTrack database. Run the older `database/central_sync_outbox.sql` first only if the outbox table does not yet exist.
2. Configure `CENTRAL_API_BASE_URL`, `CENTRAL_API_TOKEN`, `CENTRAL_API_TIMEOUT=8`, and `CENTRAL_API_ENABLED=true` in the LifeCycleTrack `.env`. The token must match `LIFECYCLETRACK_API_TOKEN` on the Central Server.
3. Use a reachable Central Server LAN address, not `localhost` from another computer. Use HTTPS when sending bearer tokens over a network.

The sender omits images and free-form remarks. HTTP 202 marks an outbox row delivered because Central has durably stored and routed the event. MaintainTrack later consumes its own inbox through the Central API proxy and acknowledges only after its local snapshot and processed-event record commit.
