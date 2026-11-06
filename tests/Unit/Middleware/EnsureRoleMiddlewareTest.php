<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    private function invoke(?object $user, string ...$roles): Response
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRole)->handle($request, fn () => new Response('ok'), ...$roles);
    }

    public function test_allows_matching_role(): void
    {
        $response = $this->invoke($this->makeAdmin(), 'admin');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_allows_any_of_multiple_roles(): void
    {
        $response = $this->invoke($this->makeMember(), 'admin', 'member');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rejects_wrong_role(): void
    {
        $this->expectException(HttpException::class);

        $this->invoke($this->makeMember(), 'admin');
    }

    public function test_rejects_guest(): void
    {
        try {
            $this->invoke(null, 'member');
            $this->fail('403 expected');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
