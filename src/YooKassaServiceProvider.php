<?php
namespace idvLab\LaravelYookassa;
use idvLab\LaravelYookassa\Contracts\Repositories\PaymentRepositoryInterface;
use idvLab\LaravelYookassa\Repositories\PaymentRepository;
use idvLab\LaravelYookassa\Services\PaymentService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use YooKassa\Client;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class YooKassaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-11-yookassa')
            ->hasRoute('yookassa')
            ->hasTranslations()
            //->hasConfigFile()
            ->hasMigrations([
                'create_yookassa_payments.php',
            ])
            ->publishesServiceProvider('YooKassaServiceProvider')
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    public function packageBooted(): void
    {
        
    }/**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['yookassa'];
    }

    public function packageRegistered(): void
    {
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);

        $this->app->bind(Client::class, function () {
            $client = new Client();
            $client->setAuth(config('yookassa.shop_id'), config('yookassa.secret_key'));
            return $client;
        });

        $this->app->singleton('Yookassa', function () {
            return new Yookassa(App::make(PaymentService::class));
        });

        $this->app->bind(YooKassa::class);

        $this->app->bind(PaymentService::class, function () {
            return new PaymentService(
                App::make(YooKassa::class),
                App::make(PaymentRepositoryInterface::class),
            );
        });
    }
}
