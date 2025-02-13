<?php

namespace Unit\Relationships;

use fabkho\doppelganger\Doppelganger;
use fabkho\doppelganger\Tests\Models\Organization;
use fabkho\doppelganger\Tests\Models\Resource;
use fabkho\doppelganger\Tests\Models\Service;
use fabkho\doppelganger\Tests\TestCase;

class BelongsToTest extends TestCase
{
    private Doppelganger $doppelganger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doppelganger = $this->app->make(Doppelganger::class);
    }

    /** @test */
    public function it_syncs_belongsTo_relationship_correctly()
    {
        // Create organization first
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        // Create resource that belongs to organization
        $resource = Resource::on('source')->create([
            'name' => 'Test Resource',
            'organization_id' => $organization->id,
            'configuration' => ['key' => 'value'],
            'is_public' => true
        ]);

        // Sync the resource
        $result = $this->doppelganger->sync(Resource::class, $resource->id);

//        dd($result);

        // Get the synced resource from target
        $targetResource = Resource::on('target')->find($result->getNewId());

//        dd($targetResource);

        // Load the organization relationship
        $targetResource->load('organization');

        // Check that the organization was synced
        $this->assertNotNull($targetResource->organization);
        $this->assertEquals($organization->name, $targetResource->organization->name);
        $this->assertEquals($targetResource->organization_id, $targetResource->organization->id);
    }

    /** @test */
    public function it_handles_missing_parent_models()
    {
        // Create a resource with a non-existent organization_id
        $resource = Resource::on('source')->create([
            'name' => 'Test Resource',
            'organization_id' => 999999, // Non-existent ID
            'configuration' => ['key' => 'value'],
            'is_public' => true
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Parent model not found");

        $this->doppelganger->sync(Resource::class, $resource->id);
    }

    /** @test */
    public function it_syncs_nested_belongsTo_relationships()
    {
        // Create the full hierarchy
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        $resource = Resource::on('source')->create([
            'name' => 'Test Resource',
            'organization_id' => $organization->id,
            'configuration' => ['key' => 'value'],
            'is_public' => true
        ]);

        $service = Service::on('source')->create([
            'name' => 'Test Service',
            'resource_id' => $resource->id,
            'config' => ['port' => 8080],
            'status' => 'running'
        ]);

        // Sync from the deepest level
        $result = $this->doppelganger->sync(Service::class, $service->id);

        // Get the synced service with all its relationships
        $targetService = Service::on('target')
            ->with('resource.organization')
            ->find($result->getNewId());

        // Verify the entire hierarchy
        $this->assertNotNull($targetService->resource);
        $this->assertNotNull($targetService->resource->organization);

        // Check that all foreign keys are properly maintained
        $this->assertEquals($targetService->resource_id, $targetService->resource->id);
        $this->assertEquals($targetService->resource->organization_id, $targetService->resource->organization->id);

        // Verify the data was copied correctly
        $this->assertEquals($service->name, $targetService->name);
        $this->assertEquals($resource->name, $targetService->resource->name);
        $this->assertEquals($organization->name, $targetService->resource->organization->name);
    }

    /** @test */
    public function it_preserves_existing_parent_models()
    {
        // Create and sync an organization first
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        $orgResult = $this->doppelganger->sync(Organization::class, $organization->id);
        $targetOrgId = $orgResult->getNewId();

        // Now create and sync a resource that belongs to this organization
        $resource = Resource::on('source')->create([
            'name' => 'Test Resource',
            'organization_id' => $organization->id,
            'configuration' => ['key' => 'value'],
            'is_public' => true
        ]);

        $resourceResult = $this->doppelganger->sync(Resource::class, $resource->id);

        // Get the synced resource
        $targetResource = Resource::on('target')->with('organization')->find($resourceResult->getNewId());

        // Verify that it uses the existing organization and didn't create a duplicate
        $this->assertEquals($targetOrgId, $targetResource->organization->id);

        // Verify that only one organization exists in the target database
        $this->assertEquals(1, Organization::on('target')->count());
    }
}
