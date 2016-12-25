<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands {

    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Conversation;
    use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;

    /**
 * Start command
 */
class StartCommand extends UserCommand {

    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'start';
    protected $description = 'دستور شروع';
    protected $usage = '/start';
    protected $version = '1.0.0';
    protected $enabled = true;
    protected $public = true;
    protected $message;
    /**#@-*/

    protected $telegram;

    public function __construct(Telegram $telegram, $update) {
        parent::__construct($telegram, $update);
        $this->telegram = $telegram;
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {

        $message = $this->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $text = $message->getText();

        $send = false;
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        if (strpos($text, '/delete') !== false) {
            $id = substr($text, 7);
            \PostDeleter::deletePost($id, $chat_id, $this->telegram);
        } else if ($text == '➕ افزودن پست') {
            $text = "نوع پست را انتخاب کنید:";
            $keyboard = [
                ["عکس و متن", "متن"],
                ["گیف و متن", "فیلم و متن"],
                ["بازگشت ⬅️", "فوروارد پست"]
            ];
            $send = true;
        } else if ($text == 'متن') {
            $this->conversation->stop();
            $this->telegram->executeCommand("sendtext");
        } else if ($text == 'عکس و متن') {
            $this->conversation->stop();
            $this->telegram->executeCommand("sendphoto");
        } else if ($text == 'فیلم و متن') {
            $this->conversation->stop();
            $this->telegram->executeCommand("sendvideo");
        } else if ($text == 'گیف و متن') {
            $this->conversation->stop();
            $this->telegram->executeCommand("sendgif");
        } else if ($text == 'مدیریت پست‌های درصف ارسال') {
            \PostAdmin::showMessages($chat_id);
        } else if ($text == '⚙ ابزارهای مدیریتی') {
            $text = "یک گزینه را انتخاب کنید:";
            $keyboard = [
                ['مدیریت پست‌های درصف ارسال'],
                ['مدیریت کانال‌ها و ادمین‌ها'],
                ['بازگشت ⬅️'],
            ];
            $send = true;
        } else if ($text == 'مدیریت کانال‌ها و ادمین‌ها') {
            $this->conversation->stop();
            $this->telegram->executeCommand('manageadmins');
        } else if ($text == 'فوروارد پست') {
            $this->conversation->stop();
            $this->telegram->executeCommand('forwardmessage');
        } else {
            $keyboard = [
                ["➕ افزودن پست"],
                ["⚙ ابزارهای مدیریتی"],
                ["راهنما", "✉️ ارتباط با ما"]
            ];
            $text = 'یکی‌از گزینه‌ها را انتخاب کنید.';
            $send = true;
        }

        if ($send) {
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text,
            ];
            $data['reply_markup'] = new ReplyKeyboardMarkup(
                [
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false,
                    'selective' => true
                ]
            );
            return Request::sendMessage($data);
        } else {
            return true;
        }

    }
}
}

namespace {

    use Longman\TelegramBot\Request;

    require __DIR__ . '/../vendor/autoload.php';

    class PostAdmin {

        public static function showMessages($chat_id) {
            $calendar = new jDateTime(true, true, 'UTC');
            $database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => 'MohandesPlus',
                'charset' => 'utf8mb4'
            ]);
            $datas = $database->select("queue", "*", [
                "AND" => [
                    "ChatId" => intval($chat_id)
                ],
                'ORDER' => 'Time'
            ]);
            $i = 0;
            $date = '';
            foreach ($datas as $data) {
                $i++;
                $tData = [];
                $time = $data['Time'] + 16240;
                $tData['chat_id'] = $chat_id;
                $newDate = $calendar->date("l Y/m/d", $time);
                if ($date != $newDate) {
                    $tData['text'] = '➖➖➖➖➖‏➖➖➖➖'."\n".'پست‌های درصف انتظار در تاریخ:'."\n".$newDate."\n".'➖➖➖➖➖‏➖➖➖➖';
                    Request::sendMessage($tData);
                    $tData = [];
                    $tData['chat_id'] = $chat_id;
                }
                $date = $newDate;
                Request::sendMessage([
                   'chat_id' => $chat_id,
                    'text' => 'زمان :'.$calendar->date('H:i', $time)
                ]);
                switch ($data['Type']) {
                    case 1:
                        $tData['text'] = $data['Text']."\n"."➖➖➖➖➖‏➖➖➖➖"."\n"."❌حذف پست‏: /delete".$time;
                        Request::sendMessage($tData);
                        break;
                    case 2:
                        if (strlen($data['Text']) > 200) {
                            $tData['parse_mode'] = 'Markdown';
                            $tData['text'] = $data['Text'].
                                // Todo: maybe sometimes bug
                                '[ ]('.$data['Photo'].')'."\n"."➖➖➖➖➖‏➖➖➖➖"."\n"."❌حذف پست‏: /delete".$data['Time'];
                            Request::sendMessage($tData);
                        } else {
                            $tData['photo'] = $data['Photo'];
                            $tData['caption'] = $data['Text']."\n"."➖➖➖➖➖‏➖➖➖➖"."\n"."❌حذف پست‏: /delete".$data['Time'];
                            Request::sendPhoto($tData);
                        }
                        break;
                    case 3:
                        $tData['video'] = $data['Video'];
                        $tData['caption'] = $data['Text']."\n"."➖➖➖➖➖‏➖➖➖➖"."\n"."❌حذف پست‏: /delete".$data['Time'];
                        Request::sendVideo($tData);
                        break;
                    case 4:
                        break;
                    case 5:
                        $tData['document'] = $data['Photo'];
                        $tData['caption'] = $data['Text']."\n"."➖➖➖➖➖‏➖➖➖➖"."\n"."❌حذف پست‏: /delete".$data['Time'];
                        Request::sendDocument($tData);
                        break;
                }
            }
            if ($i == 0) {
                $tData = [];
                $tData['chat_id'] = $chat_id;
                $tData['text'] = 'پستی یافت نشد :(';
                Request::sendMessage($tData);
            }
        }

    }

    class PostDeleter {

        public static function deletePost($time, $chat_id, $telegram) {
            $database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => '',
                'password' => 'Sooooo genius',
                'charset' => 'utf8mb4'
            ]);
            $tData = [];
            $tData['chat_id'] = $chat_id;
            Request::sendMessage($tData);
            $result = $database->delete("queue", [
                    "AND" => [
                        "Time" => $time
                    ]
                ]);
            if ($result) {
                $tData['text'] = "با موفقیت حذف شد :)";
            } else {
                $tData['text'] = "خطا در حذف :( لطفا مجددا تلاش کنید.";
            }
            Request::sendMessage($tData);
        }

    }

}
