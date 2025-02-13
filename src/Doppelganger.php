<?php

namespace fabkho\doppelganger;

use Exception;
use fabkho\doppelganger\Traits\Synchronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class Doppelganger
{
    private string $sourceConnection;
    private string $targetConnection;
    private bool $safeMode;
    private int $batchSize;
    private IdMapper $idMapper;

    public function __construct()
    {
        $this->sourceConnection = config('doppelganger.source_connection');
        $this->targetConnection = config('doppelganger.target_connection');
        $this->safeMode = config('doppelganger.safe_mode.enabled', false);
        $this->batchSize = config('doppelganger.batch_size', 100);
        $this->idMapper = new IdMapper();

        if (empty($this->sourceConnection)) {
            throw new RuntimeException('Source connection must be configured');
        }

        if (empty($this->targetConnection)) {
            throw new RuntimeException('Target connection must be configured');
        }

        if ($this->batchSize <= 0) {
            throw new RuntimeException('Batch size must be a positive integer');
        }
    }

    public function from(string $connection): self
    {
        $this->sourceConnection = $connection;
        return $this;
    }

    public function to(string $connection): self
    {
        $this->targetConnection = $connection;
        return $this;
    }

    public function useSafeMode(bool $enabled = true): self
    {
        $this->safeMode = $enabled;
        return $this;
    }

    public function setBatchSize(int $size): self
    {
        if ($size <= 0) {
            throw new RuntimeException('Batch size must be a positive integer');
        }
        $this->batchSize = $size;
        return $this;
    }

    public function getSourceConnection(): string
    {
        return $this->sourceConnection;
    }

    public function getTargetConnection(): string
    {
        return $this->targetConnection;
    }

    public function isSafeModeEnabled(): bool
    {
        return $this->safeMode;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Check if model uses the Synchronizable trait
     */
    protected function usesSynchronizable($class): bool
    {
        return in_array(Synchronizable::class, class_uses_recursive($class));
    }

    /**
     * Sync a model and its relationships from source to target connection
     * @throws Exception
     * @throws Throwable
     */
    public function sync(string $modelClass, $id): SyncResult
    {
        if (!$this->usesSynchronizable($modelClass)) {
            throw new RuntimeException("Model must use Synchronizable trait");
        }

        DB::connection($this->sourceConnection)->beginTransaction();
        DB::connection($this->targetConnection)->beginTransaction();

        try {
            $sourceModel = $modelClass::on($this->sourceConnection)->findOrFail($id);

            if (!$sourceModel->shouldBeSynchronized()) {
                throw new RuntimeException("Model is not eligible for synchronization");
            }

            $targetModel = $this->copyModel($sourceModel);

            DB::connection($this->sourceConnection)->commit();
            DB::connection($this->targetConnection)->commit();

            return new SyncResult($sourceModel->getKey(), $targetModel->getKey());
        } catch (Exception $e) {
            DB::connection($this->sourceConnection)->rollBack();
            DB::connection($this->targetConnection)->rollBack();
            throw $e;
        }
    }

    /**
     * Copy a model from source to target with ID mapping
     */
    /**
     * Copy a model from source to target with ID mapping
     */
    protected function copyModel(Model $sourceModel): Model
    {
        if (!$this->usesSynchronizable($sourceModel)) {
            throw new RuntimeException("Model must use Synchronizable trait");
        }

        // Check if model was already synced
        if ($this->idMapper->has($sourceModel->getTable(), $sourceModel->getKey())) {
            return $sourceModel::on($this->targetConnection)
                ->findOrFail($this->idMapper->get($sourceModel->getTable(), $sourceModel->getKey()));
        }

        // Get synchronized data
        $attributes = $sourceModel->toSynchronizedArray();

        // Create and save the target model
        $targetModel = new (get_class($sourceModel));
        $targetModel->setConnection($this->targetConnection);
        $targetModel->fill($attributes);

        // Add temp mapping before saving
        $this->idMapper->addTemp($sourceModel->getTable(), $sourceModel->getKey());

        // Save the model to get its ID
        $targetModel->save();

        // Confirm the mapping with real ID
        $this->idMapper->confirmTemp($sourceModel->getTable(), $sourceModel->getKey(), $targetModel->getKey());

        // Sync relationships
        foreach ($sourceModel->synchronizableRelations() as $relationName => $config) {
            if (!method_exists($sourceModel, $relationName)) {
                continue;
            }

            // Eager load the relationship if not loaded
            if (!$sourceModel->relationLoaded($relationName)) {
                $sourceModel->load($relationName);
            }

            $relation = $sourceModel->$relationName();

            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $this->syncBelongsTo($sourceModel, $targetModel, $relation, $relationName);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                $this->syncHasOneRelationship($sourceModel, $targetModel, $relation, $relationName);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                $this->syncHasManyRelationship($sourceModel, $targetModel, $relation, $relationName);
            }
        }

        // Refresh the model to ensure all relationships are loaded
        $targetModel->refresh();

        // Run any post-sync tasks
        $sourceModel->afterSync($targetModel);

        return $targetModel;
    }
    /**
     * Sync BelongsTo relationship
     */
    protected function syncBelongsTo(Model $sourceModel, Model $targetModel, BelongsTo $relation, string $relationName): void
    {
        // Get the parent model
        $foreignKey = $relation->getForeignKeyName();
        $parentId = $sourceModel->$foreignKey;

        if (!$parentId) {
            return; // Skip if no parent is set
        }

        // Load the parent model
        $parentModel = $relation->getRelated()->on($this->sourceConnection)->find($parentId);
        if (!$parentModel) {
            throw new RuntimeException("Parent model not found");
        }

        if (!$this->usesSynchronizable($parentModel)) {
            return;
        }

        // Sync the parent model if it hasn't been synced yet
        if (!$this->idMapper->has($parentModel->getTable(), $parentModel->getKey())) {
            $targetParent = $this->copyModel($parentModel);

            // Update the foreign key to point to the new parent ID
            $targetModel->$foreignKey = $targetParent->getKey();
            $targetModel->save();
        } else {
            // If parent was already synced, just update the foreign key
            $targetModel->$foreignKey = $this->idMapper->get($parentModel->getTable(), $parentModel->getKey());
            $targetModel->save();
        }
    }

    /**
     * Sync HasOne relationship
     */
    protected function syncHasOneRelationship(Model $sourceModel, Model $targetModel, \Illuminate\Database\Eloquent\Relations\HasOne $relation, string $relationName): void
    {
        // Eager load the relationship if not loaded
        if (!$sourceModel->relationLoaded($relationName)) {
            $sourceModel->load($relationName);
        }

        $relatedModel = $sourceModel->$relationName;
        if (!$relatedModel || !$this->usesSynchronizable($relatedModel)) {
            return;
        }

        // Create new relation
        $targetRelatedModel = $this->copyModel($relatedModel);

        // Update foreign key
        $foreignKey = $relation->getForeignKeyName();
        $targetRelatedModel->$foreignKey = $targetModel->getKey();
        $targetRelatedModel->save();

        // Refresh target model to ensure relationship is loaded
        $targetModel->refresh();
    }

    /**
     * Sync HasMany relationship
     */
    protected function syncHasManyRelationship(Model $sourceModel, Model $targetModel, HasMany $relation, string $relationName): void
    {
        $relatedModels = $sourceModel->$relationName;
        if (!$relatedModels) {
            return;
        }

        foreach ($relatedModels as $relatedModel) {
            if (!$this->usesSynchronizable($relatedModel)) {
                continue;
            }

            $targetRelatedModel = $this->copyModel($relatedModel);

            // Update foreign key
            $foreignKey = $relation->getForeignKeyName();
            if (method_exists($targetRelatedModel, $foreignKey)) {
                $targetRelatedModel->$foreignKey = $targetModel->getKey();
                $targetRelatedModel->save();
            }
        }
    }
}
