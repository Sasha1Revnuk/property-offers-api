<?php

namespace App\Services\Reservation\Enumerators;

use App\Support\TranslationHelper;

enum ReservationStatusEnumerator: string
{
    case Confirmed = 'confirmed';

    public function getLabel(): string
    {
        return TranslationHelper::get('translations.reservation.status.'.$this->value);
    }
}
