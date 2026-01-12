<?php

namespace Spatie\LaravelTypeScriptTransformer\References;

class LaravelRouteActionReference extends LaravelRouteReference
{
    protected function getKind(): string
    {
        return 'laravel-route-action';
    }

    public static function actionController(): static
    {
        return new static('action_controller');
    }

    public static function actionParameters(): static
    {
        return new static('action_parameters');
    }
}
