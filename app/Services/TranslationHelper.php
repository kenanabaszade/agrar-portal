<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class TranslationHelper
{
    /**
     * Supported languages
     */
    public const SUPPORTED_LANGUAGES = ['az', 'en', 'ru'];

    /**
     * Default language
     */
    public const DEFAULT_LANGUAGE = 'az';

    /**
     * Get current language from request or default
     * 
     * @return string
     */
    public static function getCurrentLanguage(): string
    {
        // Try to get from request
        if (request()->has('lang')) {
            $lang = request()->get('lang');
            if (self::isValidLanguage($lang)) {
                return $lang;
            }
        }

        // Try to get from Accept-Language header
        if (request()->hasHeader('Accept-Language')) {
            $headerLang = request()->header('Accept-Language');
            // Extract first language code (e.g., "en-US" -> "en")
            $lang = substr($headerLang, 0, 2);
            if (self::isValidLanguage($lang)) {
                return $lang;
            }
        }

        // Try to get from app locale
        $locale = App::getLocale();
        if (self::isValidLanguage($locale)) {
            return $locale;
        }

        // Return default
        return self::DEFAULT_LANGUAGE;
    }

    /**
     * Validate language code
     * 
     * @param string|null $lang
     * @return bool
     */
    public static function isValidLanguage(?string $lang): bool
    {
        return in_array($lang, self::SUPPORTED_LANGUAGES);
    }

    /**
     * Get translated value from translation array
     * 
     * @param array|string|null $translations
     * @param string|null $lang
     * @return string|null
     */
    public static function getTranslated($translations, ?string $lang = null): ?string
    {
        if (is_null($translations)) {
            return null;
        }

        $lang = $lang ?? self::getCurrentLanguage();

        // If already a string (backward compatibility)
        if (is_string($translations)) {
            return $translations;
        }

        // If JSON object/array
        if (is_array($translations)) {
            // Try requested language
            if (isset($translations[$lang]) && !empty($translations[$lang])) {
                return $translations[$lang];
            }

            // Fallback to default language
            if (isset($translations[self::DEFAULT_LANGUAGE]) && !empty($translations[self::DEFAULT_LANGUAGE])) {
                return $translations[self::DEFAULT_LANGUAGE];
            }

            // Fallback to first available language
            if (!empty($translations)) {
                foreach ($translations as $value) {
                    if (!empty($value)) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate translation format
     * 
     * @param mixed $value
     * @return bool
     */
    public static function isValidTranslationFormat($value): bool
    {
        // String is valid (backward compatibility)
        if (is_string($value)) {
            return !empty(trim($value));
        }

        // Array/JSON object
        if (is_array($value)) {
            // Must have at least one valid language
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                if (isset($value[$lang]) && !empty(trim($value[$lang]))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Normalize translation input
     * Converts string or array to proper translation format
     * 
     * @param mixed $value
     * @return array
     */
    public static function normalizeTranslation($value): array
    {
        // If string, convert to default language
        if (is_string($value)) {
            $trimmed = trim($value);
            if (!empty($trimmed)) {
                return [self::DEFAULT_LANGUAGE => $trimmed];
            }
            return [];
        }

        // If array, clean and validate
        if (is_array($value)) {
            $normalized = [];
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                if (isset($value[$lang]) && is_string($value[$lang])) {
                    $trimmed = trim($value[$lang]);
                    if (!empty($trimmed)) {
                        $normalized[$lang] = $trimmed;
                    }
                }
            }
            return $normalized;
        }

        return [];
    }

    /**
     * Ensure translation has at least default language
     * 
     * @param array $translations
     * @return array
     */
    public static function ensureDefaultLanguage(array $translations): array
    {
        // If default language exists, return as is
        if (isset($translations[self::DEFAULT_LANGUAGE]) && !empty($translations[self::DEFAULT_LANGUAGE])) {
            return $translations;
        }

        // If no translations, return empty
        if (empty($translations)) {
            return [];
        }

        // Use first available language as default
        $firstLang = array_key_first($translations);
        if ($firstLang && isset($translations[$firstLang])) {
            $translations[self::DEFAULT_LANGUAGE] = $translations[$firstLang];
        }

        return $translations;
    }

    /**
     * Transform model response to include translated fields
     * 
     * @param mixed $model
     * @param array $translatableAttributes
     * @param string|null $lang
     * @param bool $includeFullTranslations
     * @return array
     */
    public static function transformModelResponse($model, array $translatableAttributes, ?string $lang = null, bool $includeFullTranslations = false): array
    {
        $data = $model->toArray();
        $lang = $lang ?? self::getCurrentLanguage();

        foreach ($translatableAttributes as $attribute) {
            if (isset($data[$attribute])) {
                if ($includeFullTranslations) {
                    // Include full translation object
                    $translations = $model->getFullTranslation($attribute);
                    $data[$attribute] = $translations;
                    // Also add translated value for convenience
                    $data[$attribute . '_translated'] = self::getTranslated($translations, $lang);
                } else {
                    // Only include translated value for requested language
                    $data[$attribute] = self::getTranslated($data[$attribute], $lang);
                }
            }
        }

        return $data;
    }

    /**
     * Transform collection response
     * 
     * @param \Illuminate\Support\Collection $collection
     * @param array $translatableAttributes
     * @param string|null $lang
     * @param bool $includeFullTranslations
     * @return \Illuminate\Support\Collection
     */
    public static function transformCollectionResponse($collection, array $translatableAttributes, ?string $lang = null, bool $includeFullTranslations = false)
    {
        return $collection->map(function ($item) use ($translatableAttributes, $lang, $includeFullTranslations) {
            return self::transformModelResponse($item, $translatableAttributes, $lang, $includeFullTranslations);
        });
    }
}

