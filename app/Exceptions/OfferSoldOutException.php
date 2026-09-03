<?php

namespace App\Exceptions;

use App\Support\TranslationHelper;
use Exception;

class OfferSoldOutException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? TranslationHelper::get('translations.reservation.sold_out'),
        );
    }
}
