<?php

namespace Unit\Core;

use fabkho\doppelganger\IdMapper;
use fabkho\doppelganger\Tests\TestCase;
use RuntimeException;

class IdMapperTest extends TestCase
{
    private IdMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new IdMapper();
    }

    /** @test */
    public function it_can_store_and_retrieve_mappings()
    {
        $this->mapper->add('organizations', 1, 2);

        $this->assertEquals(2, $this->mapper->get('organizations', 1));
    }

    /** @test */
    public function it_can_handle_multiple_tables()
    {
        $this->mapper->add('organizations', 1, 2);
        $this->mapper->add('resources', 1, 3);

        $this->assertEquals(2, $this->mapper->get('organizations', 1));
        $this->assertEquals(3, $this->mapper->get('resources', 1));
    }

    /** @test */
    public function it_throws_exception_for_unknown_mapping()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No mapping found for table 'unknown_table' with source ID '1'");

        $this->mapper->get('unknown_table', 1);
    }

    /** @test */
    public function it_can_check_if_mapping_exists()
    {
        $this->mapper->add('organizations', 1, 2);

        $this->assertTrue($this->mapper->has('organizations', 1));
        $this->assertFalse($this->mapper->has('organizations', 2));
    }

    /** @test */
    public function it_can_get_all_mappings_for_table()
    {
        $this->mapper->add('organizations', 1, 2);
        $this->mapper->add('organizations', 3, 4);

        $expected = [
            1 => 2,
            3 => 4
        ];

        $this->assertEquals($expected, $this->mapper->getTableMappings('organizations'));
    }

    /** @test */
    public function it_can_create_temporary_mappings()
    {
        $tempId = $this->mapper->addTemp('organizations', 1);

        $this->assertStringStartsWith('temp_', $tempId);
        $this->assertTrue($this->mapper->has('organizations', 1));
        $this->assertEquals($tempId, $this->mapper->get('organizations', 1));
    }

    /** @test */
    public function it_can_confirm_temporary_mappings()
    {
        $tempId = $this->mapper->addTemp('organizations', 1);
        $this->mapper->confirmTemp('organizations', 1, 5);

        $this->assertEquals(5, $this->mapper->get('organizations', 1));
    }

    /** @test */
    public function it_includes_temporary_mappings_in_table_mappings()
    {
        $tempId = $this->mapper->addTemp('organizations', 1);
        $this->mapper->add('organizations', 2, 3);

        $mappings = $this->mapper->getTableMappings('organizations');

        $this->assertCount(2, $mappings);
        $this->assertEquals($tempId, $mappings[1]);
        $this->assertEquals(3, $mappings[2]);
    }

    /** @test */
    public function it_handles_multiple_temporary_mappings()
    {
        $tempId1 = $this->mapper->addTemp('organizations', 1);
        $tempId2 = $this->mapper->addTemp('organizations', 2);

        $this->assertNotEquals($tempId1, $tempId2);
        $this->assertTrue($this->mapper->has('organizations', 1));
        $this->assertTrue($this->mapper->has('organizations', 2));
    }

    /** @test */
    public function it_clears_temporary_mappings_after_confirmation()
    {
        $tempId = $this->mapper->addTemp('organizations', 1);
        $this->mapper->confirmTemp('organizations', 1, 5);

        $mappings = $this->mapper->getTableMappings('organizations');

        $this->assertCount(1, $mappings);
        $this->assertEquals(5, $mappings[1]);
        $this->assertArrayNotHasKey($tempId, $mappings);
    }

    /** @test */
    public function it_clears_all_mappings()
    {
        $this->mapper->add('organizations', 1, 2);
        $this->mapper->addTemp('resources', 3, 4);

        $this->mapper->clear();

        $this->assertEmpty($this->mapper->getTableMappings('organizations'));
        $this->assertEmpty($this->mapper->getTableMappings('resources'));
    }

    /** @test */
    public function it_prioritizes_confirmed_mappings_over_temporary_ones()
    {
        $tempId = $this->mapper->addTemp('organizations', 1);
        $this->mapper->add('organizations', 1, 5);

        $this->assertEquals(5, $this->mapper->get('organizations', 1));
    }

    /** @test */
    public function it_handles_string_ids()
    {
        $this->mapper->add('organizations', 'abc-123', 'xyz-456');
        $this->assertEquals('xyz-456', $this->mapper->get('organizations', 'abc-123'));
    }

    /** @test */
    public function it_handles_mixed_id_types()
    {
        $this->mapper->add('organizations', 1, 'abc-123');
        $this->mapper->add('organizations', 'def-456', 2);

        $this->assertEquals('abc-123', $this->mapper->get('organizations', 1));
        $this->assertEquals(2, $this->mapper->get('organizations', 'def-456'));
    }

    /** @test */
    public function it_can_confirm_temp_mapping_for_nonexistent_table()
    {
        $this->mapper->confirmTemp('new_table', 1, 2);
        $this->assertEquals(2, $this->mapper->get('new_table', 1));
    }
}
