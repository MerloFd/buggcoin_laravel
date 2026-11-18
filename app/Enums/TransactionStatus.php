<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Posted = 'posted';
    case Pending = 'pending';
}
