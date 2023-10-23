<?php

namespace App\Providers;

use App\Faker\FakerImageGenerate;
use App\Http\Kernel;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Generator::class, function ($app) {
            $faker = Factory::create();
            $faker->addProvider(new FakerImageGenerate($faker));
            return $faker;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*Model::preventLazyLoading(!$this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());*/
        Model::shouldBeStrict(!$this->app->isProduction());

        if (app()->isProduction()) {
            DB::listen(function ($query) {
                if ($query->time > 100) {
                    logger()
                        ->channel('telegram')
                        ->debug('longer > 1s' . $query->sql, $query->bindings);
                }
            });
        }


        app(Kernel::class)->whenRequestLifecycleIsLongerThan(
            CarbonInterval::second(4),
            function () {
                logger()
                    ->channel('telegram')
                    ->debug('whenRequestLifecycleIsLongerThan' . request()->url());
            }
        );
    }
}
