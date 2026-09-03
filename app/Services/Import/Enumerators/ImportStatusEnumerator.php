<?php

namespace App\Services\Import\Enumerators;

use App\Support\TranslationHelper;

enum ImportStatusEnumerator: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return TranslationHelper::get('translations.import.status.'.$this->value);
    }
}
