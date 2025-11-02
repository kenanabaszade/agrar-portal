<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Supported languages
     */
    protected static $supportedLanguages = ['az', 'en', 'ru'];

    /**
     * Default language
     */
    protected static $defaultLanguage = 'az';

    /**
     * Get translated attribute value
     * 
     * @param string $attribute
     * @param string|null $lang
     * @return string|null
     */
    public function getTranslated(string $attribute, ?string $lang = null): ?string
    {
        $lang = $lang ?? $this->getCurrentLanguage();
        
        // Get raw attribute value (bypass accessor to avoid recursion)
        $value = $this->attributes[$attribute] ?? null;
        
        if (is_null($value)) {
            return null;
        }

        // If value is JSON string, decode it
        if (is_string($value)) {
            // Try to decode JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                // It's a plain string (backward compatibility)
                return $value;
            }
        }

        // If JSON object/array
        if (is_array($value)) {
            // Try requested language
            if (isset($value[$lang]) && !empty($value[$lang])) {
                return $value[$lang];
            }

            // Fallback to default language
            if (isset($value[self::$defaultLanguage]) && !empty($value[self::$defaultLanguage])) {
                return $value[self::$defaultLanguage];
            }

            // Fallback to first available language
            if (!empty($value)) {
                foreach ($value as $langValue) {
                    if (!empty($langValue)) {
                        return $langValue;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get full translation object
     * 
     * @param string $attribute
     * @return array
     */
    public function getFullTranslation(string $attribute): array
    {
        $value = $this->getAttribute($attribute);

        if (is_null($value)) {
            return [];
        }

        // If already a string (backward compatibility)
        if (is_string($value)) {
            return [self::$defaultLanguage => $value];
        }

        // If JSON object
        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Set translation for an attribute
     * 
     * @param string $attribute
     * @param array|string $translations
     * @return void
     */
    public function setTranslation(string $attribute, $translations): void
    {
        // If string provided, convert to default language
        if (is_string($translations)) {
            $translations = [self::$defaultLanguage => $translations];
        }

        // Ensure it's an array
        if (!is_array($translations)) {
            return;
        }

        // Validate and clean translations
        $cleanTranslations = [];
        foreach (self::$supportedLanguages as $lang) {
            if (isset($translations[$lang]) && !empty(trim($translations[$lang]))) {
                $cleanTranslations[$lang] = trim($translations[$lang]);
            }
        }

        // At least default language must be provided
        if (!isset($cleanTranslations[self::$defaultLanguage]) && !empty($translations)) {
            // Use first provided language as default
            $firstLang = array_key_first($translations);
            if ($firstLang) {
                $cleanTranslations[self::$defaultLanguage] = trim($translations[$firstLang]);
            }
        }

        $this->setAttribute($attribute, $cleanTranslations);
    }

    /**
     * Get current language from request or default
     * 
     * @return string
     */
    protected function getCurrentLanguage(): string
    {
        // Try to get from request
        if (request()->has('lang')) {
            $lang = request()->get('lang');
            if (in_array($lang, self::$supportedLanguages)) {
                return $lang;
            }
        }

        // Try to get from app locale
        $locale = App::getLocale();
        if (in_array($locale, self::$supportedLanguages)) {
            return $locale;
        }

        // Return default
        return self::$defaultLanguage;
    }

    /**
     * Check if attribute has translation for language
     * 
     * @param string $attribute
     * @param string|null $lang
     * @return bool
     */
    public function hasTranslation(string $attribute, ?string $lang = null): bool
    {
        $lang = $lang ?? $this->getCurrentLanguage();
        $value = $this->getAttribute($attribute);

        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return !empty($value);
        }

        if (is_array($value)) {
            return isset($value[$lang]) && !empty($value[$lang]);
        }

        return false;
    }

    /**
     * Get all translations for an attribute
     * 
     * @param string $attribute
     * @return array
     */
    public function getAllTranslations(string $attribute): array
    {
        return $this->getFullTranslation($attribute);
    }

    /**
     * Get attribute with automatic translation
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // If this is a translatable attribute and value is array, translate it
        if ($this->isTranslatableAttribute($key) && is_array($value)) {
            $lang = $this->getCurrentLanguage();
            return $value[$lang] ?? $value['az'] ?? reset($value) ?? null;
        }

        return $value;
    }

    /**
     * Check if attribute is translatable
     * Override this method in model to specify translatable attributes
     * 
     * @param string $attribute
     * @return bool
     */
    protected function isTranslatableAttribute(string $attribute): bool
    {
        if (property_exists($this, 'translatable')) {
            return in_array($attribute, $this->translatable);
        }

        return false;
    }

    /**
     * Get supported languages
     * 
     * @return array
     */
    public static function getSupportedLanguages(): array
    {
        return self::$supportedLanguages;
    }

    /**
     * Get default language
     * 
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return self::$defaultLanguage;
    }
}

