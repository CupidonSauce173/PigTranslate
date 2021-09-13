<p align="center">
<img width="150" height="150" src="https://github.com/CupidonSauce173/PigTranslate/blob/main/PigTranslateIcon.jpg" />
</p>
<h1 align="center"> PigTranslate </h1>
<p align="center">Join my discord: https://discord.gg/2QAPHbqrny </p>
<p align="center">This is a multi-threaded real-time translation system for PMMP using machine-learning technology </p>

### Little note before using

This project is only a proof of concept and shall not be used in any kind of production server, unlike Google Traduction or Microsoft Azure, there aren't any army behind this system, which makes it less reliable than other big services like the ones I mentionned, **BUT**, this is an open-source engine that you can modify like you want, it's completely free and you can host it in your own machines which makes it great. If you know what you are doing, you can easily make this engine way better.

### Prerequisites

This plugin uses LibreTranslate that is based on Argos Translate library, you will need to do the installation of LibreTranslate in order to use this plugin, here is the github link : https://github.com/LibreTranslate/LibreTranslate
Also, if you want to learn more about Argos Translate Library, you can access it from there : https://github.com/argosopentech/argos-translate

### Introduction

This plugin aim to create a real-time translation system for the chat of PocketMine-MP (PMMP) per user. This means that if there are 2 players that speaks spanish, 1 french and 4 english players, each of these players will get the chat in the right language (so all messages from the spanish players will be translated from spanish -> english and sent to the english players and vise-versa). This plugin is made so all cURL requests are made over a TranslateThread class, so the main thread won't have to do the requests. The only processes that the main thread will have to do are looking for messages to send, send them to the players and also flushing unused languages so the plugin won't have to translate them for no reason.

### Player Data

The player language preference is saved in a .yml file (for now, it will change in the future). They can, with /language (or /lang), set their language preference to one of the registered languages (the list is below). If they have no file yet, one will be created with a default language that you can set in your config file.

### Plugin Logic

There is a container variable which is a volatile object (so it can be modified by multiple threads) that stores the list of players, the configs of the server, the active languages, the messages to send and the message queue to translate, when a player sends a message, a new value will be created in the messageQueue and the TranslateThread will create a new request to the LibreTranslate API, as it is right now, it will call the MESSAGE_BROADCAST constant. When this constant is put in the Translate method, it will look for all active languages and create one request for each one of them. When the requests are done, it will create new values in the messagesToSend array, a repeating task in the main thread will loop throught this array and send the messages to the right targets and then delete the values. Every x amount of seconds, the main thread will also look for unused languages to flush, so there isn't any useless request made.

### Translation Fail Handling

If a translation fails, there won't be any issue, the original message will be sent anyway (which meansit won't be translated).

### Command

To change a language, the player will have to **/language (language)**, if there is no argument, it will send a list of available languages and also tell them their current setting.
  
<p align="center">
<img src="https://github.com/CupidonSauce173/PigTranslate/blob/main/image_01.PNG" />
</p>

### Plugin Configs

```yml
# Default language that the players will have when first joining the server.
defaultLanguage: "en"
# IP address of your LibreTranslate instance.
ip: "127.0.0.1/translate"

# Tasks field

# How often do you want the active languages to be cleaned (in seconds)
clean-languages: 60
# How often do you want the TranslateThread to translate the messages ? (in seconds)
# Note: 0.5 is what I think is the best for "real-time" translation, or else the delay will be too high.
translate-thread: 0.5
# How often do you want the messages to be sent to the players ? (in milliseconds)
broadcast-task: 10
```

### Available Languages

| **Index** | **Name** |
| ------------ | :---------- |
| en | english |
| ar | arabic |
| zh | chinese |
| nl | dutch |
| fi | finish |
| fr | french |
| de | german |
| hi | hindi |
| hu | hungarian |
| id | indonesian |
| ga | irish |
| it | italian |
| ja | japanese |
| ko | korean |
| pl | polish |
| pt | portuguese |
| ru | russian |
| es | spanish |
| sw | swedish |
| tr | turkish |
| uk | ukrainian |
| vi | vietnamese |
