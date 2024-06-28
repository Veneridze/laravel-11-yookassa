<?php

namespace idvLab\LaravelYookassa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use idvLab\LaravelYookassa\Enums\PaymentStatus;
use idvLab\LaravelYookassa\Events\YookassaPaymentNotification;
use idvLab\LaravelYookassa\Http\Requests\NotificationRequest;
use idvLab\LaravelYookassa\Services\PaymentService;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;

final class NotificationController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(NotificationRequest $request): JsonResponse
    {
        $requestBody = $request->all();
        if (!isset($requestBody['event'])) {
            throw new \Exception('event not found');
        }

        if (($requestBody['event'] === NotificationEventType::PAYMENT_SUCCEEDED)) {
            $notification = new NotificationSucceeded($requestBody);
        } elseif ($requestBody['event'] === NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE) {
            $notification = new NotificationWaitingForCapture($requestBody);
        } else {
            $notification = new NotificationCanceled($requestBody);
        }

        $payment = $notification->getObject();
        $status = PaymentStatus::tryFrom($payment->getStatus());
        $this->paymentService->setStatus($payment->getId(), $status);
        $yooKassaPayment = $this->paymentService->findByPaymentId($payment->getId());
        YookassaPaymentNotification::dispatch($yooKassaPayment);

        return response()->json();
    }
}
