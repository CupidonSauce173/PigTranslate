<?php


namespace CupidonSauce173\PigTranslate;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

use function array_search;
use function implode;

class Cmd extends Command
{

    public function __construct()
    {
        parent::__construct(
            'language',
            'Set your language',
            '/language <language>',
            ['lang']);
        $this->setPermission('PigTranslate.permission.language');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!isset($args[0])) {
            $sender->sendMessage(
                'Please select one of these languages: ' . TextFormat::BOLD . implode(', ', PigTranslate::languages)
                . PHP_EOL . TextFormat::RESET .
                TextFormat::GREEN . 'Current language: ' .
                PigTranslate::languages[PigTranslate::getInstance()->container[0]['players'][$sender->getName()]['lang']]
            );
            return;
        }
        $lang = array_search(strtolower($args[0]), PigTranslate::languages);
        if ($lang === false) {
            $sender->sendMessage(
                'Please select one of these languages: ' . TextFormat::BOLD . implode(', ', PigTranslate::languages)
                . PHP_EOL . TextFormat::RESET .
                TextFormat::GREEN . 'Current language: ' .
                PigTranslate::languages[PigTranslate::getInstance()->container[0]['players'][$sender->getName()]['lang']]
            );
            return;
        }
        if (array_search($lang, (array)PigTranslate::getInstance()->container[0]['activeLanguages']) === false) {
            PigTranslate::getInstance()->container[0]['activeLanguages'][] = $lang;
        }
        PigTranslate::getInstance()->container[0]['players'][$sender->getName()]['lang'] = $lang;
        $sender->sendMessage('You successfully changed your language to: ' . $args[0]);
        return;
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return PigTranslate::getInstance();
    }
}