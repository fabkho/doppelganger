<?php

namespace Unit\Config;

use fabkho\doppelganger\Tests\Models\Organization;
use fabkho\doppelganger\Tests\Models\Resource;
use fabkho\doppelganger\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_loads_default_configuration()
    {
        $config = config('doppelganger');

        $this->assertEquals('source', $config['source_connection']);
        $this->assertEquals('target', $config['target_connection']);
        $this->assertEquals(100, $config['batch_size']);
        $this->assertEquals(600, $config['timeout']);
    }

    /** @test */
    public function it_can_configure_models_and_their_relationships()
    {
        config()->set('doppelganger.models', [
            Organization::class => [
                'relationships' => [
                    'resources' => [
                        'model' => Resource::class,
                        'type' => 'hasMany'
                    ]
                ]
            ]
        ]);

        $config = config('doppelganger.models');

        $this->assertArrayHasKey(Organization::class, $config);
        $this->assertArrayHasKey('relationships', $config[Organization::class]);
        $this->assertEquals(Resource::class, $config[Organization::class]['relationships']['resources']['model']);
    }

    /** @test */
    public function it_has_safe_mode_configuration()
    {
        $config = config('doppelganger.safe_mode');

        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('seed_path', $config);
        $this->assertArrayHasKey('cleanup_after', $config);
    }
}
