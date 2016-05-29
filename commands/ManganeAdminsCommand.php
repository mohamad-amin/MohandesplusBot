<?php

/**
 * Created by PhpStorm.
 * User: Mohamad Amin
 * Date: 3/26/2016
 * Time: 3:22 PM
 */

namespace Longman\TelegramBot\Commands\UserCommands {

    use Longman\TelegramBot\Entities\ReplyKeyboardHide;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;
    use Longman\TelegramBot\Conversation;
    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;

    class ManageAdminsCommand extends UserCommand {

        protected $name = 'manageadmins';                      //your command's name
        protected $description = 'مدیریت ادمین‌ها';          //Your command description
        protected $usage = '/manageadmins';                    // Usage of your command
        protected $version = '1.0.0';
        protected $enabled = true;
        protected $public = true;
        protected $message;

        protected $conversation;

        public function execute() {

            $channels = [];
            $message = $this->getMessage();              // get Message info

            $chat = $message->getChat();
            $user = $message->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $text = $message->getText(true);
            $message_id = $message->getMessageId();      //Get message Id

            $data = [];
            $data['reply_to_message_id'] = $message_id;
            $data['chat_id'] = $chat_id;

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
            if (!isset($this->conversation->notes['state'])) {
                $state = '0';
            } else {
                $state = $this->conversation->notes['state'];
            }

            if ($text == 'بازگشت') {
                --$state;
                $this->conversation->notes['state'] = $state;
                $this->conversation->update();
                $text = '';
            }

            switch ($state) {
                case 0:
                    if (empty($text)) {
                        $data['text'] = 'گزینه‌ی مورد نظر را انتخاب کنید:';
                        $keyboard = [];
                        $keyboard[] = ['➕ افزودن کانال', '➖ حذف کانال', '📣 مدیریت کانال‌ها'];
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
                    $shouldContinue = false;
                    switch ($text) {
                        case '➕ افزودن کانال':
                            $this->conversation->stop();
                            $this->telegram->executeCommand('addchannel');
                            break;
                        case '➖ حذف کانال':
                            if (count(\AdminDatabase::getAdminsChannels($user->getUserName())) > 0) {
                                $this->conversation->stop();
                                $this->telegram->executeCommand('removechannel');
                            } else {
                                $tData['chat_id'] = $chat_id;
                                $tData['text'] = "شما کانالی برای حذف ندارید :(";
                                $tData['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                                Request::sendMessage($tData);
                                $this->conversation->stop();
                                $this->telegram->executeCommand('start');
                            }
                            break;
                        case '📣 مدیریت کانال‌ها':
                            if (count(\AdminDatabase::getAdminsChannels($user->getUserName())) > 0) {
                                $this->conversation->notes['state'] = ++$state;
                                $this->conversation->update();
                                $shouldContinue = true;
                            } else {
                                $tData['chat_id'] = $chat_id;
                                $tData['text'] = "شما کانالی برای مدیریت ندارید :(";
                                $tData['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                                Request::sendMessage($tData);
                                $this->conversation->stop();
                                $this->telegram->executeCommand('start');
                            }
                            break;
                    }
                    $text = '';
                    if (!$shouldContinue) break;
                case 1:
                    $channels = \AdminDatabase::getAdminsChannels($user->getUsername());
                    if (empty($channels) || !in_array($text, $channels)) {
                        if (!empty($text)) $data['text'] = 'لطفا کانال را درست انتخاب کنید:';
                        else $data['text'] = 'کانال را انتخاب کنید:';
                        $keyboard = [];
                        $i = 0;
                        foreach ($channels as $channel) {
                            $j = (int) floor($i/3);
                            $keyboard[$j][$i%3] = $channel;
                            $i++;
                        }
                        $keyboard[] = ['بیخیال', 'بازگشت'];
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
                    $this->conversation->notes['channelName'] = $text;
                    $text = '';
                    $this->conversation->notes['state'] = ++$state;
                    $this->conversation->update();
                case 3:
                    if (empty($text)) {
                        $data = [];
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $keyboard = [
                            ['مشاهده‌ی ادمین‌ها', 'حذف ادمین', 'افزودن ادمین'],
                            ['بازگشت', 'بیخیال']
                        ];
                        $data['text'] = 'گزینه‌ی موردنظر را انتخاب کنید:';
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
                    $channel = $this->conversation->notes['channelName'];
                    switch ($text) {
                        case 'مشاهده‌ی ادمین‌ها':
                            $helpers = explode(',', \AdminDatabase::getHelpersFromChannel($channel, $user->getUsername()));
                            
                            break;
                        case 'حذف ادمین':
                            break;
                        case 'افزودن ادمین':
                            break;
                    }
                    $this->conversation->notes['messageText'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 2:
                    if (empty($text) || !is_numeric($text)) {
                        $data = [];
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'سال ارسال پیام خود را وارد کنید';
                        $keyboard = [
                            ['1395', '1396', '1397'],
                            ['بازگشت', 'بیخیال']
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
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ماه ارسال پیام را وارد کنید:';
                        $keyboard = [
                            ['1', '2', '3', '4'],
                            ['5', '6', '7', '8'],
                            ['9', '10', '11', '12'],
                            ['بازگشت', 'بیخیال']
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
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'روز ارسال پیام را وارد کنید:';
                        if ($this->conversation->notes['month'] < 7) {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', '31', ' '],
                                ['بازگشت', 'بیخیال']
                            ];
                        } else {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', ' ', ' '],
                                ['بازگشت', 'بیخیال']
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
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ساعت (۲۴ ساعته) ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت', 'بیخیال']];
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
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'دقیقه‌ی ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت', 'بیخیال']];
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
                    if (empty($text) || !($text == 'ارسال')) {
                        $this->conversation->update();

                        $time = $this->conversation->notes['year'].'-'.
                            $this->conversation->notes['month'].'-'.
                            $this->conversation->notes['day'].'-'.
                            $this->conversation->notes['hour'].'-'.
                            $this->conversation->notes['minute'];

                        $keyboard = [['ارسال', 'بازگشت', 'بیخیال']];
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'پیش نمایش:';
                        Request::sendMessage($data);
                        $data['text'] = $this->conversation->notes['messageText'];
                        Request::sendMessage($data);
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
                        $data['reply_markup'] = $reply_keyboard_markup;
                        $data['text'] = 'برای ارسال پست بالا در تاریخ و زمان '.
                            \PersianDateFormatter::format($this->conversation->notes).' دکمه‌ی ارسال را کلیک کنید. ';
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $databaser->addMessageToDatabase(
                        $this->conversation->notes['messageText'] . "\n" . '@mohandes_plus',
                        '@' . $this->conversation->notes['channelName'],
                        $chat_id,
                        $this->conversation->notes['year'].'-'.
                        $this->conversation->notes['month'].'-'.
                        $this->conversation->notes['day'].'-'.
                        $this->conversation->notes['hour'].'-'.
                        $this->conversation->notes['minute'],
                        ($this->conversation->notes['edit_time'] == null) ? 0 : $this->conversation->notes['edit_time']
                    );
                    $data = [];
                    $data['reply_to_message_id'] = $message_id;
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

    class AdminDatabase {
        
        public static function isUserAdminAtChannel($user, $channel) {

            $data = Database::getDatabase()->select('admin', '*', [
                'AND' => [
                    "Channel" => $channel
                ]
            ]);

            foreach ($data as $item) {
                if ($item['Admin'] == $user) return true;
            }

            return false;
        }

        public static function addHelperToChannel($channel, $admin, $helper) {

            if (self::isUserAdminAtChannel($admin, $channel)) {

                $data = Database::getDatabase()->select('admin', '*', [
                    'AND' => [
                        "Channel" => $channel
                    ]
                ]);

                foreach ($data as $item) {
                    $helpers = $item['Helpers'];
                    if (strlen($helpers) === 0) {
                        $helpers = $helper;
                    } else {
                        $helpers = ','.$helper;
                    }
                    Database::getDatabase()->update('admin',
                        ['Helpers' => $helpers],
                        ['Channel' => $channel]
                    );
                    return true;
                }

                return false;

            } else return false;

        }

        public static function getChannels() {
            $data = Database::getDatabase()->select('admin', '*');
            $channels = [];
            foreach ($data as $row) {
                $channels[] = $row['Channel'];
            }
            return $channels;
        }

        public static function removeHelperFromChannel($channel, $admin, $helper) {
            
            if (self::isUserAdminAtChannel($admin, $channel)) {

                $data = Database::getDatabase()->select('admin', '*', [
                    'AND' => [
                        "Channel" => $channel
                    ]
                ]);

                foreach ($data as $item) {
                    $helpers = explode(',', $item['Helpers']);
                    for ($i=0; $i<count($helpers); $i++) {
                        if ($helpers[$i] == $helper) {
                            array_splice($helpers, $i, 1);
                            break;
                        }
                    }
                    Database::getDatabase()->update('admin',
                        ['Helpers' => implode(',', $helpers)],
                        ['Channel' => $channel]
                    );
                    return true;
                }

                return false;

            } else return false;
            
        }

        public static function getHelpersFromChannel($channel, $admin) {

            if (self::isUserAdminAtChannel($admin, $channel)) {
                
                $data = Database::getDatabase()->select('admin', 'Helpers', [
                    'Channel' => $channel
                ]);
                foreach ($data as $item) {
                    return explode(',', $item['Helpers']);
                }
                return null;

            } else return null;

        }

        public static function userCanPostAtChannel($user, $channel) {

            $data = Database::getDatabase()->select('admin', '*', [
                'Channel' => $channel
            ]);

            foreach ($data as $row) {
                if ($row['Admin'] == $user || strpos($row['Helpers'], $user) !== false) {
                    return true;
                }
            }

            return false;

        }

        public static function getAdminsChannels($user) {

            $data = Database::getDatabase()->select('admin', '*', [
                'Admin' => $user
            ]);

            $channels = [];
            foreach ($data as $row) {
                $channels[] = $row['Channel'];
            }

            return $channels;

        }
        
    }

    class Database {

        public static function getDatabase() {
            return new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => 'MohandesPlus',
                'charset' => 'utf8mb4'
            ]);
        }

    }

}