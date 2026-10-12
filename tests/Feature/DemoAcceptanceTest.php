<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reproducible_demo_contains_one_admin_ten_students_and_forty_labelled_scholarships(): void
    {
        $this->seed();
        $this->seed();
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(10, User::where('role', 'student')->count());
        $this->assertSame(40, Scholarship::count());
        $this->assertSame(40, Scholarship::where('is_fictional', true)->count());
        $this->assertDatabaseCount('saved_scholarships', 0);
        $this->actingAs(User::where('email', 'student1@scholarmatch.test')->firstOrFail());
        foreach (['/dashboard', '/recommendations', '/saved', '/profile'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_warmed_recommendations_meet_two_second_target_with_bounded_queries(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'student1@scholarmatch.test')->firstOrFail());
        $this->get('/recommendations')->assertOk();
        DB::enableQueryLog();
        $start = hrtime(true);
        $response = $this->get('/recommendations');
        $elapsed = (hrtime(true) - $start) / 1e6;
        $response->assertOk();
        $this->assertLessThan(2000, $elapsed);
        $this->assertLessThanOrEqual(12, count(DB::getQueryLog()));
        if (! is_dir(base_path('artifacts'))) {
            mkdir(base_path('artifacts'));
        }
        file_put_contents(base_path('artifacts/benchmark-'.config('database.default').'.json'), json_encode(['dataset_scholarships' => 40, 'response_ms' => $elapsed, 'queries' => count(DB::getQueryLog()), 'database' => config('database.default')], JSON_PRETTY_PRINT));
    }

    public function test_production_seeding_is_rejected_and_administrators_use_controlled_setup(): void
    {
        $this->app['env'] = 'production';
        try {
            (new DatabaseSeeder)->run();
            $this->fail('Production demo seeding must be rejected.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('must not be seeded', $e->getMessage());
        }
        $this->app['env'] = 'testing';
        $this->artisan('scholarmatch:admin', ['email' => 'owner@example.test', '--name' => 'Owner'])->expectsQuestion('Password (at least 12 characters)', 'Owner!1234567')->expectsOutput('Administrator provisioned.')->assertExitCode(0);
        $this->assertSame('admin', User::where('email', 'owner@example.test')->firstOrFail()->role);
        $this->artisan('scholarmatch:admin', ['email' => 'bad', '--name' => 'Owner'])->expectsQuestion('Password (at least 12 characters)', 'short')->assertExitCode(1);
    }
}
