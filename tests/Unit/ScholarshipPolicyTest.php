<?php

namespace Tests\Unit;

use App\Models\Scholarship;
use App\Models\User;
use App\Policies\ScholarshipPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScholarshipPolicyTest extends TestCase
{
    public static function permissionCases(): array
    {
        return ['active admin' => ['admin', true, true], 'active student' => ['student', true, false], 'inactive admin' => ['admin', false, false], 'inactive student' => ['student', false, false]];
    }

    #[DataProvider('permissionCases')]
    public function test_scholarship_mutation_permissions(string $role, bool $active, bool $permitted): void
    {
        $policy = new ScholarshipPolicy;
        $u = new User(['role' => $role, 'is_active' => $active]);
        $s = new Scholarship(['status' => 'open']);
        $this->assertSame($permitted, $policy->create($u));
        $this->assertSame($permitted, $policy->update($u, $s));
        $this->assertSame($permitted, $policy->delete($u, $s));
        $this->assertSame($active, $policy->view($u, $s));
    }
}
