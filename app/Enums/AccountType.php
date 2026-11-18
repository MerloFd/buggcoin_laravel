<?php

namespace App\Enums;

enum AccountType: string
{
    /** Conta corrente (Pluggy BANK / CHECKING_ACCOUNT). */
    case Checking = 'checking';

    /** Cartão de crédito (Pluggy CREDIT / CREDIT_CARD). */
    case CreditCard = 'credit_card';

    /** "Carteira" for manual entries (change manual-entries). */
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'conta',
            self::CreditCard => 'cartão',
            self::Manual => 'manual',
        };
    }
}
