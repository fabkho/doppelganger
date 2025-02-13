<?php

namespace Concerns;

trait RelationshipTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetConfig();
    }

    protected function resetConfig(): void
    {
        config(['doppelganger.exclude_columns' => []]);
        config(['doppelganger.models' => []]);
    }

    protected function assertModelsSynced($sourceModel, $targetModel, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->assertEquals(
                $sourceModel->$attribute,
                $targetModel->$attribute,
                "Failed asserting that attribute '{$attribute}' was synced correctly"
            );
        }
    }
}
