<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class TranslationSearchHelper
{
    /**
     * Supported languages for search
     */
    protected static array $supportedLanguages = ['az', 'en', 'ru'];

    /**
     * Add JSON field search to query
     * Searches in all supported languages for the given field
     * 
     * @param Builder $query
     * @param string $field Column name (e.g., 'title', 'description')
     * @param string $searchTerm Search term
     * @param array|null $languages Languages to search in (default: ['az', 'en', 'ru'])
     * @return Builder
     */
    public static function addJsonFieldSearch(Builder $query, string $field, string $searchTerm, ?array $languages = null): Builder
    {
        $languages = $languages ?? self::$supportedLanguages;
        $searchPattern = "%{$searchTerm}%";

        return $query->where(function ($q) use ($field, $searchPattern, $languages) {
            foreach ($languages as $lang) {
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.{$lang}')) LIKE ?", [$searchPattern]);
            }
        });
    }

    /**
     * Add multiple JSON field search to query
     * Searches in all supported languages for multiple fields
     * 
     * @param Builder $query
     * @param array $fields Array of column names (e.g., ['title', 'description'])
     * @param string $searchTerm Search term
     * @param array|null $languages Languages to search in (default: ['az', 'en', 'ru'])
     * @return Builder
     */
    public static function addMultipleJsonFieldSearch(Builder $query, array $fields, string $searchTerm, ?array $languages = null): Builder
    {
        $languages = $languages ?? self::$supportedLanguages;
        $searchPattern = "%{$searchTerm}%";

        return $query->where(function ($q) use ($fields, $searchPattern, $languages) {
            foreach ($fields as $field) {
                foreach ($languages as $lang) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.{$lang}')) LIKE ?", [$searchPattern]);
                }
            }
        });
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
     * Set supported languages
     * 
     * @param array $languages
     * @return void
     */
    public static function setSupportedLanguages(array $languages): void
    {
        self::$supportedLanguages = $languages;
    }
}


