<?php


namespace CupidonSauce173\PigTranslate\Utils;

use CupidonSauce173\PigTranslate\PigTranslate;

use Thread;
use Volatile;

use function microtime;
use function json_encode;
use function json_decode;
use function curl_init;
use function curl_setopt;
use function curl_exec;
use function curl_close;

class TranslateThread extends Thread
{
    private Volatile $container;

    /**
     * @param Volatile $container
     */
    function __construct(Volatile $container)
    {
        $this->container = $container;
        $this->container[0]['runThread'] = true;
    }

    function run()
    {

        $nextTime = microtime(true) + $this->container[1]['translate-thread'];

        while ($this->container[0]['runThread']) {
            if (microtime(true) >= $nextTime) {
                $this->prepareMessages();
                $nextTime = microtime(true) + $this->container[1]['translate-thread'];
            }
        }
    }

    function prepareMessages()
    {
        foreach ($this->container[0]['messageQueue'] as $value => $request) {

            $message = $request['message']; # Message to translate
            $targetLang = $request['target']; # Target language
            $type = $request['type']; # Event type

            unset($this->container[0]['messageQueue'][$value]);

            // Translation field

            $raw_data = [
                'q' => $message,
                'source' => 'auto',
                'target' => $targetLang
            ];
            $data = json_encode($raw_data);

            # Creation & Execution of the cURL request.

            $curl = curl_init($this->container[1]['ip']);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($curl);

            if (!$output) {
                die("Connection Failure");
            }
            curl_close($curl);
            $result = json_decode($output);
            $translatedMessage = $result->translatedText;

            // Field end
            switch ($type) {
                case PigTranslate::MESSAGE_BROADCAST:
                    foreach ($this->container[0]['players'] as $player => $data) {
                        if ($data['lang'] === $targetLang) {
                            $this->container[0]['messagesToSend'][] =
                                [
                                    'player' => $player,
                                    'message' => $translatedMessage,
                                    'originalMessage' => $request['message'],
                                    'sender' => $request['sender'],
                                    'chat_format' => $request['chat_format'],
                                    'type' => PigTranslate::MESSAGE_BROADCAST
                                ];
                        }
                    }
                    break;
                case PigTranslate::ALL_PLAYERS:
                    foreach ($this->container[0]['players'] as $player => $data) {
                        if ($data['lang'] === $targetLang) {
                            $this->container[0]['messagesToSend'][] =
                                [
                                    'player' => $player,
                                    'message' => $translatedMessage,
                                    'type' => PigTranslate::ALL_PLAYERS
                                ];
                        }
                    }
                    break;
                case PigTranslate::SINGLE_PLAYER:
                    if (isset($request['playerTarget'])) {
                        $this->container[0]['messagesToSend'][] =
                            [
                                'player' => $request['playerTarget'],
                                'message' => $translatedMessage,
                                'type' => PigTranslate::SINGLE_PLAYER
                            ];
                    }
                    break;
            }
        }
    }
}