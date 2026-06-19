<?php

namespace Spatie\LaravelTypeScriptTransformer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class InstallTypeScriptTransformerCommand extends Command
{
    public $signature = 'typescript:install';

    public $description = 'Installs TypeScript transformer within your Laravel application.';

    protected string $provider = 'App\Providers\TypeScriptTransformerServiceProvider';

    public function handle(): void
    {
        $this->comment('Publishing TypeScript Transformer Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'typescript-transformer-provider']);
        $this->info('TypeScript Transformer Service Provider installed.');

        $this->registerServiceProvider();
    }

    protected function registerServiceProvider(): void
    {
        $providersPath = base_path('bootstrap/providers.php');

        $contents = file_exists($providersPath) ? file_get_contents($providersPath) : '';

        if (Str::contains($contents, 'TypeScriptTransformerServiceProvider')) {
            $this->info('TypeScript Transformer Service Provider already registered.');

            return;
        }

        if (ServiceProvider::addProviderToBootstrapFile($this->provider, $providersPath)) {
            $this->info('TypeScript Transformer Service Provider registered.');

            return;
        }

        $this->error(
            "Could not automatically register the TypeScript Transformer Service Provider. "
            ."Please add `{$this->provider}::class` to your bootstrap/providers.php file manually."
        );
    }
}
