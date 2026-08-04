# Asset Search — Foleon Backend Assessment

Semantic search PoC for marketing assets. Asset descriptions are embedded with Ollama (`nomic-embed-text`), stored in Elasticsearch, and queried via a natural-language search endpoint.

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
# 1. Start all services (app, queue, elasticsearch)
docker compose up -d --build

# 2. Load sample assets and build the search index (~110 assets, takes a minute)
docker compose exec app php artisan assets:index --seed

# 3. Try semantic search
curl -H 'Accept: application/json' 'http://localhost:8000/search?q=hiring'
```

Expected: results about recruiting/headcount even though the word "hiring" does not appear in the asset descriptions.

### Example response

```json
{
  "query": "hiring",
  "results": [
    {
      "id": "ast_2005",
      "name": "campus_recruiting_plan",
      "description": "University recruiting strategy listing target schools, internship cohort size, and campus event calendar for engineering hires.",
      "score": 0.79975593
    }
  ]
}
```

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
php artisan test --compact tests/Feature/Search/AssetSearchTest.php
php artisan test --compact --filter="semantic matches"
```

All **27 tests** should pass.

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
| `curl 'http://localhost:8000/search?q=hiring'` | Search endpoint |

## Architecture

```
Asset (SQLite)
  └─ Observer → AssetIndexer → EmbeddingGenerator (Ollama) → AssetSearchIndex (Elasticsearch)

GET /search?q=… → AssetSearchService → embed query → kNN search → JSON
```

- **Embeddings:** Laravel AI SDK → OpenAI-compatible Ollama API (`nomic-embed-text`, 768 dims)
- **Search:** Elasticsearch kNN on `description_vector`
- **Lifecycle:** create/update/delete on `Asset` syncs the index automatically

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
