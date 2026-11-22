<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

abstract class ApiDocTag
{
    public const string PRODUCTS = '01 Products';
    public const string ORDERS   = '02 Orders';
    public const string GENERAL  = '99 General';
}
