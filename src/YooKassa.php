<?php

declare(strict_types=1);

namespace Digkill\YooKassaLaravel;

use Exception;
use Illuminate\Support\Str;
use YooKassa\Client;
use Digkill\YooKassaLaravel\Enums\Currency;
use Digkill\YooKassaLaravel\Enums\PaymentStatus;
use Digkill\YooKassaLaravel\Payment\CodesPayment;
use Digkill\YooKassaLaravel\Payment\CreatePayment;
use Digkill\YooKassaLaravel\Payment\WebhookPayment;
use YooKassa\Common\Exceptions\ApiConnectionException;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Common\Exceptions\AuthorizeException;
use YooKassa\Common\Exceptions\BadApiRequestException;
use YooKassa\Common\Exceptions\ExtensionNotFoundException;
use YooKassa\Common\Exceptions\ForbiddenException;
use YooKassa\Common\Exceptions\InternalServerError;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Common\Exceptions\ResponseProcessingException;
use YooKassa\Common\Exceptions\TooManyRequestsException;
use YooKassa\Common\Exceptions\UnauthorizedException;
use YooKassa\Request\Refunds\CreateRefundResponse;

class YooKassa
{
    public const YOOKASSA_NAME_CONFIG = 'yookassa';

    /**
     * Configuration YooKassa
     *
     * @var array
     */
    private array $config;

    /**
     * YooKassa Client
     *
     * @var Client
     */
    private Client $client;

    public function __construct(array $config = [])
    {
        $configYooKassa = config(self::YOOKASSA_NAME_CONFIG) ?? [];
        // Configuration
        $this->config = [...$config, ...$configYooKassa];

        // Create Client
        $this->client = new Client();

        // Create Authorization
        $this->client->setAuth($this->config['shop_id'], $this->config['secret_key']);
    }

    /**
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws ApiException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws InternalServerError
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws Exception
     */
    public function createPayment(float    $amount,
                                  string   $description,
                                  string   $orderId = null,
                                  int      $userId = null,
                                  string   $currency = null,
                                  bool     $capture = true,
                                  array    $additionalMetadata = [],
                                  callable $callback = null): DTO\CreatePaymentResponseDTO
    {
        if ($orderId === null) {
            // Generate orderId
            $orderId = Str::uuid()->toString();
        }

        if ($currency === null) {
            $currency = Currency::RUB->value;
        }

        // Redirect URI
        if (empty($this->config['redirect_uri'])) {
            throw new Exception('Not redirect uri');
        }

        $redirectUrl = "{$this->config['redirect_uri']}?order_id=$orderId";
        $metadata = array_merge(['order_id' => $orderId,], $additionalMetadata);

        $response = $this->client->createPayment([
            'amount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $redirectUrl,
            ],
            'metadata' => $metadata,
            'capture' => $capture,
            'description' => $description,
        ], $orderId);

        if ($callback !== null) {
            $callback($response);
        }

        // Create Request
        return (new CreatePayment($response, $orderId, $userId))->get();
    }

    /**
     * @throws ResponseProcessingException
     * @throws BadApiRequestException
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws NotFoundException
     * @throws ApiException
     * @throws ExtensionNotFoundException
     * @throws InternalServerError
     */
    public function checkPayment(string $paymentId, $orderId, $amount, $currency, callable $success, callable $failed = null)
    {
        // Get Payment Info
        $payment = $this->client->getPaymentInfo($paymentId);

        // Validation Payment Life
        if ($payment->getStatus() === PaymentStatus::WAITING_FOR_CAPTURE->value) {
            $response = $this->client->capturePayment([
                'amount' => [
                    'value' => $amount,
                    'currency' => $currency,
                ],
            ], $paymentId, $orderId);

            if ($response->getStatus() === PaymentStatus::SUCCEEDED->value) {
                return $success($response);
            } else {
                if ($failed) {
                    return $failed($payment);
                }

                return [
                    'error' => 'Canceled Invoice',
                    'code' => CodesPayment::CANCELED_INVOICE
                ];
            }
        } elseif ($payment->getStatus() == 'succeeded') {
            return $success($payment);
        } else {
            if ($failed) {
                return $failed($payment);
            }

            return [
                'error' => 'Canceled Invoice',
                'code' => CodesPayment::CANCELED_INVOICE
            ];
        }
    }

    /**
     * @throws NotFoundException
     * @throws ApiException
     * @throws ResponseProcessingException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws AuthorizeException
     * @throws InternalServerError
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws ApiConnectionException
     * @throws UnauthorizedException
     */
    public function refund($orderId, $paymentId, $amount, $currency = 'RUB'): CreateRefundResponse
    {
        return $this->client->createRefund(
            array(
                'amount' => [
                    'value' => $amount,
                    'currency' => $currency,
                ],
                'payment_id' => $paymentId,
            ),
            $orderId
        );
    }

    public function webhook(): WebhookPayment
    {
        return new WebhookPayment($this);
    }
}
