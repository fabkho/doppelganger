<?php

namespace Unit\Relationships;

use fabkho\doppelganger\Doppelganger;
use fabkho\doppelganger\Tests\Models\Organization;
use fabkho\doppelganger\Tests\Models\Resource;
use fabkho\doppelganger\Tests\TestCase;

class HasManyTest extends TestCase
{
    private Doppelganger $doppelganger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doppelganger = $this->app->make(Doppelganger::class);
    }

    /** @test */
    public function it_syncs_hasMany_relationship_correctly()
    {
        // Create organization with multiple locations
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        // Create two resources for the organization
        Resource::on('source')->create([
            'organization_id' => $organization->id,
            'name' => 'Resource 1',
            'configuration' => ['key' => 'value'],
            'is_public' => true
        ]);

        Resource::on('source')->create([
            'organization_id' => $organization->id,
            'name' => 'Resource 2',
            'configuration' => ['key' => 'value'],
            'is_public' => false
        ]);


        // Fresh load to ensure relationships are loaded
        $organization = Organization::on('source')->find($organization->id);

        $this->assertCount(2, $organization->resources, 'Should have 2 services in source');

        // Sync the organization
        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        // Get synced organization with services
        $targetOrg = Organization::on('target')
            ->with(['resources'])
            ->find($result->getNewId());

        // Verify the services were synced
        $this->assertCount(2, $targetOrg->resources, 'Should have 2 services in target');

        // Verify foreign keys were updated correctly
        foreach ($targetOrg->resources as $location) {
            $this->assertEquals($targetOrg->id, $location->organization_id);
        }

        // Verify location data was preserved
        dump($targetOrg->resources);
        $resource = $targetOrg->resources->firstWhere('is_public', true);
        $this->assertNotNull($resource);
        $this->assertEquals('Resource 1', $resource->name);

        $resource1 = $targetOrg->resources->firstWhere('is_public', false);
        $this->assertNotNull($resource);
        $this->assertEquals('Resource 2', $resource1->name);
    }

    /** @test */
    public function it_handles_empty_hasMany_relationship()
    {
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        $targetOrg = Organization::on('target')
            ->with('resources')
            ->find($result->getNewId());

        $this->assertCount(0, $targetOrg->resources);
    }

    /** @test */
    public function it_preserves_relationships_when_syncing_multiple_parents()
    {
        // Create first organization with resources
        $organization1 = Organization::on('source')->create([
            'name' => 'Org 1',
            'status' => 'active'
        ]);

        Resource::on('source')->create([
            'organization_id' => $organization1->id,
            'name' => 'Resource 1',
            'is_public' => true
        ]);

        // Create second organization with resources
        $organization2 = Organization::on('source')->create([
            'name' => 'Org 2',
            'status' => 'active'
        ]);

        Resource::on('source')->create([
            'organization_id' => $organization2->id,
            'name' => 'Resource 2',
            'is_public' => false
        ]);

        // Sync both organizations
        $result1 = $this->doppelganger->sync(Organization::class, $organization1->id);
        $result2 = $this->doppelganger->sync(Organization::class, $organization2->id);

        // Get both target organizations with their resources
        $targetOrg1 = Organization::on('target')->with('resources')->find($result1->getNewId());
        $targetOrg2 = Organization::on('target')->with('resources')->find($result2->getNewId());

        // Each organization should have its own location
        $this->assertCount(1, $targetOrg1->resources);
        $this->assertCount(1, $targetOrg2->resources);

        // resources should be associated with the correct organizations
        $this->assertEquals($targetOrg1->id, $targetOrg1->resources->first()->organization_id);
        $this->assertEquals($targetOrg2->id, $targetOrg2->resources->first()->organization_id);
    }
}
