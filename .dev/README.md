# Centreon plugin — local test environment

The plugin talks to the **Centreon REST API v2** (`src/ApiClient.php`, Guzzle)
to read host status, services, timeline and downtimes, and to push checks,
downtimes and acknowledgements.

A full Centreon stack (web + Engine + Broker + MariaDB) is not reproducible in
a dev environment, so this folder ships a **[WireMock](https://wiremock.org/)
service** that answers the exact endpoints the plugin calls with canned JSON
fixtures.

## What's here

| Path | Purpose |
|------|---------|
| `docker-compose.centreon.yaml` | Purely additive: declares only the `centreon-api` service (never touches `app`) |
| `wiremock/mappings/*.json` | One stub per Centreon endpoint the plugin calls |
| `wiremock/__files/*.json` | Response bodies for the larger stubs |

> **Why additive?** When `app` is a VS Code Dev Container, `docker compose …
> up --build` recreates it *without* the Dev Container overrides (`/vscode`
> mount, keep-alive command) and the running container breaks. This file only
> adds the `centreon-api` service, so layering it can never recreate `app`.

The mock is **stateless** — no PHP extension to install in `app`, no data
volume. Restarting it reloads the fixtures from disk.

## Requirements

- The GLPI core docker stack (`docker-compose.yaml` at the GLPI root), with
  the `app` container running.
- Any CPU arch — the `wiremock/wiremock` image is multi-arch.

## Usage

`make` targets live in `plugins/centreon/Makefile`; run them from the plugin
directory (`plugins/centreon`).

```bash
# start the mock Centreon API next to the dev stack
make centreon-env-up

# sanity check: the app container can reach it and gets a token back
make centreon-verify
```

Teardown (the mock holds no state, so there is nothing else to clean):

```bash
make centreon-env-down
```

The published port `localhost:12088` is for poking at the mock from the host
(`curl`, browser, the WireMock admin API under `/__admin`). From inside the
`app` container the service is `http://centreon-api:8080`.

## Plugin configuration to enter in GLPI

Install / enable the plugin as usual (`make install`, `make enable`), then go
to *Setup → Centreon settings* (Config tab) and fill in:

| Field | Value |
|-------|-------|
| Centreon URL | `http://centreon-api:8080/centreon/api/latest/` — **trailing slash required** (Guzzle resolves the endpoints relative to it), and use the service name, **not** `localhost:12088` which is only reachable from the host |
| Username | anything, e.g. `glpi-dev` |
| Password | anything, e.g. `glpi-dev` |

A green *"You are connected to Centreon API !"* badge confirms the mock
answered the `login` call.

## Exercising the host tab

The plugin links a GLPI item to a Centreon host **by name**
(`Host::searchItemMatch()`), so:

1. Create a **Computer** named `centreon-demo` (the name returned by the
   `monitoring/hosts` fixture).
2. Open it → **Centreon** tab. You should see status `UP`, the 3 fixture
   services, and the timeline / action buttons.

All host ids are accepted by the mock (`urlPathPattern` on `[0-9]+`), so the
`centreon_id` stored by the match (88) or any other value resolves.

## Editing the fixtures

Stubs are plain JSON under `wiremock/`. After editing, reload them with:

```bash
curl -X POST http://localhost:12088/__admin/mappings/reset
```

or just `make centreon-env-down && make centreon-env-up`.

Endpoints currently stubbed (base path `/centreon/api/latest`):

| Method | Path | Plugin method |
|--------|------|---------------|
| POST | `/login` | `connectionRequest`, `diagnostic` |
| GET | `/monitoring/hosts` | `getHostsList` (name match) |
| GET | `/monitoring/hosts/{id}` | `getOneHost` |
| GET | `/monitoring/resources/hosts/{id}` | `getOneHostResources` |
| GET | `/monitoring/hosts/{id}/services` | `getServicesListForOneHost` |
| GET | `/monitoring/hosts/{id}/timeline` | `getOneHostTimeline` |
| GET/POST | `/monitoring/hosts/{id}/downtimes` | `listDowntimes` / `setDowntimeOnAHost` |
| POST | `/monitoring/hosts/{id}/check` | `sendCheckToAnHost` |
| POST | `/monitoring/hosts/{id}/acknowledgements` | `acknowledgement` |
| GET/DELETE | `/monitoring/downtimes/{id}` | `displayDowntime` / `cancelDowntime` |
| GET | `/monitoring/services` , `/monitoring/services/downtimes` | `getServicesList` / `servicesDowntimesByHost` |

## Notes

- `docker-compose.centreon.yaml` is **layered** on core via `-f` (see the
  Makefile), so it never edits a GLPI core file and needs no
  `docker-compose.override.yaml`. It is purely additive (only `centreon-api`),
  so it never recreates `app`.
- `app` never depends on `centreon-api`, so teardown only affects the mock.
