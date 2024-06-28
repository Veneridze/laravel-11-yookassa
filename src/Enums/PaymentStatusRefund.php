<?php

namespace idvLab\LaravelYookassa\Enums;

enum PaymentStatusRefund: string
{
    case NOT_REFUNDED = 'not_refunded';
    case  REFUNDED = 'refunded';
    case  PARTIAL_REFUNDED = 'partial_refunded';
}
