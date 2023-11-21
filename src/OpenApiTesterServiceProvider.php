<?php

namespace Goez\OpenAPI\Tester;

use Illuminate\Support\ServiceProvider;

class OpenApiTesterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../stub/docs' => base_path('docs'),
            __DIR__ . '/../stub/tests' => base_path('tests'),
        ], 'open-api-tester');
    }
}
