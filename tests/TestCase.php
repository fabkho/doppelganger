<?php

namespace fabkho\doppelganger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use fabkho\doppelganger\DoppelgangerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DoppelgangerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set the default database connection
        $app['config']->set('database.default', 'source');

        // Configure source database as SQLite in-memory
        $app['config']->set('database.connections.source', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure target database as separate SQLite in-memory
        $app['config']->set('database.connections.target', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup package configuration
        $app['config']->set('doppelganger', [
            'source_connection' => 'source',
            'target_connection' => 'target',
            'batch_size' => 100,
            'timeout' => 600,
            'safe_mode' => [
                'enabled' => false,
                'seed_path' => storage_path('doppelganger/seeds'),
                'cleanup_after' => true,
            ],
            'include_soft_deleted' => false,
            'max_relationship_depth' => 5,
        ]);
    }

    protected function setUpDatabase(): void
    {
        // Create tables in source database
        $this->createTestTables('source');

        // Create tables in target database
        $this->createTestTables('target');
    }

    protected function createTestTables(string $connection): void
    {
        $schema = $this->app['db']->connection($connection)->getSchemaBuilder();

        // Create organizations table with various column types
        $schema->create('organizations', function ($table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config_settings')->nullable();  // renamed from settings
            $table->decimal('revenue', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_audit')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->integer('employee_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create organization_settings table
        $schema->create('organization_settings', function ($table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->onDelete('cascade');
            $table->string('default_language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->nullable();
            $table->string('time_format')->nullable();
            $table->string('currency')->nullable();
            $table->json('notification_settings')->nullable();
            $table->json('branding_settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create organization_locations table
        $schema->create('organization_locations', function ($table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Ensure only one primary location per organization
            $table->unique(['organization_id', 'is_primary']);
        });

        // Create resources table with relationships and constraints
        $schema->create('resources', function ($table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('configuration')->nullable();
            $table->boolean('is_public')->default(false);
            $table->dateTime('last_accessed')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('last_accessed');
        });

        // Create services table with additional relationships
        $schema->create('services', function ($table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('config')->nullable();
            $table->enum('status', ['running', 'stopped', 'failed'])->default('stopped');
            $table->dateTime('last_run')->nullable();
            $table->integer('error_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Add composite index
            $table->index(['resource_id', 'status']);
        });
    }
}
