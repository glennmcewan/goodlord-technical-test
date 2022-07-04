<?php

namespace App\Dto;

class PaymentTypes
{
    const WITHDRAWAL = 'atm';
    const BANK_CREDIT = 'bank credit';
    const BANK_DEBIT = 'bank transfer';
    const DIRECT_DEBIT = 'direct debit';
    const STANDING_ORDER = 'standing order';
    const CARD_PAYMENT = 'card payment';
}
