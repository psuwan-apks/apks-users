<?php

namespace apks\utils\languages;

class apksTRANSLATOR
{
    private $translations;
    private $defaultTranslations;

    public function __construct($lang = 'en')
    {
        $this->loadLanguage($lang);

        // Load default language as fallback
        $this->defaultTranslations = include APPLICATION_PATH . DS . 'app' . DS . 'lang' . DS . 'en.php';
    }

    // Method load selected language file
    public function loadLanguage($lang)
    {
        $langFile = APPLICATION_PATH . 'app' . DS . 'lang' . DS . $lang . '.php';
        if (file_exists($langFile)) {
            $this->translations = include $langFile;
        } else {
            $this->translations = []; // Empty array if language file doesn't exist
        }
    }

    // Method to translate a key
    public function translate($key)
    {
        return $this->translations[$key] ?? $this->defaultTranslations[$key] ?? $key;
    }
}