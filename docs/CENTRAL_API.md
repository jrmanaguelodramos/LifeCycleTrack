# Central Server integration

LifeCycleTrack can copy equipment and facility records to the Central Server after a successful create or edit. Its PostgreSQL database remains the source of truth. Central API errors are logged and do not prevent the local operation. This first slice sends future changes; it does not backfill existing records.

## Configure

In LifeCycleTrack's local `.env`, add:

```dotenv
CENTRAL_API_BASE_URL=http://192.168.1.50/SIA103_Central-Server/public
CENTRAL_API_TOKEN=<the LifeCycleTrack token configured on the Central Server>
CENTRAL_API_TIMEOUT=8
CENTRAL_API_ENABLED=false
```

Replace `192.168.1.50` with the Central Server computer's LAN IPv4 address (find it with `ipconfig` on that computer). Do not use `localhost`, which would refer to the LifeCycleTrack computer itself. Ensure the server's Apache port (usually 80, or 443 for HTTPS) is allowed in Windows Firewall for the Private network. Use HTTPS to protect the bearer token; plain HTTP exposes it to other parties able to observe the Wi-Fi network. Enable the setting only after the Central Server is running, its PostgreSQL schema has been applied, and the LifeCycleTrack token is configured there as `LIFECYCLETRACK_API_TOKEN`. Keep the token out of source control.

LifeCycleTrack posts an allowlisted asset/facility payload to `POST /api/v1/ingest/lifecycle_asset`. It excludes image URLs and personal data. See the Central Server's `docs/API_CONTRACT.md` for the field list and response behavior.
