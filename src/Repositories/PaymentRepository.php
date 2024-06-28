<?php

namespace idvLab\LaravelYookassa\Repositories;

use idvLab\LaravelYookassa\Enums\PaymentStatusRefund;
use Exception;
use idvLab\LaravelYookassa\Contracts\Repositories\PaymentRepositoryInterface;
use idvLab\LaravelYookassa\Models\YookassaPayment;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use YooKassa\Model\Refund\RefundInterface;

/**
 * @method YookassaPayment getModel()
 */
class PaymentRepository implements PaymentRepositoryInterface
{
    protected string $class = YookassaPayment::class;

    public function updateByPaymentId(string $paymentId, array $data): bool
    {
        return $this->getRepository()
            ->where(['payment_id' => $paymentId])
            ->update($data);
    }

    public function findByPaymentId(string $paymentId): ?YookassaPayment
    {
        return $this->getRepository()
            ->where(['payment_id' => $paymentId])
            ->firstOrFail();
    }

    /**
     * @return YookassaPayment
     * @throws BindingResolutionException
     */
    public function getRepository(): YookassaPayment
    {
        return app()->make($this->class);
    }

    /**
     * @throws Exception
     */
    public function create(array $data): ?Model
    {
        $model = $this->getRepository()->create($data);

        if ($model === null) {
            throw new Exception(trans('exceptions.not_created'));
        }

        return $model;
    }

    /**
     * @throws BindingResolutionException
     */
    public function refund(RefundInterface $refund): YookassaPayment
    {
        $yookassaPayment = $this->getRepository()
            ->where(['payment_id' => $refund->getPaymentId()])
            ->first();

        $yookassaPayment->refund_amount = $yookassaPayment->refund_amount + (float)$refund->getAmount()->getValue();

        if ($yookassaPayment->refund_amount > $yookassaPayment->amount) {
            throw new \Exception('The refund amount is greater than the payment amount');
        }

        if ($yookassaPayment->amount === $yookassaPayment->refund_amount) {
            $yookassaPayment->status = PaymentStatusRefund::REFUNDED->value;
        } else {
            $yookassaPayment->status = PaymentStatusRefund::PARTIAL_REFUNDED->value;
        }

        $yookassaPayment->save();
        return $yookassaPayment;
    }
}
