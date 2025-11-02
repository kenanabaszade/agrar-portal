<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TranslationRule implements ValidationRule
{
    /**
     * Supported languages
     */
    protected array $supportedLanguages = ['az', 'en', 'ru'];

    /**
     * Whether at least default language (az) is required
     */
    protected bool $requireDefault = true;

    /**
     * Create a new rule instance.
     */
    public function __construct(bool $requireDefault = true)
    {
        $this->requireDefault = $requireDefault;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow nullable
        if ($value === null) {
            if ($this->requireDefault) {
                $fail('The :attribute field is required.');
            }
            return;
        }

        // If string (backward compatibility during migration)
        if (is_string($value)) {
            if (empty(trim($value)) && $this->requireDefault) {
                $fail('The :attribute field cannot be empty.');
            }
            return;
        }

        // Must be array
        if (!is_array($value)) {
            $fail('The :attribute must be an array with language keys (az, en, ru).');
            return;
        }

        // Validate language keys
        $hasValidLanguage = false;
        foreach ($this->supportedLanguages as $lang) {
            if (isset($value[$lang])) {
                if (!is_string($value[$lang])) {
                    $fail("The :attribute.{$lang} must be a string.");
                    return;
                }
                if (!empty(trim($value[$lang]))) {
                    $hasValidLanguage = true;
                }
            }
        }

        // Check for unsupported language keys
        foreach (array_keys($value) as $lang) {
            if (!in_array($lang, $this->supportedLanguages)) {
                $fail("The :attribute contains unsupported language key: {$lang}. Supported languages are: " . implode(', ', $this->supportedLanguages));
                return;
            }
        }

        // Require at least default language if required
        if ($this->requireDefault && (!$hasValidLanguage || (empty($value['az']) || empty(trim($value['az']))))) {
            $fail('The :attribute must have at least the default language (az) translation.');
        }
    }
}
