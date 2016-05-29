<?php
/**
 * Created by PhpStorm.
 * User: Mohamad Amin
 * Date: 3/26/2016
 * Time: 12:50 AM
 */

echo microtime(true);

//
//require __DIR__ . '/vendor/autoload.php';
//use Longman\TelegramBot\Request;
//use \Longman\TelegramBot\Telegram;
//use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
//use GO\Scheduler;
//
//$API_KEY = '192363220:AAFSdry9_scVmTYoq9RSXM01FtKv8PNqK5k';
//$BOT_NAME = 'MohandesplusBot';
//
//function myFunc() {
//    return "Hello world from function!";
//}
//
//phpinfo();
//
//try {
//    // Create Telegram API object
//    $telegram = new Telegram($API_KEY, $BOT_NAME);
//
//    $scheduler = new Scheduler([
//        'emailFrom' => 'myemail@address.from'
//    ]);
//    $procrastinator->register(
//        $procrastinator
//            ->newDeferred()
//            ->name('some other name')
//            ->call(function() {sleep(10);})
//            ->build()
//    );
//
//    $procrastinator->schedule();
//
//    $scheduler->call(function() {
//        $channels['Mohandestest'] = array('LeMohamadAmin');
//        $data = [];
//        $data['chat_id'] = '116838684';
//        $data['text'] = 'Keyboard test';
//        $keyboard = [];
//        $i = 0;
//        $data['text'].= ' before';
//        foreach ($channels as $key => $value) {
//            $j = (int) floor($i/3);
//            $keyboard[$j][$i%3] = $key;
//            $i++;
//        }
//        $data['reply_markup'] = new ReplyKeyboardMarkup(
//            [
//                'keyboard' => $keyboard ,
//                'resize_keyboard' => true,
//                'one_time_keyboard' => false,
//                'selective' => true
//            ]
//        );
//        $result = Request::sendMessage($data);
//    })->at('');
//
//
//} catch (Longman\TelegramBot\Exception\TelegramException $e) {
//    // log telegram errors
//    echo $e;
//}