<?php

namespace Unit\Relationships;

use fabkho\doppelganger\Doppelganger;
use fabkho\doppelganger\Tests\Models\Organization;
use fabkho\doppelganger\Tests\Models\OrganizationSettings;
use fabkho\doppelganger\Tests\TestCase;

class HasOneTest extends TestCase
{
    private Doppelganger $doppelganger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doppelganger = $this->app->make(Doppelganger::class);
    }

    /** @test */
    public function it_syncs_hasOne_relationship_correctly()
    {
        // Create organization with settings
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        OrganizationSettings::on('source')->create([
            'organization_id' => $organization->id,
            'default_language' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'notification_settings' => ['email' => true]
        ]);

        // Fresh load to ensure relationship is loaded
        $organization = Organization::on('source')->find($organization->id);

        $this->assertNotNull($organization->settings, 'Settings should exist in source');

        // Sync the organization
        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        // Get synced organization with settings
        $targetOrg = Organization::on('target')
            ->with('settings')
            ->find($result->getNewId());

        // Verify the settings were synced
        $this->assertNotNull($targetOrg->settings, 'Settings should exist in target');
        $this->assertEquals('en', $targetOrg->settings->default_language);
        $this->assertEquals('UTC', $targetOrg->settings->timezone);
        $this->assertEquals(['email' => true], $targetOrg->settings->notification_settings);

        // Verify foreign key relationship
        $this->assertEquals($targetOrg->id, $targetOrg->settings->organization_id);
    }

    /** @test */
    public function it_handles_null_hasOne_relationship()
    {
        $organization = Organization::on('source')->create([
            'name' => 'Test Org',
            'status' => 'active'
        ]);

        $result = $this->doppelganger->sync(Organization::class, $organization->id);

        $targetOrg = Organization::on('target')
            ->with(['settings', 'location'])
            ->find($result->getNewId());

        $this->assertNull($targetOrg->settings);
    }

    /** @test */
    public function it_preserves_foreign_key_relationships_when_syncing_multiple_related_models()
    {
        // Create first organization with settings
        $organization1 = Organization::on('source')->create([
            'name' => 'Org 1',
            'status' => 'active'
        ]);

        OrganizationSettings::on('source')->create([
            'organization_id' => $organization1->id,
            'default_language' => 'en',
            'timezone' => 'UTC'
        ]);

        // Create second organization with settings
        $organization2 = Organization::on('source')->create([
            'name' => 'Org 2',
            'status' => 'active'
        ]);

        OrganizationSettings::on('source')->create([
            'organization_id' => $organization2->id,
            'default_language' => 'de',
            'timezone' => 'Europe/Berlin'
        ]);

        // Sync both organizations
        $result1 = $this->doppelganger->sync(Organization::class, $organization1->id);
        $result2 = $this->doppelganger->sync(Organization::class, $organization2->id);

        // Verify relationships are preserved correctly
        $targetOrg1 = Organization::on('target')->with('settings')->find($result1->getNewId());
        $targetOrg2 = Organization::on('target')->with('settings')->find($result2->getNewId());

        // Settings should be associated with the correct organizations
        $this->assertEquals('en', $targetOrg1->settings->default_language);
        $this->assertEquals('de', $targetOrg2->settings->default_language);

        // Foreign keys should match their parent IDs
        $this->assertEquals($targetOrg1->id, $targetOrg1->settings->organization_id);
        $this->assertEquals($targetOrg2->id, $targetOrg2->settings->organization_id);
    }
}
