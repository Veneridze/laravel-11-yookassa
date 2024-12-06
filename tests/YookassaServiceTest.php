<?php

namespace Tests\Feature;

use idvLab\LaravelYookassa\Repositories\PaymentRepository;
use idvLab\LaravelYookassa\Services\PaymentService;
use idvLab\LaravelYookassa\YooKassa;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

final class YookassaServiceTest extends TestCase
{
    //use RefreshDatabase;

    /**
     * Test Index LanguageLine functionality
     *
     * @return void
     */
    public function testCreatePayment(): void
    {
        $paymentService = new PaymentService(
            app(YooKassa::class),
            app(PaymentRepository::class),
        );

        $model = $paymentService->create( 20,500, '12340000', 'test test', 'RUB', true);
        $this->assertNotEmpty($model);
    }
}
