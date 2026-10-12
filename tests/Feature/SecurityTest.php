<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_error_reports_exclude_household_income_and_raw_queries(): void
    {
        $handler = new TestHandler;
        Log::swap(new \Illuminate\Log\Logger(new Logger('test', [$handler])));
        $user = User::factory()->create();
        $user->profile()->create(['household_income' => 1234.56]);
        try {
            $user->profile()->create(['household_income' => 9876.54]);
            $this->fail('The unique profile constraint must fail.');
        } catch (QueryException $exception) {
            app(ExceptionHandler::class)->report($exception);
        }
        $records = $handler->getRecords();
        $this->assertCount(1, $records);
        $this->assertSame('Database operation failed.', $records[0]->message);
        $serialized = json_encode($records[0]->toArray());
        $this->assertStringNotContainsString('9876.54', $serialized);
        $this->assertStringNotContainsString('insert into', $serialized);
    }

    public function test_mutations_require_a_csrf_token_outside_the_test_bypass(): void
    {
        $u = User::factory()->create();
        $s = Scholarship::factory()->create();
        $this->app['env'] = 'local';
        $this->actingAs($u)->post('/saved/'.$s->id)->assertStatus(419);
        $this->assertDatabaseCount('saved_scholarships', 0);
        $this->withSession(['_token' => 'valid-test-token'])->post('/saved/'.$s->id, ['_token' => 'valid-test-token'])->assertRedirect();
        $this->assertDatabaseCount('saved_scholarships', 1);
    }

    public function test_inactive_sessions_are_blocked_before_any_mutation_and_guest_routes_redirect(): void
    {
        $s = Scholarship::factory()->create();
        $u = User::factory()->create(['is_active' => false]);
        $this->actingAs($u)->post('/saved/'.$s->id)->assertRedirect('/login');
        $this->assertDatabaseCount('saved_scholarships', 0);
        foreach (['/recommendations', '/saved', '/profile', '/admin/users', '/scholarships/'.$s->id] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }
}
