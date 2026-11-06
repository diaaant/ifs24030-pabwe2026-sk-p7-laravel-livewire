<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Repositories\StockRepository;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_bindings(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_stock_policy_is_registered(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_rate_limiter_is_1000_per_second(): void
    {
        $limiter = RateLimiter::limiter('1000-per-second');

        $guestLimit = $limiter(Request::create('/'));
        $this->assertSame(1000, $guestLimit->maxAttempts);
        $this->assertSame(1, $guestLimit->decaySeconds);
        $this->assertSame('127.0.0.1', $guestLimit->key);

        $user = $this->makeMember();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $this->assertSame($user->id, $limiter($request)->key);
    }

    public function test_force_https_enabled(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/stocks'));
    }

    public function test_force_https_disabled(): void
    {
        config(['app.force_https' => false]);
        URL::forceScheme('http');

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', URL::to('/stocks'));
    }
}
