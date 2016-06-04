<?php
/**
 * Created by PhpStorm.
 * User: Mohamad Amin
 * Date: 3/26/2016
 * Time: 12:50 AM
 */

echo microtime(true);


require __DIR__ . '/vendor/autoload.php';
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

$API_KEY = '192363220:AAFSdry9_scVmTYoq9RSXM01FtKv8PNqK5k';
$BOT_NAME = 'MohandesplusBot';

try {
    // Create Telegram API object
    $telegram = new Telegram($API_KEY, $BOT_NAME);

    $data = [];
    $data['chat_id'] = '116838684';
    $data['parse_mode'] = 'Markdown';
    $data['text'] = 'Hi There'.
        '[ ](http://192.99.103.107/api/mohandesplusbot/images/photo/file_35.jpg)';

    Request::sendMessage($data);

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    echo $e;
}