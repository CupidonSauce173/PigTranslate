<?php

namespace CupidonSauce173\PigTranslate;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

use pocketmine\utils\Config;

use function array_search;
use function file_exists;

class EventsListener implements Listener {
    /**
     * @param PlayerChatEvent $event
     */
    function onChat(PlayerChatEvent $event) {
        # Foreach all active languages in the server.
        foreach (PigTranslate::getInstance()->container[0]['activeLanguages'] as $language) {
            PigTranslate::Translate($event->getMessage(), $language, PigTranslate::MESSAGE_BROADCAST, null, $event->getPlayer());
        }
        $event->cancel();
    }

    /**
     * @param PlayerJoinEvent $event
     */
    function onJoin(PlayerJoinEvent $event) {
        $name = $event->getPlayer()->getName();
        if (!file_exists(PigTranslate::getInstance()->userDataFolder . strtolower($name) . '.yml')) {
            $pData = new Config(PigTranslate::getInstance()->userDataFolder . strtolower($name) . '.yml', Config::YAML,
                [
                    'language' => PigTranslate::getInstance()->container[1]['defaultLanguage']
                ]);
            $language = $pData->get('language');
            $pData->save();
        } else {
            $pData = new Config(PigTranslate::getInstance()->userDataFolder . strtolower($name) . '.yml', Config::YAML);
            $language = $pData->get('language');
        }
        PigTranslate::getInstance()->container[0]['players'][$name] = [];
        PigTranslate::getInstance()->container[0]['players'][$name]['lang'] = $language;
        if (array_search($language, (array)PigTranslate::getInstance()->container[0]['activeLanguages']) === false) {
            PigTranslate::getInstance()->container[0]['activeLanguages'][] = $language;
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    function onLeave(PlayerQuitEvent $event) {
        $name = $event->getPlayer()->getName();
        $pData = new Config(PigTranslate::getInstance()->userDataFolder . strtolower($name) . '.yml', Config::YAML);
        $pData->set('language', PigTranslate::getInstance()->container[0]['players'][$name]['lang']);
        $pData->save();
        unset(PigTranslate::getInstance()->container[0]['players'][$name]);
    }
}