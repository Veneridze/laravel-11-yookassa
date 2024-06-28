<?php

namespace idvLab\LaravelYookassa\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use idvLab\LaravelYookassa\Models\YookassaPayment;

final class YookassaPaymentNotification
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public YookassaPayment $payment
    )
    {
    }
}
