<?php

/**
 * Created by PhpStorm.
 * User: Mohamad Amin
 * Date: 3/26/2016
 * Time: 3:22 PM
 */

namespace Longman\TelegramBot\Commands\UserCommands {

    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Conversation;
    use Longman\TelegramBot\Entities\ReplyKeyboardHide;
    use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;

    class ForwardMessageCommand extends UserCommand {

        protected $name = 'forwardmessage';                      //your command's name
        protected $description = 'فوروارد پست';          //Your command description
        protected $usage = '/forwardmessage';                    // Usage of your command
        protected $version = '1.0.0';
        protected $enabled = true;
        protected $public = true;
        protected $message;

        protected $conversation;
        protected $telegram;

        public function __construct(Telegram $telegram, $update) {
            parent::__construct($telegram, $update);
            $this->telegram = $telegram;
        }

        public function execute() {

            $databaser = new \ForwardDatabaser();
            $message = $this->getMessage();              // get Message info

            $chat = $message->getChat();
            $user = $message->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $text = $message->getText(true);
            $message_id = $message->getMessageId();      //Get message Id

            $data = [];
            $data['chat_id'] = $chat_id;
            $channels = \AdminDatabase::getHelpersChannels($user->getUsername());
            if ($text == 'فوروارد') {
                $text = '';
            }

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
            if (!isset($this->conversation->notes['state'])) {
                $state = '0';
            } else {
                $state = $this->conversation->notes['state'];
            }

            if ($text == 'بازگشت ⬅️') {
                --$state;
                $this->conversation->notes['state'] = $state;
                $this->conversation->update();
                $text = '';
            }

            switch ($state) {
                case 0:
                    if (empty($text) || !in_array($text, $channels)) {
                        if (!empty($text) && !in_array($text, $channels)) {
                            $data = [];
                            $data['chat_id'] = $chat_id;
                            $data['text'] = 'متاسفیم. به نظر نمیاید که شما ادمین این کانال باشید :(';
                            $data['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                            $result = Request::sendMessage($data);
                            $this->conversation->stop();
                            $this->telegram->executeCommand("start");
                            break;
                        } else {
                            $data['text'] = 'کانال را انتخاب کنید:';
                            $keyboard = [];
                            $i = 0;
                            foreach ($channels as $key) {
                                $j = (int) floor($i/3);
                                $keyboard[$j][$i % 3] = $key;
                                $i++;
                            }
                            $keyboard[] = ['❌ بی‌خیال'];
                            $data['reply_markup'] = new ReplyKeyboardMarkup(
                                [
                                    'keyboard' => $keyboard,
                                    'resize_keyboard' => true,
                                    'one_time_keyboard' => true,
                                    'selective' => true
                                ]
                            );
                            $result = Request::sendMessage($data);
                            break;
                        }
                    }
                    $this->conversation->notes['channelName'] = $text;
                    $text = '';
                    $this->conversation->notes['state'] = ++$state;
                    $this->conversation->update();
                case 1:
                    if ($message->getForwardDate() == null) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['text'] = 'پست را فوروارد کنید:';
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }

                    $this->conversation->notes['type'] = 1;
                    if ($text != null) {
                        $this->conversation->notes['text'] = $text."\n".'@mohandes_plus';
                    } else if ($message->getCaption() != null) {
                        $this->conversation->notes['text'] = $message->getCaption()."\n".'@mohandes_plus';
                    } else {
                        $this->conversation->notes['text'] = '@mohandes_plus';
                    }
                    if ($message->getPhoto() != null) {
                        $this->conversation->notes['photo'] =
                            $message->getPhoto()[count($message->getPhoto())-1]->getFileId();
                        $this->conversation->notes['type'] = 2;
                    }
                    if ($message->getVideo() != null) {
                        $this->conversation->notes['video'] = $message->getVideo()->getFileId();
                        $this->conversation->notes['type'] = 3;
                    }
                    if ($message->getDocument() != null) {
                        $this->conversation->notes['photo'] = $message->getDocument()->getFileId();
                        $this->conversation->notes['type'] = 5;
                    }
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 2:
                    if (empty($text) || !is_numeric($text)) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'سال ارسال پیام خود را وارد کنید';
                        $keyboard = [
                            ['1395', '1396', '1397'],
                            ['بازگشت ⬅️', '❌ بی‌خیال']
                        ];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['year'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 3:
                    if (empty($text) || !is_numeric($text) || intval($text)<1 || intval($text)>12) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ماه ارسال پیام را وارد کنید:';
                        $keyboard = [
                            ['1', '2', '3', '4'],
                            ['5', '6', '7', '8'],
                            ['9', '10', '11', '12'],
                            ['بازگشت ⬅️', '❌ بی‌خیال']
                        ];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['month'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 4:
                    if (empty($text) || !is_numeric($text) || intval($text)<1 || intval($text)>31) {
                        $this->conversation->update();
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'روز ارسال پیام را وارد کنید:';
                        if ($this->conversation->notes['month'] < 7) {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', '31', ' '],
                                ['بازگشت ⬅️', '❌ بی‌خیال']
                            ];
                        } else {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', ' ', ' '],
                                ['بازگشت ⬅️', '❌ بی‌خیال']
                            ];
                        }
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['day'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 5:
                    if (empty($text) || !is_numeric($text) || intval($text)<0 || intval($text)>24) {
                        $this->conversation->update();
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ساعت (۲۴ ساعته) ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['hour'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 6:
                    if (empty($text) || !is_numeric($text) || intval($text)<0 || intval($text)>60) {
                        $this->conversation->update();
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'دقیقه‌ی ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['minute'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 7:
                    if (empty($text) || !($text == '✔️ تایید و ارسال')) {
                        $this->conversation->update();

                        $time = $this->conversation->notes['year'].'-'.
                            $this->conversation->notes['month'].'-'.
                            $this->conversation->notes['day'].'-'.
                            $this->conversation->notes['hour'].'-'.
                            $this->conversation->notes['minute'];

                        $keyboard = [['✔️ تایید و ارسال'],['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'پیش نمایش:';
                        Request::sendMessage($data);
                        $tData['chat_id'] = $chat_id;
                        switch ($this->conversation->notes['type']) {
                            case 1:
                                $tData['text'] = $this->conversation->notes['text'];
                                $result = Request::sendMessage($tData);
                                break;
                            case 2:
                                if (strlen($this->conversation->notes['messageText']) > 200) {
                                    $serverResponse = Request::getFile(['file_id' => $this->conversation->notes['photo']]);
                                    if ($serverResponse->isOk()) {
                                        $file_name = $serverResponse->getResult()->getFilePath();
                                        Request::downloadFile($serverResponse->getResult());
                                        $tData['parse_mode'] = 'Markdown';
                                        $path = 'http://scixnet.com/api/mohandesplusbot/images/'.str_replace('_', '', $file_name);
                                        $tData['text'] = $this->conversation->notes['text'].
                                            '[ ]('.$path.')';
                                        $this->conversation->notes['photo'] = $path;
                                        $this->conversation->update();
                                        Request::sendMessage($tData);
                                    } else {
                                        $tData['text'] = 'Server response not ok :('."\n".@$serverResponse;
                                        Request::sendMessage($tData);
                                    }
                                } else {
                                    $tData['photo'] = $this->conversation->notes['photo'];
                                    $tData['caption'] = $this->conversation->notes['text'];
                                    Request::sendPhoto($tData);
                                }
                                break;
                            case 3:
                                $tData['video'] = $this->conversation->notes['video'];
                                $tData['caption'] = $this->conversation->notes['text'];
                                $result = Request::sendVideo($tData);
                                break;
                            case 4:
                                break;
                            case 5:
                                $tData['document'] = $this->conversation->notes['photo'];
                                $tData['caption'] = $this->conversation->notes['text'];
                                $result = Request::sendDocument($tData);
                                break;
                        }
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        if (\PersianTimeGenerator::getTimeInMilliseconds($time) < round(microtime(true))) {
                            $data['text'] = 'هشدار! زمان انتخابی شما قبل از حال است! در این صورت پیام شما در لحظه فرستاده خواهد شد.';
                            Request::sendMessage($data);
                        }
                        $reply_keyboard_markup = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['reply_markup'] = $reply_keyboard_markup;
                        $data['text'] = 'برای ارسال پست بالا در تاریخ و زمان '.
                            \PersianDateFormatter::format($this->conversation->notes).' دکمه‌ی ارسال را کلیک کنید. ';
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $databaser->addMessageToDatabase(
                        $this->conversation->notes['text'],
                        $this->conversation->notes['photo'],
                        $this->conversation->notes['video'],
                        null,
                        '@' . $this->conversation->notes['channelName'],
                        $chat_id,
                        $this->conversation->notes['year'].'-'.
                        $this->conversation->notes['month'].'-'.
                        $this->conversation->notes['day'].'-'.
                        $this->conversation->notes['hour'].'-'.
                        $this->conversation->notes['minute'],
                        $this->conversation->notes['type']
                    );
                    $data = [];
                    $data['chat_id'] = $chat_id;
                    $data['text'] = "پیام شما ارسال خواهد شد :)";
                    $data['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                    $this->telegram->executeCommand("start");
                    break;
            }

            return $result;

        }



    }
}

namespace {

    require __DIR__ . '/../vendor/autoload.php';

    class ForwardDatabaser {

        public function addMessageToDatabase($text, $photo, $video, $audio,
                                             $channelName, $chatId, $time, $type) {
            /*$database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);*/
            $database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => 'MohandesPlus',
                'charset' => 'utf8mb4'
            ]);
            $database->insert("queue", [
                "Channel" => $channelName,
                "ChatId" => $chatId,
                "Type" => intval($type),
                "Text" => $text,
                "Photo" => $photo,
                "Video" => $video,
                "Audio" => $audio,
                "Time" => PersianTimeGenerator::getTimeInMilliseconds($time),
            ]);
        }

    }

}