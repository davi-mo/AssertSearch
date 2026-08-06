# AI Usage — Foleon Backend Assessment

The assignment explicitly allows AI tools. This document explains **how I used them**, **what I asked for**, and **how I verified the output** — so reviewers can see my workflow, not just the final code.

---

## Tools & models

| Role | Tool / model | Notes                                                                                                                               |
|------|----------------|-------------------------------------------------------------------------------------------------------------------------------------|
| **Pair programming** | [Cursor](https://cursor.com/) — Agent mode | Primary tool for scaffolding, implementation, tests, debugging, and docs                                                            |
| **Coding agent model** | Cursor **Composer** (agent) | Used for multi-file edits, running commands, and iterating on failures                                                              |
| **Project guidance** | Laravel **Boost** (`laravel/boost`) | MCP tools such as `search-docs` for version-specific Laravel / AI SDK docs                                                          |
| **Project skills** | Cursor skills in `.cursor/skills/` | e.g. `laravel-best-practices`, `pest-testing`, `ai-sdk-development` — loaded automatically for relevant tasks (Added to .gitignore) |
| **Application embeddings** | **Ollama** — `nomic-embed-text` (768 dims) | **Not** a coding model — this is the runtime embedding model the PoC uses for semantic search                                       |

I did **not** use a cloud LLM API key for embeddings. Search embeddings run locally via Ollama, as required by the assignment.

---

## How I worked with AI

1. **Step-by-step, aligned with the assignment** — I did not ask for everything at once. Each phase matched the brief: Docker → packages → asset model → sample data → indexing → search → tests → README.
2. **Directed, not passive** — I chose architecture (observer sync, Elasticsearch kNN, SQLite), decided what to gitignore, and cut scope where the brief said to stop.
3. **Verified every phase** — ran `php artisan test`, `curl` against `/search`, and later a **fresh `git clone`** following only the README.
4. **Fixed AI mistakes myself** — e.g. the Elasticsearch `NoNodeAvailableException` caused by `localhost:9200` inside Docker; test helpers not loading in the container’s baked `vendor` volume.

---

## Prompts used (chronological)

These are the main prompts I sent in Cursor, in the order the project was built.

### 1. Setup & infrastructure

```
I need to do this assignment from: Backend_Assignment_-_Foleon.pdf . First, dockerize the project.
```

```
Now require using composer, the following packages: https://laravel.com/ai , https://laravel.com/ai/boost , and elastic search
```

```
Yes, continue the wire up
```

**Outcome:** Docker (app, queue, Elasticsearch), Laravel AI SDK, Boost, Elasticsearch client, Ollama embedding config, `AssetSearchIndex`, `EmbeddingGenerator`.

---

### 2. Core build (assignment order)

```
You can do now the asset model
```

```
Proceed with the sample data seeder
```

```
Now, proceed with Indexing pipeline (core AC)
```

```
Now proceed with Search endpoint (core AC)
```

**Outcome:** `Asset` model, 110-asset seeder (`assets.json`), `AssetObserver` + `AssetIndexer`, `GET /search?q=…`, `php artisan assets:index --seed`.

---

### 3. Debugging (human-driven, AI-assisted)

```
Check this issue:

   Elastic\Transport\Exception\NoNodeAvailableException

  No alive nodes. All the 1 nodes seem to be down.
```

(Same issue reported again after curl failed in the browser.)

**Outcome:** `ELASTICSEARCH_HOST` synced in `docker/entrypoint.sh` to `http://elasticsearch:9200`; search endpoint returns 503 when ES is unreachable instead of throwing.

---

### 4. Finish & polish

```
Summarize what is missing
```

```
Do the test coverage, then do the readme file.
```

```
11 tests are failing. Fix it
```

```
Do the minor polish. Remove the boilerplate that are not relevant for this POC and check if there are files that can be git ignored
```

```
Check the requirements from the assignment and check if we covered everything
```

**Outcome:** README, 32 tests, integration tests for lifecycle/search, boilerplate removed (welcome page, Vite frontend, example tests), `.gitignore` tightened, acceptance criteria verified from a clean clone.

---

## What AI generated vs what I decided

| Area | Mostly AI-assisted | Mostly my call |
|------|--------------------|----------------|
| Docker / compose / entrypoint | ✓ | Verified ports, Ollama on host |
| Laravel services & observers | ✓ | Chose sync observer over queue for PoC |
| Sample asset descriptions | ✓ (drafted) | Reviewed themes & edge cases; committed JSON |
| Tests | ✓ | Asked for lifecycle + semantic edge cases; fixed Docker autoload issue |
| README / AI_USAGE | ✓ (drafted) | Reviewed for accuracy; ran fresh-clone check |
| Architecture trade-offs | Discussed with AI | Final choices documented in README |
| Scope cuts (no auth, no frontend, no CI) | — | Per assignment brief |

---

## How I verified AI output

| Check | When |
|-------|------|
| `php artisan test --compact` | After every major feature |
| `docker compose exec app php artisan test --compact` | After test helper / Docker fixes |
| `curl 'http://localhost:8000/search?q=hiring'` | After search + ES fixes |
| `docker compose exec app php artisan assets:index --seed` | Index pipeline |
| Fresh `git clone` → README quick start only | Before considering delivery complete |
| Read diffs | Before committing; removed unrelated boilerplate |

---

## What I would do differently next time

- Ask for **queue-based indexing** earlier if time allowed — sync observer was fine for the PoC but is the first scale bottleneck.
- Add one **smoke test** against real Ollama + ES in CI — skipped per scope.
- Keep **`AI_USAGE.md` updated as I go** — this file was written at the end from the session history.

---

## For reviewers

- **Embeddings in the app:** `nomic-embed-text` via Ollama — see [README](README.md).
- **Coding assistance:** Cursor Agent + Laravel Boost docs — this file.
- **Commit history:** Real incremental commits, not one squashed dump — reflects the step-by-step prompts above.
