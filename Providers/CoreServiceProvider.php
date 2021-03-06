<?php

namespace Modules\Core\Providers;

use Modules\Core\Entities\Locale;
use Modules\Core\Entities\Channel;
use Modules\Core\Entities\Currency;
use Illuminate\Foundation\AliasLoader;
use Modules\Core\Entities\ActivityLog;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Entities\ExchangeRate;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Entities\Configuration;
use Modules\Core\Entities\Store;
use Modules\Core\Entities\Website;
use Modules\Core\Observers\LocaleObserver;
use Modules\Core\Observers\ChannelObserver;
use Modules\Core\Observers\CurrencyObserver;
use Modules\Core\Services\ActivityLogHelper;
use Modules\Core\Observers\ExchangeRateObserver;
use Modules\Core\Services\ConfigurationHelper;
use Modules\Core\Services\CoreCacheHelper;
use Modules\Core\Services\PriceFormatter;
use Modules\Core\Services\ResolverHelper;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Core';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'core';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerActivityLogger();
        $this->registerObserver();

        include __DIR__ . '/../Helpers/helpers.php';
        Validator::extend('decimal', 'Modules\Core\Contracts\Validations\Decimal@passes');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->publishes([
            __DIR__.'/../' =>  base_path('Modules/Core/'),
        ], "Module_Core");
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }

    /**
     * Register activity logger.
     *
     * @return void
     */
    public function registerActivityLogger()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Audit', ActivityLoggerFacade::class);

        $this->app->singleton('audit', function () {
            return new ActivityLogHelper(new ActivityLog());
        });

        $this->app->singleton('siteConfig', function () {
            return new ConfigurationHelper(new Configuration());
        });

        $this->app->singleton('resolver', function () {
            return new ResolverHelper(new Website());
        });

        $this->app->singleton('coreCache', function () {
            return new CoreCacheHelper(new Website(), new Channel(), new Store());
        });

        $this->app->singleton('priceFormat', function () {
            return new PriceFormatter();
        });
    }

    /**
     * Register observers.
     *
     * @return void
     */
    private function registerObserver()
    {
        Channel::observe(ChannelObserver::class);
        Locale::observe(LocaleObserver::class);
        Currency::observe(CurrencyObserver::class);
        ExchangeRate::observe(ExchangeRateObserver::class);
    }
}
