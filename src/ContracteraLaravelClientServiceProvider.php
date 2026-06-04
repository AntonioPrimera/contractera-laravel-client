<?php

namespace AntonioPrimera\ContracteraLaravelClient;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ContracteraLaravelClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('contractera-laravel-client')
            ->hasConfigFile();
    }
}
