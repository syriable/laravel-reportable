<?php

declare(strict_types=1);

namespace Syriable\Reportable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Syriable\Reportable\ReportableServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Carbon::setTestNow();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ReportableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        $this->migrateReportableTables();
    }

    protected function migrateReportableTables(): void
    {
        foreach (['create_reports_table', 'create_report_actions_table'] as $migration) {
            (include __DIR__."/../database/migrations/{$migration}.php.stub")->up();
        }
    }

    protected function rollbackReportableTables(): void
    {
        foreach (['create_report_actions_table', 'create_reports_table'] as $migration) {
            (include __DIR__."/../database/migrations/{$migration}.php.stub")->down();
        }
    }

    protected function freshReportableTables(): void
    {
        $this->rollbackReportableTables();
        $this->migrateReportableTables();
    }
}
