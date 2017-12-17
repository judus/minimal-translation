<?php

namespace Maduser\Minimal\Translation;

/**
 * Class Translation
 *
 * @package Maduser\Minimal\Libraries
 */
class Translator
{
    /**
     * @var array
     */
    private static $availableLanguages = ['en', 'de', 'fr', 'it', 'rm'];

    /**
     * @var string
     */
    private static $defaultLanguage = 'en';

    /**
     * @var string
     */
    private static $preferedLanguages;

    /**
     * @var string
     */
    private static $language;

    /**
     * @var array
     */
    private static $translations = [];

    /**
     * @var string
     */
    private static $filePath = __DIR__.'/translations.json';

    /**
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        return self::$availableLanguages;
    }

    /**
     * @param array $availableLanguages
     */
    public static function setAvailableLanguages(array $availableLanguages)
    {
        self::$availableLanguages = $availableLanguages;
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return self::$defaultLanguage;
    }

    /**
     * @param string $defaultLanguage
     */
    public static function setDefaultLanguage(string $defaultLanguage)
    {
        self::$defaultLanguage = $defaultLanguage;
    }

    /**
     * @return array
     */
    public static function getPreferedLanguages()
    {
        if (count(self::$preferedLanguages) == 0) {
            self::setPreferedLanguages(
                strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])
            );
        }

        return self::$preferedLanguages;
    }

    /**
     * @param string $preferedLanguages
     */
    public static function setPreferedLanguages($preferedLanguages)
    {
        self::$preferedLanguages = $preferedLanguages;
    }

    /**
     * @return string
     */
    public static function getLanguage()
    {
        if (empty(self::$language)) {
            return self::getPreferedLanguage(
                self::getAvailableLanguages(),
                self::getPreferedLanguages()
            );
        }

        return self::$language;
    }

    /**
     * @param string $language
     */
    public static function setLanguage($language)
    {
        self::$language = $language;
    }

    /**
     * @return array
     */
    public static function getTranslations(): array
    {
        return self::$translations;
    }

    /**
     * @param array $translations
     */
    public static function setTranslations(array $translations)
    {
        self::$translations = $translations;
    }

    /**
     * @return string
     */
    public static function getFilePath(): string
    {
        return self::$filePath;
    }

    /**
     * @param string $filePath
     */
    public static function setFilePath(string $filePath)
    {
        self::$filePath = $filePath;
    }

     /**
     * @param      $args
     * @param null $lang
     *
     * @return mixed
     */
    public static function get($args, $lang = null)
    {
        $lang = !is_null($lang) ? $lang : self::getLanguage();
        $args = is_array($args) ? $args : [$args, null];

        $text = $args[0];
        //unset($args[0]);

        $hash = md5($text);

        if (count(self::$translations) == 0) {
            if (file_exists(self::getFilePath())) {
                $json = file_get_contents(self::getFilePath());
                self::$translations = json_decode($json, true);
            }
        }

        if (isset(self::$translations[$hash])
            && isset(self::$translations[$hash][$lang])
        ) {
            if (!empty(self::$translations[$hash][$lang])) {
                $args[0] = self::$translations[$hash][$lang];
                return call_user_func_array('sprintf', $args);
            }
        } else {
            self::$translations[$hash]['origin'] = $text;
            foreach (self::getAvailableLanguages() as $language) {
                if (!isset(self::$translations[$hash][$language])) {
                    self::$translations[$hash][$language] = '';
                }
            }
            $json = json_encode(
                self::$translations,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );
            file_put_contents(self::getFilePath(), $json);
            self::$translations = json_decode($json, true);
        }

        return call_user_func_array('sprintf', $args);
    }

    /**
     * @param $availableLanguages
     * @param $httpAcceptLanguage
     *
     * @return mixed
     */
    public static function getPreferedLanguage($availableLanguages, $httpAcceptLanguage)
    {
        $availableLanguages = array_flip($availableLanguages);

        $languages = array();
        preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~',
            strtolower($httpAcceptLanguage), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {

            list($a) = explode('-', $match[1]) + array('', '');
            $value = isset($match[2]) ? (float)$match[2] : 1.0;

            if (isset($availableLanguages[$match[1]])) {
                $languages[$match[1]] = $value;
                continue;
            }

            if (isset($availableLanguages[$a])) {
                $languages[$a] = $value - 0.1;
            }

        }
        if ($languages) {
            arsort($languages);

            return key($languages);
        } else {
            return self::getDefaultLanguage();
        }
    }

}