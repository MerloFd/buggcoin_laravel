<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Pluggy = 'pluggy';
    case Manual = 'manual';
}
