<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Policies\StockPolicy;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    public function test_policy_rules(): void
    {
        $policy = new StockPolicy;
        $owner = $this->makeMember();
        $other = $this->makeMember();
        $admin = $this->makeAdmin();
        $stock = $this->makeStock($owner);

        $this->assertTrue($policy->viewAny($owner));
        $this->assertFalse($policy->viewAny($admin));

        $this->assertTrue($policy->view($owner, $stock));
        $this->assertFalse($policy->view($other, $stock));
        $this->assertTrue($policy->view($admin, $stock));

        $this->assertTrue($policy->create($owner));
        $this->assertFalse($policy->create($admin));

        foreach (['update', 'delete', 'adjust'] as $ability) {
            $this->assertTrue($policy->{$ability}($owner, $stock), "owner {$ability}");
            $this->assertFalse($policy->{$ability}($other, $stock), "other {$ability}");
            $this->assertFalse($policy->{$ability}($admin, $stock), "admin {$ability}");
        }
    }
}
