<?php

namespace idvLab\LaravelYookassa\Facades;

use idvLab\LaravelYookassa\YooKassa;
use Illuminate\Support\Facades\Facade;

class YooKassaFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return YooKassa::class;
    }
}
