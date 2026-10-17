<?php

declare(strict_types=1);

namespace spawaskar\Tags;

use Illuminate\Support\ServiceProvider;
use spawaskar\Tags\Console\Commands\TagsCommand;

class TagsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tags.php', 'tags');

        $this->app->singleton(Tags::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/tags.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tags');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'tags');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/tags.php' => config_path('tags.php'),
        ], ['tags', 'tags-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tags'),
        ], ['tags', 'tags-views']);

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/tags'),
        ], ['tags', 'tags-vue']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/tags'),
        ], ['tags', 'tags-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/tags'),
        ], ['tags', 'tags-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['tags', 'tags-migrations']);

        $this->commands([
            TagsCommand::class,
        ]);
    }
}
