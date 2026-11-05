<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Question;

class DuplicateDetectionService
{
    /**
     * @var array<string, bool>
     */
    private array $seenHashes = [];

    /**
     * In-memory lookup map of existing database hashes for high-speed O(1) checks.
     *
     * @var array<string, bool>|null
     */
    private ?array $preloadedDbHashes = null;

    /**
     * Preload all existing database question hashes into an O(1) hash map.
     * Prevents issuing thousands of individual SQL queries during bulk import.
     */
    public function preloadExistingHashes(): void
    {
        $this->preloadedDbHashes = Question::withTrashed()
            ->pluck('content_hash')
            ->filter()
            ->flip()
            ->map(fn() => true)
            ->all();
    }

    /**
     * Check if a question text is a duplicate in the database or in the current batch.
     */
    public function isDuplicate(string $questionText): bool
    {
        $hash = Question::normalizeHash($questionText);

        if (isset($this->seenHashes[$hash])) {
            return true;
        }

        if ($this->preloadedDbHashes !== null) {
            $exists = isset($this->preloadedDbHashes[$hash]);
        } else {
            $exists = Question::withTrashed()->where('content_hash', $hash)->exists();
        }

        $this->seenHashes[$hash] = true;

        return $exists;
    }

    /**
     * Check database existence only without registering in local batch cache.
     */
    public function existsInDatabase(string $questionText): bool
    {
        $hash = Question::normalizeHash($questionText);

        if ($this->preloadedDbHashes !== null) {
            return isset($this->preloadedDbHashes[$hash]);
        }

        return Question::withTrashed()->where('content_hash', $hash)->exists();
    }

    /**
     * Clear the internal cache between batches.
     */
    public function reset(): void
    {
        $this->seenHashes = [];
        $this->preloadedDbHashes = null;
    }
}
