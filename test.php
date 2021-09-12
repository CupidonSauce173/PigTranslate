<?php

if(!function_exists('readline')) {
    function readline($prompt = null){
        if($prompt){
            echo $prompt;
        }
        $fp = fopen("php://stdin","r");
        return rtrim(fgets($fp, 1024));
    }
}

new TestScript();



class TestScript
{
    private array $container;

    public function __construct()
    {
        $this->container = [
            'activeLanguages' => [],
            'messagesToSend' => [],
            'players' =>
                [
                    'TheShinPin' => 'en',
                    'CupidonSauce173' => 'fr',
                    'MamaNeeds' => 'en',
                    'ShowyNinja' => 'es'
                ]
        ];

        $a = readline('Enter message to translate: ');
        $this->processMessages($a);
        $a = readline('Enter another message: ');
        $this->processMessages($a);
        $a = readline('Again!: ');
        $this->processMessages($a);
    }

    function processMessages($message): void
    {
        # Adding languages when not found
        foreach ($this->container['players'] as $player => $value) {
            if (array_search($value, $this->container['activeLanguages']) === false) {
                $this->container['activeLanguages'][] = $value;
            }
        }

        foreach ($this->container['activeLanguages'] as $lang) {
            foreach ($this->container['players'] as $player => $value) {
                if ($value === $lang) {
                    $msg = $this->RequestTranslation($message, $lang);
                    $this->container['messagesToSend'][$player][] = $msg;
                }
            }
        }
    }

    function RequestTranslation(string $message, string $targetLanguage): string
    {
        $raw_data = [
            'q' => $message,
            'source' => 'auto',
            'target' => $targetLanguage
        ];
        $data = json_encode($raw_data);

        # Creation & Execution of the cURL request.

        $curl = curl_init('192.168.220.128/translate');
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
        return $result->translatedText;
    }
}