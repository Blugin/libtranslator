<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\lib\translator;

use blugin\lib\translator\convert\LocaleConverter;
use pocketmine\command\CommandSender;
use pocketmine\lang\LanguageNotFoundException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Translator{
    /** @var PluginBase */
    protected $plugin;

    /** @var string locale name */
    protected $defaultLocale;

    /** @var Language[] */
    protected $lang = [];

    /** @param PluginBase $owningPlugin */
    public function __construct(PluginBase $owningPlugin){
        $this->plugin = $owningPlugin;

        $this->loadAllLocale();
        $this->defaultLocale = Server::getInstance()->getLanguage()->getLang();
    }

    /**
     * @param string      $str
     * @param mixed[]     $params
     * @param string|null $locale
     *
     * @return string
     */
    public function translate(string $str, array $params = [], ?string $locale = null) : string{
        $lang = $this->getLang($locale);
        if($lang !== null){
            $str = $lang->get($str);
        }
        foreach($params as $i => $param){
            $str = str_replace("{%$i}", (string) $param, $str);
        }
        return $str;
    }

    /**
     * @param string        $str
     * @param mixed[]       $params
     * @param CommandSender $sender
     *
     * @return string
     */
    public function translateTo(string $str, array $params, CommandSender $sender) : string{
        $locale = Server::getInstance()->getLanguage()->getLang();
        if(!Server::getInstance()->isLanguageForced() && $sender instanceof Player){
            $locale = LocaleConverter::fromPlayer($sender, $locale);
        }
        return $this->translate($str, $params, $locale);
    }

    /**
     * @param string|null $locale
     *
     * @return Language|null
     */
    public function getLang(?string $locale = null) : ?Language{
        $locale = strtolower($locale) ?? $this->getDefaultLocale();
        return $this->lang[$locale] ?? null;
    }

    /** @return Language[] */
    public function getLangList() : array{
        return $this->lang;
    }

    /** @return string */
    public function getDefaultLocale() : string{
        return $this->defaultLocale;
    }

    /** @return string[] */
    public function getLocaleList() : array{
        return array_keys($this->getLangList());
    }

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function setDefaultLocale(string $locale) : bool{
        $locale = strtolower($locale);
        if(!isset($this->lang[$locale]))
            return false;

        $this->defaultLocale = strtolower($locale);
        return true;
    }

    /**
     * Load all locale file from plugin data folder
     */
    public function loadAllLocale() : void{
        $path = $this->plugin->getDataFolder() . "locales/";
        if(!is_dir($path))
            throw new LanguageNotFoundException("Language directory $path does not exist or is not a directory");

        foreach(scandir($path, SCANDIR_SORT_NONE) as $_ => $filename){
            if(!preg_match('/^([a-zA-Z]){3}\.ini$/', $filename, $matches) || !isset($matches[1]))
                continue;

            $this->lang[$matches[1]] = Language::loadFrom($path . $filename, $matches[1]);
        }
    }

    /**
     * @return PluginBase
     */
    public function getPlugin() : PluginBase{
        return $this->plugin;
    }
}