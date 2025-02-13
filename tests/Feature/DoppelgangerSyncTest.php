<?php

namespace Feature;

use fabkho\doppelganger\Doppelganger;
use fabkho\doppelganger\Tests\Models\Organization;
use fabkho\doppelganger\Tests\TestCase;

class DoppelgangerSyncTest extends TestCase
{
    private Doppelganger $doppelganger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doppelganger = $this->app->make(Doppelganger::class);
    }

    /** @test */
    public function it_can_sync_single_model()
    {
        $organization = Organization::on('source')->create([
            'name' => 'Test Organization'
        ]);

        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization'
        ], 'target');

        $this->assertEquals($organization->id, $result->getOriginalId());
    }

    /** @test */
    public function it_maintains_id_mappings_correctly()
    {
        $organization = Organization::on('source')->create([
            'name' => 'Test Organization'
        ]);

        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        // Sync again should return same target ID
        $secondResult = $this->doppelganger->sync(Organization::class, $organization->id);

        $this->assertEquals($result->getNewId(), $secondResult->getNewId());
        $this->assertEquals(1, Organization::on('target')->count(), 'Should not create duplicate records');
    }

    /** @test */
    public function it_handles_different_column_types()
    {
        $organization = Organization::on('source')->create([
            'name' => 'Test Organization',
            'description' => 'A very long description for testing text columns...',
            'settings' => ['theme' => 'dark', 'notifications' => true],
            'revenue' => 1234567.89,
            'is_active' => true,
            'last_audit' => now(),
            'status' => 'active',
            'metadata' => ['region' => 'EU', 'tier' => 'premium'],
            'employee_count' => 500
        ]);

        $result = $this->doppelganger->sync(Organization::class, $organization->id);
        $targetOrg = Organization::on('target')->find($result->getNewId());

        // Test simple columns
        $this->assertEquals($organization->name, $targetOrg->name);
        $this->assertEquals($organization->description, $targetOrg->description);
        $this->assertEquals($organization->revenue, $targetOrg->revenue);
        $this->assertEquals($organization->is_active, $targetOrg->is_active);
        $this->assertEquals($organization->employee_count, $targetOrg->employee_count);
        $this->assertEquals($organization->status, $targetOrg->status);

        // Test JSON columns
//        dd($organization->settings, $targetOrg->settings);
        $this->assertEquals($organization->settings, $targetOrg->settings);
        $this->assertEquals($organization->metadata, $targetOrg->metadata);

        // Test DateTime
        $this->assertEquals(
            $organization->last_audit->format('Y-m-d H:i:s'),
            $targetOrg->last_audit->format('Y-m-d H:i:s')
        );

        // Test UUID was regenerated but is valid
        $this->assertNotNull($targetOrg->uuid);
        $this->assertNotEquals($organization->uuid, $targetOrg->uuid);
    }
}
