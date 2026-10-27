<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Memastikan store cache default terdaftar dan valid di config/cache.php.
     */
    public function test_default_cache_store_is_configured_properly(): void
    {
        $defaultStore = config('cache.default');
        $stores = config('cache.stores');

        $this->assertNotEmpty($defaultStore);
        $this->assertArrayHasKey($defaultStore, $stores, "Default cache store [{$defaultStore}] tidak terdaftar di config/cache.php");
    }

    /**
     * Memastikan CacheEventMutex (yang dipakai withoutOverlapping scheduler) dapat dibuat dan dieksekusi.
     */
    public function test_cache_event_mutex_can_be_resolved_and_used(): void
    {
        $mutex = new CacheEventMutex($this->app->make(CacheFactory::class));
        $this->assertInstanceOf(CacheEventMutex::class, $mutex);

        // Uji coba operasi lock bawaan driver cache aktif
        $lockKey = 'test-siperapat-schedule-mutex';
        $lock = Cache::lock($lockKey, 10);

        $acquired = $lock->get();
        $this->assertTrue($acquired, 'Gagal memperoleh atomic lock pada cache store');

        $released = $lock->release();
        $this->assertTrue($released, 'Gagal melepaskan atomic lock pada cache store');
    }
}
