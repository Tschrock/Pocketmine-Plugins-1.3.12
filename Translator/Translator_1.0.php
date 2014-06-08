<?php

/*
  __PocketMine Plugin__
  name=Translator
  description=Translate stuff in-game!
  version=1.0
  author=tschrock
  class=Translator
  apiversion=12
 */

class Translator implements Plugin {

    private $api, $config;
    

    public function __construct(ServerAPI $api, $server = false) {
        $this->api = $api;
        TranslateAPI::set($this);
    }

    public function init() {
        
        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "baseUrl" => "http://translate.google.com/translate_a/t?client=p&ie=UTF-8&oe=UTF-8",
            "defaultTo" => "en",
            "defaultFrom" => "auto",
            "UserAgent" => "Mozilla/4.0"
        ));

        $this->api->console->register("translate", "/translate (lang) to <lang> <Text to translate>", array($this, "commandHandler"));
    }

    public function eventHandler($data, $event) {
        
    }

    public function commandHandler($cmd, $params, $issuer) {
        if ($cmd == "translate") {
            if ($params[0] == "to") {
                $fromlang = $this->config->get("defaultFrom");
                $tolang = $params[1];
                $offset = 2;
            } elseif ($params[1] == "to") {
                $fromlang = $params[0];
                $tolang = $params[2];
                $offset = 3;
            } else {
                $fromlang = $this->config->get("defaultFrom");
                $tolang = $this->config->get("defaultTo");
                $offset = 0;
            }
            return $this->translate(implode(" ", array_slice($params, $offset)), $tolang, $fromlang);
        }
    }

    public function translate($text, $langTo, $langFrom) {
        $langTo = TranslateAPI::parseLang($langTo);
        $langFrom = TranslateAPI::parseLang($langFrom);

        $translateurl = $this->config->get("baseUrl") . "&text=" . urlencode($text) . "&hl=$langTo&sl=$langFrom";

        $curlReq = curl_init($translateurl);
        curl_setopt($curlReq, CURLOPT_USERAGENT, $this->config->get("UserAgent"));
        curl_setopt($curlReq, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlReq, CURLOPT_FAILONERROR, true);
        $result = curl_exec($curlReq);
        curl_close($curlReq);

        if ($result === false || !is_string($result)) {
            return "Could not translate text";
        } else {
            $Jdecode = json_decode($result, true);

            if (!is_null($Jdecode) && isset($Jdecode["sentences"]) && isset($Jdecode["sentences"][0]) && isset($Jdecode["sentences"][0]["trans"])) {
                return $Jdecode["sentences"][0]["trans"];
            } else {
                return "Could not translate text";
            }
        }
    }

    public function __destruct() {
        
    }

}

class TranslateAPI {

    private static $object;

    public static function set(Translator $plugin) {
        if (TranslateAPI::$object instanceof Translator) {
            return false;
        }
        TranslateAPI::$object = $plugin;
    }

    public static function get() {
        return TranslateAPI::$object;
    }

    public static function translate($text, $langTo, $langFrom) {
        return TranslateAPI::$object->translate($text, $langTo, $langFrom);
    }

    public static function parseLang($language) {
        $lang = array_search(strtolower($language), TranslateAPI::$langCodes);
        if (is_null($lang) || $lang === false) {
            $lang = $language;
        }
        return $lang;
    }

    private static $langCodes = array(
        "af" => "afrikaans",
        "ak" => "akan",
        "sq" => "albanian",
        "am" => "amharic",
        "ar" => "arabic",
        "hy" => "armenian",
        "az" => "azerbaijani",
        "eu" => "basque",
        "be" => "belarusian",
        "bem" => "bemba",
        "bn" => "bengali",
        "bh" => "bihari",
        "xx-bork" => "bork",
        "bs" => "bosnian",
        "br" => "breton",
        "bg" => "bulgarian",
        "km" => "cambodian",
        "ca" => "catalan",
        "chr" => "cherokee",
        "ny" => "chichewa",
        "zh-cn" => "chinese_simplified",
        "zh-tW" => "chinese_traditional",
        "co" => "corsican",
        "hr" => "croatian",
        "cs" => "czech",
        "da" => "danish",
        "nl" => "dutch",
        "xx-elmer" => "elmer_fudd",
        "en" => "english",
        "eo" => "esperanto",
        "et" => "estonian",
        "ee" => "ewe",
        "fo" => "faroese",
        "tl" => "filipino",
        "fi" => "finnish",
        "fr" => "french",
        "fy" => "frisian",
        "gaa" => "ga",
        "gl" => "galician",
        "ka" => "georgian",
        "de" => "german",
        "el" => "greek",
        "gn" => "guarani",
        "gu" => "gujarati",
        "xx-hacker" => "hacker",
        "ht" => "haitian_creole",
        "ha" => "hausa",
        "haw" => "hawaiian",
        "iw" => "hebrew",
        "hi" => "hindi",
        "hu" => "hungarian",
        "is" => "icelandic",
        "ig" => "igbo",
        "id" => "indonesian",
        "ia" => "interlingua",
        "ga" => "irish",
        "it" => "italian",
        "ja" => "japanese",
        "jw" => "javanese",
        "kn" => "kannada",
        "kk" => "kazakh",
        "rw" => "kinyarwanda",
        "rn" => "kirundi",
        "xx-klingon" => "klingon",
        "kg" => "kongo",
        "ko" => "korean",
        "kri" => "krio",
        "ku" => "kurdish",
        "ckb" => "kurdish_soranî",
        "ky" => "kyrgyz",
        "lo" => "laothian",
        "la" => "latin",
        "lv" => "latvian",
        "ln" => "lingala",
        "lt" => "lithuanian",
        "loz" => "lozi",
        "lg" => "luganda",
        "ach" => "luo",
        "mk" => "macedonian",
        "mg" => "malagasy",
        "ms" => "malay",
        "ml" => "malayalam",
        "mt" => "maltese",
        "mi" => "maori",
        "mr" => "marathi",
        "mfe" => "mauritian_creole",
        "mo" => "moldavian",
        "mn" => "mongolian",
        "sr-me" => "montenegrin",
        "ne" => "nepali",
        "pcm" => "nigerian_pidgin",
        "nso" => "northern_sotho",
        "no" => "norwegian",
        "nn" => "norwegian_nynorsk",
        "oc" => "occitan",
        "or" => "oriya",
        "om" => "oromo",
        "ps" => "pashto",
        "fa" => "persian",
        "xx-pirate" => "pirate",
        "pl" => "polish",
        "pt-br" => "portuguese_brazil",
        "pt-pt" => "portuguese",
        "pa" => "punjabi",
        "qu" => "quechua",
        "ro" => "romanian",
        "rm" => "romansh",
        "nyn" => "runyakitara",
        "ru" => "russian",
        "gd" => "scots_gaelic",
        "sr" => "serbian",
        "sh" => "serbo-croatian",
        "st" => "sesotho",
        "tn" => "setswana",
        "crs" => "seychellois_creole",
        "sn" => "shona",
        "sd" => "sindhi",
        "si" => "sinhalese",
        "sk" => "slovak",
        "sl" => "slovenian",
        "so" => "somali",
        "es" => "spanish",
        "es-419" => "spanish_latin_american",
        "su" => "sundanese",
        "sw" => "swahili",
        "sv" => "swedish",
        "tg" => "tajik",
        "ta" => "tamil",
        "tt" => "tatar",
        "te" => "telugu",
        "th" => "thai",
        "ti" => "tigrinya",
        "to" => "tonga",
        "lua" => "tshiluba",
        "tum" => "tumbuka",
        "tr" => "turkish",
        "tk" => "turkmen",
        "tw" => "twi",
        "ug" => "uighur",
        "uk" => "ukrainian",
        "ur" => "urdu",
        "uz" => "uzbek",
        "vi" => "vietnamese",
        "cy" => "welsh",
        "wo" => "wolof",
        "xh" => "xhosa",
        "yi" => "yiddish",
        "yo" => "yoruba",
        "zu" => "zulu"
    );
    
}
