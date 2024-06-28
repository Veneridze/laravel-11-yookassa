<?php

namespace idvLab\LaravelYookassa\Contracts\Repositories;

use idvLab\LaravelYookassa\Models\YookassaPayment;

interface PaymentRepositoryInterface
{
    public function updateByPaymentId(string $paymentId, array $data): bool;

    public function findByPaymentId(string $paymentId): ?YookassaPayment;

}
