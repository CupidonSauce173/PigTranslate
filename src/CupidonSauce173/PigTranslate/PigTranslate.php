<?php


namespace CupidonSauce173\PigTranslate;


use CupidonSauce173\PigTranslate\Utils\TranslateThread;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

use pocketmine\utils\Config;
use Thread;
use Volatile;

use function file_exists;
use function array_search;


class PigTranslate extends PluginBase implements Listener
{
    const ALL_PLAYERS = 0;
    const SINGLE_PLAYER = 1;
    const MESSAGE_BROADCAST = 2;

    public string $userDataFolder;

    # Threaded items
    public Thread $translateThread;
    public Volatile $container;

    public static PigTranslate $instance;

    public const languages = [
        'en' => 'english', # English
        'ar' => 'arabic', # Arabic
        'zh' => 'chinese', # Chinese
        'nl' => 'dutch', # Dutch
        'fi' => 'finish', # Finish
        'fr' => 'french', # French
        'de' => 'german', # German
        'hi' => 'hindi', # Hindi
        'hu' => 'hungarian', # Hungarian
        'id' => 'indonesian', # Indonesian
        'ga' => 'irish', # Irish
        'it' => 'italian', # Italian
        'ja' => 'japanese', # Japanese
        'ko' => 'korean', # Korean
        'pl' => 'polish', # Polish
        'pt' => 'portuguese', # Portuguese
        'ru' => 'russian', # Russian
        'es' => 'spanish', # Spanish
        'sw' => 'swedish', # Swedish
        'tr' => 'turkish', # Turkish
        'uk' => 'ukrainian', # Ukrainian
        'vi' => 'vietnamese' # Vietnamese
    ];

    # Server Events

    function onEnable()
    {
        self::$instance = $this;
        # Preparing multi-thread system,
        $this->container = new Volatile();
        $this->container[] = [
            'activeLanguages' => [],
            'messagesToSend' => [],
            'players' => [],
            'messageQueue' => [],
            'pluginMessages' => []]; // To implement later.

        # Config & Data file preparation.
        $this->userDataFolder = $this->getDataFolder() . 'players/';
        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->saveResource('config.yml');
        }
        if (!file_exists($this->userDataFolder)) {
            @mkdir($this->userDataFolder, 0777, true);
        }
        $config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->container[1] = $config->getAll();

        # Starting the TranslateThread.
        $this->translateThread = new TranslateThread($this->container);
        $this->translateThread->start();

        $this->getServer()->getPluginManager()->registerEvents(new EventsListener(), $this);
        $this->getServer()->getCommandMap()->register('PigTranslate', new Cmd());

        # Tasks field

        # This task will look if a message has to be sent to a player, if yes, it will send it and unset the value from the array.
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                foreach ($this->container[0]['messagesToSend'] as $value => $data) {
                    /** @var Player $player */
                    $player = $this->getServer()->getPlayer($data['player']);
                    switch ($data['type']) {
                        case self::MESSAGE_BROADCAST:
                            if ($data['sender'] === $player->getName()) {
                                $player->sendMessage($data['sender'] . $data['chat_format'] . $data['originalMessage']);
                                break;
                            }
                            $player->sendMessage($data['sender'] . $data['chat_format'] . $data['message']);
                            break;
                        case self::ALL_PLAYERS:
                            $player->sendMessage($data['message']);
                            break;
                        case self::SINGLE_PLAYER:
                            //
                            $player->sendMessage($data['message']);
                            break;
                    }
                    unset($this->container[0]['messagesToSend'][$value]);
                }
            }
        ), $this->container[1]['broadcast-task']);

        # This task will look if there are active languages that nobody uses and flush them, runs every 1 minute.
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                # Construct list of used languages (from the players array).
                $langList = [];
                foreach ($this->container[0]['players'] as $player) {
                    if (!isset($langList[$player['lang']])) {
                        $langList[] = $player['lang'];
                    }
                }
                # Remove all languages from langList from the activeLanguages list.
                $activeLang = (array)$this->container[0]['activeLanguages'];
                foreach ($langList as $lang) {
                    unset($activeLang[array_search($lang, $activeLang)]);
                }
                # Remove all unused languages.
                foreach ($activeLang as $lang) {
                    $index = array_search($lang, (array)$this->container[0]['activeLanguages']);
                    unset($this->container[0]['activeLanguages'][$index]);
                }
            }
        ), 20 * 60);
    }

    function onDisable()
    {
        # This will stop the TranslateThread
        $this->container[0]['runThread'] = false;
    }

    /**
     * @return PigTranslate|void
     */
    function onLoad()
    {
        self::$instance = $this;
    }

    # Public API

    /**
     * @return PigTranslate
     */
    static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @param string $message
     * @param string $targetLanguage
     * @param int $type
     * @param Player|null $playerTarget
     * @param Player|null $sender
     */
    static function Translate(string $message, string $targetLanguage, int $type, Player $playerTarget = null, Player $sender = null)
    {
        switch ($type) {
            case self::MESSAGE_BROADCAST:
                self::getInstance()->container[0]['messageQueue'][] =
                    [
                        'message' => $message,
                        'type' => self::MESSAGE_BROADCAST,
                        'target' => $targetLanguage,
                        'sender' => $sender->getName(),
                        'chat_format' => ' > ' # Add PureChat support later.
                    ];
                break;
            case self::ALL_PLAYERS:
                self::getInstance()->container[0]['messageQueue'][] =
                    [
                        'message' => $message,
                        'type' => self::ALL_PLAYERS,
                        'target' => $targetLanguage
                    ];
                break;

            case self::SINGLE_PLAYER:
                self::getInstance()->container[0]['messageQueue'][] =
                    [
                        'message' => $message,
                        'type' => self::SINGLE_PLAYER,
                        'target' => $targetLanguage,
                        'playerTarget' => $playerTarget
                    ];
                break;
        }
    }
}