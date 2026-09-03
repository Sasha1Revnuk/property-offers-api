<?php

namespace App\Http\Resources\Api\V1;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

class EnumValueResource extends JsonResource
{
    /**
     * @return array{value: string|int, label: string}
     */
    public function toArray(Request $request): array
    {
        $enum = $this->resource;

        if (! $enum instanceof BackedEnum) {
            throw new InvalidArgumentException('EnumValueResource requires a backed enum.');
        }

        if (! method_exists($enum, 'getLabel')) {
            throw new InvalidArgumentException('EnumValueResource requires getLabel() on the enum.');
        }

        $label = $enum->getLabel();

        if (! is_string($label)) {
            throw new InvalidArgumentException('Enum getLabel() must return a string.');
        }

        return [
            'value' => $enum->value,
            'label' => $label,
        ];
    }
}
