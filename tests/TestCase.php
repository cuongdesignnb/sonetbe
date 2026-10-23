<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'cache.stores.array' => ['driver' => 'array', 'serialize' => false],
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'auth.defaults.guard' => 'web',
            'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
            'auth.providers.users' => ['driver' => 'eloquent', 'model' => User::class],
            'session.driver' => 'array',
            'session.lifetime' => 120,
            'logging.default' => 'null',
            'logging.channels.null' => [
                'driver' => 'monolog',
                'handler' => \Monolog\Handler\NullHandler::class,
                'with' => [],
            ],
        ]);

        DB::purge('sqlite');
        $this->createVideoSchema();
    }

    protected function createVideoSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('student');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('title')->default('Lesson');
            $table->string('video_bunny_id')->nullable();
            $table->string('video_bunny_library_id')->nullable();
            $table->string('embed_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_local_path')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
