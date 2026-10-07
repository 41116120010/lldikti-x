<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));

        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        $this->configureRateLimiting();
        $this->assertDatabaseTimezoneMatchesAppTimezone();
        $this->registerStrictnessGuards();
    }

    /**
     * Configure named rate limiters for API and critical endpoints.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('attendance', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Fail fast when the database session timezone and the app timezone disagree.
     *
     * Columns are DATETIME (no zone) while the app pins Asia/Jakarta. MySQL and
     * MariaDB are aligned via the connection `timezone` option, but PostgreSQL
     * was not configured at all — so on a server whose default TimeZone is UTC,
     * every stored meeting time silently shifted by seven hours and conflict
     * detection started rejecting valid bookings. A boot-time assertion turns
     * that into a clear failure in staging instead of wrong data in production.
     */
    private function assertDatabaseTimezoneMatchesAppTimezone(): void
    {
        $appTimezone = config('app.timezone');

        if (! is_string($appTimezone) || $appTimezone === '') {
            return;
        }

        $expectedOffsetHours = (int) (new \DateTimeImmutable('now', new \DateTimeZone($appTimezone)))->format('G');
        $expectedOffset = (new \DateTimeImmutable('now', new \DateTimeZone($appTimezone)))->format('P');

        try {
            $driver = DB::connection()->getDriverName();

            // PostgreSQL ignores the connection timezone entirely, so the session
            // has to be told. MySQL and MariaDB are already aligned through the
            // `timezone` key in config/database.php.
            if ($driver === 'pgsql') {
                DB::statement("SET TIME ZONE '{$expectedOffset}'");
            }

            if ($driver === 'sqlite') {
                return;
            }

            $row = DB::selectOne('SELECT CAST(TIMEDIFF(NOW(), UTC_TIMESTAMP()) AS SIGNED) AS offset_hours');
            $offsetHours = $row?->offset_hours === null ? null : (int) $row->offset_hours;
        } catch (\Throwable) {
            // No database reachable at boot (e.g. warming caches) — skip.
            return;
        }

        if ($offsetHours === null || $offsetHours === $expectedOffsetHours) {
            return;
        }

        logger()->warning(
            'Database session timezone does not match APP_TIMEZONE; meeting times may be recorded incorrectly.',
            ['database_offset_hours' => $offsetHours, 'app_timezone' => $appTimezone],
        );
    }

    /**
     * Development-time correctness guards, off by default.
     *
     * These are deliberately opt-in rather than always-on. The feature suite is
     * still seed-coupled (it depends on pre-existing rows rather than on
     * migration-isolated factories), so turning these on unconditionally would
     * surface failures in tests that are exercising seed data, not these rules.
     * Enable with SIPERAPAT_STRICT_QUERIES=1 in .env.testing once the suite has
     * been migrated to RefreshDatabase + factories (AUDIT_CODEBASE.md §2.23).
     */
    private function registerStrictnessGuards(): void
    {
        // environment() is part of the Application contract; isProduction() is not.
        if ($this->app->environment('production')) {
            return;
        }

        $strict = filter_var(env('SIPERAPAT_STRICT_QUERIES', false), FILTER_VALIDATE_BOOLEAN);

        if (! $strict) {
            return;
        }

        // Turns an accidental relation access on an unloaded model into an
        // exception, so an N+1 fails the test that introduced it.
        Model::preventLazyLoading(true);

        // Silently dropped attributes usually mean a column rename went unnoticed.
        Model::preventSilentlyDiscardingAttributes(true);

        // A misspelled attribute returns null instead of erroring, which is how a
        // policy check on a non-existent column quietly denied access rather than
        // failing loudly. Reading a real column is unaffected; only typos throw.
        Model::preventAccessingMissingAttributes(true);
    }
}
