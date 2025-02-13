<?php

namespace fabkho\doppelganger;

class IdMapper
{
    /**
     * @var array<string, array<int|string, int|string>>
     */
    private array $mappings = [];

    /**
     * @var array<string, array<int|string, string>>
     */
    private array $tempMappings = [];

    /**
     * Add a temporary mapping and return the temporary ID
     */
    public function addTemp(string $table, $sourceId): string
    {
        if (!isset($this->tempMappings[$table])) {
            $this->tempMappings[$table] = [];
        }

        $tempId = 'temp_' . uniqid();
        $this->tempMappings[$table][$sourceId] = $tempId;

        return $tempId;
    }

    /**
     * Confirm a temporary mapping with the real target ID
     */
    public function confirmTemp(string $table, $sourceId, $targetId): void
    {
        if (!isset($this->mappings[$table])) {
            $this->mappings[$table] = [];
        }

        $this->mappings[$table][$sourceId] = $targetId;

        // Remove temp mapping if it exists
        if (isset($this->tempMappings[$table][$sourceId])) {
            unset($this->tempMappings[$table][$sourceId]);
        }
    }

    public function add(string $table, $sourceId, $targetId): void
    {
        if (!isset($this->mappings[$table])) {
            $this->mappings[$table] = [];
        }

        $this->mappings[$table][$sourceId] = $targetId;
    }

    public function get(string $table, $sourceId): int|string
    {
        // Check real mappings first
        if (isset($this->mappings[$table][$sourceId])) {
            return $this->mappings[$table][$sourceId];
        }

        // Then check temp mappings
        if (isset($this->tempMappings[$table][$sourceId])) {
            return $this->tempMappings[$table][$sourceId];
        }

        throw new \RuntimeException("No mapping found for table '{$table}' with source ID '{$sourceId}'");
    }

    public function has(string $table, $sourceId): bool
    {
        return isset($this->mappings[$table][$sourceId]) ||
            isset($this->tempMappings[$table][$sourceId]);
    }

    public function getTableMappings(string $table): array
    {
        $result = [];

        // Add confirmed mappings
        if (isset($this->mappings[$table])) {
            $result = $this->mappings[$table];
        }

        // Add temp mappings, but don't overwrite confirmed ones
        if (isset($this->tempMappings[$table])) {
            foreach ($this->tempMappings[$table] as $sourceId => $tempId) {
                if (!isset($result[$sourceId])) {
                    $result[$sourceId] = $tempId;
                }
            }
        }

        return $result;
    }

    public function clear(): void
    {
        $this->mappings = [];
        $this->tempMappings = [];
    }
}
