# Asset Search — Foleon Backend Assessment

Semantic search PoC for marketing assets. Asset descriptions are embedded with Ollama (`nomic-embed-text`), stored in Elasticsearch, and queried via a natural-language search endpoint.

**AI usage:** This project was built with Cursor as a pair-programming tool. See [AI_USAGE.md](AI_USAGE.md) for prompts, models, and how output was verified.

## What was built

| Piece | Role |
|-------|------|
| `Asset` model + SQLite | Source of truth for asset metadata |
| `AssetObserver` | Keeps the search index in sync on create/update/delete |
| `AssetIndexer` | Embeds descriptions and upserts/removes documents |
| `AssetSearchIndex` | Elasticsearch index management + kNN search |
| `AssetSearchService` | Embeds the query and maps ES hits to JSON |
| `GET /search?q=…` | Top-10 semantic results with scores |
| `php artisan assets:index --seed` | One command to load sample data and build the index |

### Acceptance criteria

| Criterion | Where it is met |
|-----------|-----------------|
| **Lifecycle** — created assets searchable, updated assets searchable by their *current* description, deleted assets not searchable at all | `AssetObserver` → `AssetIndexer`. Verified in `tests/Feature/Indexing/AssetLifecycleTest.php` and end-to-end in `tests/Feature/Search/AssetSearchIntegrationTest.php` |
| **Smart search** — a query whose words appear in none of the descriptions still returns the right assets | [Example search](#example-search-request-and-response) below, with the full request and response |
| **Runnable** — one documented command takes sample data through to a searchable index | `php artisan assets:index --seed` |

Search reads only from the Elasticsearch index; it never re-reads SQLite. Verified from a clean `git clone` with no code changes and no undocumented steps.

### Why these choices

- **SQLite** — zero setup for reviewers; sufficient for a PoC with ~100 assets.
- **Ollama + `nomic-embed-text`** — local, free embeddings via an OpenAI-compatible API; wired through Laravel AI SDK.
- **Elasticsearch kNN** — purpose-built for vector similarity at small scale; no custom ranking logic needed.
- **Synchronous observer indexing** — simplest lifecycle sync for a PoC; a queue worker is included in Docker for a future async path.
- **Mocked external services in tests** — tests run without Ollama or Elasticsearch; integration tests use an in-memory index with deterministic vectors.

## Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| Docker & Docker Compose | recent | Runs the app, queue worker, and Elasticsearch |
| Ollama | latest | Runs on the **host machine** (not in Docker) |

Pull the embedding model once:

```bash
ollama pull nomic-embed-text
```

Verify Ollama is running:

```bash
curl http://localhost:11434/api/tags
```

## Quick start (recommended for reviewers)

From the project root:

```bash
# 1. Start all services (app, queue, elasticsearch) — first build takes ~1 min
docker compose up -d --build

# 2. Load the 110 committed sample assets and build the search index (a few seconds)
docker compose exec app php artisan assets:index --seed

# 3. Try semantic search
curl -H 'Accept: application/json' 'http://localhost:8000/search?q=hiring'
```

### Example search: request and response

This is the acceptance-criteria example — a query whose word appears in none of the top-ranked descriptions.

**Request**

```bash
curl -H 'Accept: application/json' 'http://localhost:8000/search?q=hiring'
```

**Response** (verbatim, top 10 by cosine similarity)

```json
{
  "query": "hiring",
  "results": [
    {
      "id": "ast_2005",
      "name": "campus_recruiting_plan",
      "description": "University recruiting strategy listing target schools, internship cohort size, and campus event calendar for engineering hires.",
      "score": 0.79975593
    },
    {
      "id": "ast_2003",
      "name": "talent_pipeline_Q3",
      "description": "Recruiting pipeline report with candidate stages for senior backend roles, time-to-fill trends, and offer acceptance rates.",
      "score": 0.78267515
    },
    {
      "id": "ast_2001",
      "name": "IMG_4829",
      "description": "People operations plan outlining open requisitions for platform engineering, expected start dates, and recruiting funnel conversion rates.",
      "score": 0.78266764
    },
    {
      "id": "ast_9006",
      "name": "untitled_document",
      "description": "Draft.",
      "score": 0.7731676
    },
    {
      "id": "ast_2007",
      "name": "onboarding_checklist_hr",
      "description": "HR onboarding checklist covering equipment provisioning, compliance training, and first-week goals for new employees.",
      "score": 0.7661486
    },
    {
      "id": "ast_2011",
      "name": "contractor_policy_update",
      "description": "Updated policy on contractor usage, approval thresholds, and conversion paths to full-time employment.",
      "score": 0.76613533
    },
    {
      "id": "ast_2009",
      "name": "workforce_planning_deck",
      "description": "Workforce planning presentation linking revenue targets to required staffing levels in sales development and customer support.",
      "score": 0.7595037
    },
    {
      "id": "ast_2008",
      "name": "referral_program_brief",
      "description": "Employee referral program overview with bonus tiers, eligibility rules, and quarterly participation statistics.",
      "score": 0.75614357
    },
    {
      "id": "ast_2010",
      "name": "diversity_report_internal",
      "description": "Internal diversity and inclusion metrics report with representation breakdowns by level and hiring source channels.",
      "score": 0.7481712
    },
    {
      "id": "ast_4009",
      "name": "brand_photography_shotlist",
      "description": "Shot list for brand photography featuring diverse teams in office and remote settings for website refresh.",
      "score": 0.74626607
    }
  ]
}
```

**Why this demonstrates semantic search:** none of the top eight descriptions contain the word "hiring". They are matched on meaning — recruiting, headcount, requisitions, workforce planning. The only description that *does* contain the literal word ("hiring source channels", `ast_2010`) ranks 9th, below eight purely semantic matches. Note also `ast_2001`, whose name is `IMG_4829` — a meaningless filename that keyword search could never find.

## Running tests

Tests use an in-memory SQLite database and mock Elasticsearch/Ollama — **no external services required**.

**Inside Docker** (matches the review environment):

```bash
docker compose exec app php artisan test --compact
```

**On the host** (requires PHP 8.4+ and `composer install`):

```bash
composer test
# or
php artisan test --compact
```

Run a single file or test:

```bash
php artisan test --compact tests/Feature/Search/
php artisan test --compact --filter="deleted assets"
```

All **32 tests** should pass. Coverage includes:

- Search endpoint validation, empty results, and ES-unavailable 503
- Root health/info JSON endpoint
- Index → search happy path (service + HTTP)
- Lifecycle sync: create, re-index on update, remove on delete
- Deleted assets absent from search; updated descriptions reflected in results
- Index command, seeder, model, and unit tests for core services

## Environment setup details

### Docker (default)

`docker-compose.yml` injects the required variables. On first boot, `docker/entrypoint.sh`:

- copies `.env.example` → `.env` if missing
- generates `APP_KEY`
- creates `database/database.sqlite`
- syncs `ELASTICSEARCH_HOST=http://elasticsearch:9200` into `.env`

Ollama is reached from the container via `host.docker.internal:11434` (see `EMBEDDING_BASE_URL` in `docker-compose.yml`).

### Local (without Docker)

If you prefer running PHP directly on the host:

```bash
cp .env.example .env
composer install
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

Set in `.env`:

```
ELASTICSEARCH_HOST=http://localhost:9200
EMBEDDING_BASE_URL=http://localhost:11434/v1
```

Start Elasticsearch separately (`docker compose up -d elasticsearch` is enough), then:

```bash
php artisan serve
php artisan assets:index --seed
```

## Useful commands

| Command | Purpose |
|---------|---------|
| `docker compose up -d --build` | Start / rebuild all services |
| `docker compose exec app php artisan assets:index --seed` | Seed DB + index assets |
| `docker compose exec app php artisan assets:index` | Re-index existing assets |
| `docker compose exec app php artisan test --compact` | Run test suite |
| `curl 'http://localhost:8000/'` | Service info JSON |
| `curl 'http://localhost:8000/search?q=hiring'` | Search endpoint |

## Architecture

```
Asset (SQLite)
  └─ Observer → AssetIndexer → EmbeddingGenerator (Ollama) → AssetSearchIndex (Elasticsearch)

GET /search?q=… → AssetSearchService → embed query → kNN search → JSON
```

- **Embeddings:** Laravel AI SDK → OpenAI-compatible Ollama API (`nomic-embed-text`, 768 dims), configured from `EMBEDDING_BASE_URL`, `EMBEDDING_MODEL`, and `EMBEDDING_DIMENSIONS`
- **Search:** Elasticsearch kNN on the `embedding` dense_vector field
- **Lifecycle:** create/update/delete on `Asset` syncs the index automatically

## Known limitations

Stated plainly rather than hidden:

- **Thin descriptions rank noisily.** `ast_9006` ("Draft.") lands 4th for `?q=hiring` purely because a near-empty string produces an unhelpful vector. This is a deliberate edge case in the sample data. A minimum-score threshold or a length guard at index time would filter it.
- **`name` is stored but not embedded.** Only `description` is vectorised, per the assignment. Names are returned in results but do not influence ranking. Embedding `name + description` together, or scoring them separately and blending, would likely improve recall for the cases where names *are* meaningful.
- **Indexing is synchronous.** The observer embeds and upserts inside the request/command. Fine at 110 assets; it would need the queue at scale.
- **No retry or dead-lettering.** If Ollama or Elasticsearch is down mid-write, that asset silently misses the index until the next full `assets:index` run.

## Out of scope / next steps

Intentionally skipped for this PoC:

- Authentication and authorization
- Frontend UI
- Production deployment and CI pipeline
- Pagination, filtering, or hybrid BM25 + vector search
- Async indexing via the queue worker (container is ready; observer still indexes synchronously)

If this were going to production, next steps would be:

1. **Queue indexing jobs** — move embed + upsert off the request/observer path
2. **Hybrid retrieval** — combine keyword (BM25) and vector scores for better recall
3. **Relevance tuning** — minimum score threshold, reranking, query expansion
4. **Observability** — index lag metrics, embedding latency, search quality evals
5. **CI integration test** — optional smoke test against real ES + Ollama in a pipeline

## Troubleshooting

**`NoNodeAvailableException` / "No alive nodes"**

The app container must use `http://elasticsearch:9200`, not `http://localhost:9200`. Rebuild and restart:

```bash
docker compose build app
docker compose up -d
```

**503 "Cannot reach Elasticsearch"**

Elasticsearch is not running or still starting. Wait for the health check, then retry:

```bash
docker compose up -d elasticsearch
curl http://localhost:9200/_cluster/health
```

**Indexing fails / embedding errors**

Ollama is not running on the host or `nomic-embed-text` is not pulled:

```bash
ollama serve          # if not already running
ollama pull nomic-embed-text
```

**Search returns empty results**

The index may not be built yet:

```bash
docker compose exec app php artisan assets:index --seed
```
