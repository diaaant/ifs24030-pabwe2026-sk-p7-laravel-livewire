<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    private function invoke(?User $user, Stock $stock, bool $asModel): Response
    {
        $request = Request::create('/stocks/'.$stock->id);
        $route = new Route('GET', '/stocks/{stock}', fn () => null);
        $route->bind($request);

        if ($asModel) {
            $route->setParameter('stock', $stock);
        }

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return (new EnsureStockOwnership)->handle($request, fn () => new Response('ok'));
    }

    public function test_owner_passes_with_bound_model(): void
    {
        $owner = $this->makeMember();
        $stock = $this->makeStock($owner);

        $this->assertSame('ok', $this->invoke($owner, $stock, true)->getContent());
    }

    public function test_owner_passes_with_raw_id(): void
    {
        $owner = $this->makeMember();
        $stock = $this->makeStock($owner);

        $this->assertSame('ok', $this->invoke($owner, $stock, false)->getContent());
    }

    public function test_non_owner_is_rejected(): void
    {
        $stock = $this->makeStock($this->makeMember());

        try {
            $this->invoke($this->makeMember(), $stock, true);
            $this->fail('403 expected');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_guest_is_rejected(): void
    {
        $stock = $this->makeStock($this->makeMember());

        $this->expectException(HttpException::class);

        $this->invoke(null, $stock, true);
    }
}
