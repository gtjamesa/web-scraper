<?php

namespace JamesAusten\WebScraper\Scraper;

use Illuminate\Support\ServiceProvider;

class WebScraperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->handleConfig();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        config([
            __DIR__ . '/../config/web-scraper.php',
        ]);
    }

    /**
     * Config
     */
    protected function handleConfig()
    {
        $packageConfig = __DIR__ . '/../config/web-scraper.php';
        $destinationConfig = config_path('web-scraper.php');

        $this->publishes([
            $packageConfig => $destinationConfig,
        ]);
    }
}
