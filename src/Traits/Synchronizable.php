<?php

namespace fabkho\doppelganger\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

trait Synchronizable
{
    /**
     * Custom synchronization handlers for specific attributes
     */
    protected array $doppelgangerHandlers = [];

    /**
     * Boot the trait.
     */
    public static function bootSynchronizable()
    {
        // This could be used to add global scopes or observers if needed
    }

    /**
     * Get the relationships that should be excluded from synchronization.
     * Override this method to exclude specific relationships.
     */
    public function excludeFromSync(): array
    {
        return [];
    }

    /**
     * Get all synchronizable relationships for this model
     */
    public function synchronizableRelations(): array
    {
        $relationships = [];
        $excludedRelations = $this->excludeFromSync();

        // Get all public methods
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            // Skip if method is in exclude list
            if (in_array($method->getName(), $excludedRelations)) {
                continue;
            }

            // Skip if method has parameters (relationships don't have parameters)
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                if (!$returnType) {
                    continue;
                }

                $returnTypeName = $returnType->getName();

                // Check if return type is a Relation or one of its subclasses
                if (!is_subclass_of($returnTypeName, Relation::class)) {
                    continue;
                }

                // Call the method to get the relationship
                $relationship = $method->invoke($this);
                if ($relationship instanceof Relation) {
                    $relationships[$method->getName()] = true;
                }
            } catch (\Throwable $e) {
                // Skip methods that throw exceptions when called
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Get the columns that should be excluded from synchronization.
     */
    public function excludedFromSync(): array
    {
        return [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];
    }

    /**
     * Get validation rules for synchronization.
     */
    public function getSyncValidationRules(): array
    {
        return [];
    }

    /**
     * Determine if the model should be synchronized.
     */
    public function shouldBeSynchronized(): bool
    {
        return true;
    }

    /**
     * Prepare the model's data for synchronization.
     */
    public function toSynchronizedArray(): array
    {
        $attributes = $this->toArray(); // Automatically applies casts

        foreach ($this->excludedFromSync() as $column) {
            unset($attributes[$column]);
        }

        foreach ($attributes as $key => $value) {
            if ($handler = $this->doppelgangerHandlers[$key] ?? null) {
                $attributes[$key] = $handler($value, $this);
            }
        }

        return $attributes;
    }

    /**
     * Register a custom sync handler for a specific attribute
     */
    public function withSyncHandler(string $attribute, callable $handler): self
    {
        $this->doppelgangerHandlers[$attribute] = $handler;
        return $this;
    }

    /**
     * Handle any post-synchronization tasks
     */
    public function afterSync($targetModel): void
    {
        //
    }

    /**
     * Get the sync connection that should be used when syncing.
     */
    public function syncConnection(): string
    {
        return config('doppelganger.target_connection', 'target');
    }

    /**
     * Get the batch size that should be used when syncing.
     */
    public function syncBatchSize(): int
    {
        return config('doppelganger.batch_size', 100);
    }

    /**
     * Determine if safe mode should be used when syncing.
     */
    public function shouldUseSafeMode(): bool
    {
        return config('doppelganger.safe_mode.enabled', false);
    }
}
