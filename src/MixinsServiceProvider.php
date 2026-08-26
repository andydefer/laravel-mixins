<?php

declare(strict_types=1);

namespace AndyDefer\Mixins;

use Illuminate\Support\ServiceProvider;

final class MixinsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings needed - traits are used directly
    }

    public function boot(): void {}
}
