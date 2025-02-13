<?php

namespace Unit\Core;


use fabkho\doppelganger\Doppelganger;
use fabkho\doppelganger\Tests\TestCase;

class DoppelgangerServiceTest extends TestCase
{
    private Doppelganger $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(Doppelganger::class);
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Doppelganger::class, $this->service);
    }

    /** @test */
    public function it_can_get_source_and_target_connections()
    {
        $this->assertEquals('source', $this->service->getSourceConnection());
        $this->assertEquals('target', $this->service->getTargetConnection());
    }

    /** @test */
    public function it_can_set_custom_connections()
    {
        $this->service->from('custom_source')->to('custom_target');

        $this->assertEquals('custom_source', $this->service->getSourceConnection());
        $this->assertEquals('custom_target', $this->service->getTargetConnection());
    }

    /** @test */
    public function it_can_enable_safe_mode()
    {
        $this->service->useSafeMode();

        $this->assertTrue($this->service->isSafeModeEnabled());
    }

    /** @test */
    public function it_can_set_batch_size()
    {
        $this->service->setBatchSize(50);

        $this->assertEquals(50, $this->service->getBatchSize());
    }
}
