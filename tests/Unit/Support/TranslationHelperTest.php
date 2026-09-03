<?php

namespace Tests\Unit\Support;

use App\Support\TranslationHelper;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslationHelperTest extends TestCase
{
    #[Test]
    public function get_returns_translated_string(): void
    {
        $this->assertSame(
            __('auth.failed'),
            TranslationHelper::get('auth.failed'),
        );
    }

    #[Test]
    public function get_returns_key_when_translation_is_missing(): void
    {
        $this->assertSame(
            'translations.missing.key',
            TranslationHelper::get('translations.missing.key'),
        );
    }

    #[Test]
    public function get_array_returns_string_entries_for_locale_group(): void
    {
        Lang::addLines([
            'translations.example.label' => 'Label',
            'translations.example.hint' => 'Hint',
        ], app()->getLocale());

        $this->assertSame(
            [
                'label' => 'Label',
                'hint' => 'Hint',
            ],
            TranslationHelper::getArray('translations.example'),
        );
    }

    #[Test]
    public function localized_arrays_returns_current_locale_bucket_even_when_empty(): void
    {
        $this->assertSame(
            [app()->getLocale() => []],
            TranslationHelper::localizedArrays('translations.does_not_exist'),
        );
    }
}
