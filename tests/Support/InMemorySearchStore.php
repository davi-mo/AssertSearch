<?php

namespace Tests\Support;

class InMemorySearchStore
{
    /**
     * @var array<string, array{asset_id: string, name: string, description: string, embedding: array<int, float>}>
     */
    private array $documents = [];

    /**
     * @param  array<int, float>  $embedding
     */
    public function upsert(string $assetId, string $name, string $description, array $embedding): void
    {
        $this->documents[$assetId] = [
            'asset_id' => $assetId,
            'name' => $name,
            'description' => $description,
            'embedding' => $embedding,
        ];
    }

    public function delete(string $assetId): void
    {
        unset($this->documents[$assetId]);
    }

    /**
     * @param  array<int, float>  $queryVector
     * @return array<int, array{asset_id: string, name: string, description: string, score: float}>
     */
    public function search(array $queryVector, int $limit = 10): array
    {
        if ($this->documents === []) {
            return [];
        }

        $scored = [];

        foreach ($this->documents as $document) {
            $score = cosineSimilarity($queryVector, $document['embedding']);

            if ($score <= 0) {
                continue;
            }

            $scored[] = [
                'asset_id' => $document['asset_id'],
                'name' => $document['name'],
                'description' => $document['description'],
                'score' => $score,
            ];
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    public function count(): int
    {
        return count($this->documents);
    }
}

/**
 * @param  array<int, float>  $a
 * @param  array<int, float>  $b
 */
function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    foreach ($a as $index => $value) {
        $other = $b[$index] ?? 0.0;
        $dot += $value * $other;
        $normA += $value * $value;
        $normB += $other * $other;
    }

    if ($normA === 0.0 || $normB === 0.0) {
        return 0.0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}
