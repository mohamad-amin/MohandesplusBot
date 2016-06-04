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
            $text = $message->getText();
            $message_id = $message->getMessageId();      //Get message Id

            $data = [];
            $data['reply_to_message_id'] = $message_id;
            $data['chat_id'] = $chat_id;
            if (strpos($text, '/deleteadmin') !== false) {
                $text = substr($text, 12);
            }

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
                    $validAnswers = ['📣 مدیریت کانال‌ها', '➕ افزودن کانال', '➖ حذف کانال'];
                    if (empty($text) || !in_array($text, $validAnswers)) {
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
                            $j = (int)floor($i / 3);
                            $keyboard[$j][$i % 3] = $channel;
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
                case 2:
                    $validAnswers = ['مشاهده‌ی ادمین‌ها', 'حذف ادمین', 'افزودن ادمین'];
                    if (empty($text) || !in_array($text, $validAnswers)) {
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
                    $shouldContinue = false;
                    $channel = $this->conversation->notes['channelName'];
                    switch ($text) {
                        case 'مشاهده‌ی ادمین‌ها':
                            $helpers = \AdminDatabase::getHelpersFromChannel($channel, $user->getUsername());
                            $tData = [];
                            $tData['chat_id'] = $chat_id;
                            if ($helpers != null && count($helpers) > 0) {
                                $tData['text'] = '';
                                for ($i = 0; $i < count($helpers); $i++) {
                                    $tData['text'] .= ($i+1) . '. @' . $helpers[$i] . "\n";
                                }
                            } else {
                                $tData['text'] = 'کانال ' . '@' . $this->conversation->notes['channelName'] . ' ادمینی ندارد.';
                            }
                            Request::sendMessage($tData);
                            break;
                        case 'حذف ادمین':
                            $helpers = \AdminDatabase::getHelpersFromChannel($channel, $user->getUsername());
                            $tData = [];
                            $tData['chat_id'] = $chat_id;
                            if ($helpers != null && count($helpers) > 0) {
                                $tData['text'] = 'ادمین مورد نظر را انتخاب کنید:';
                                Request::sendMessage($tData);
                                $tData['text'] = '';
                                for ($i = 0; $i < count($helpers); $i++) {
                                    $tData['text'] .= ($i+1) . '. @' . $helpers[$i] . "\n" . 'حذف: ' . '/deleteadmin' . $helpers[$i] . "\n";
                                }
                                $this->conversation->notes['state'] = 4;
                                $this->conversation->update();
                            } else {
                                $tData['text'] = 'کانال ' . '@' . $this->conversation->notes['channelName'] . ' ادمینی ندارد.';
                            }
                            Request::sendMessage($tData);
                            break;
                        case 'افزودن ادمین':
                            $this->conversation->notes['state'] = ++$state;
                            $this->conversation->update();
                            $shouldContinue = true;
                            break;
                    }
                    $text = '';
                    if (!$shouldContinue) break;
                case 3:
                    if (empty($text) || $message->getForwardFrom() == null ||
                        $message->getForwardFrom()->getUsername() == null || empty($message->getForwardFrom()
                            ->getUsername())) {
                        $data = [];
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        if ($message->getForwardFrom() == null) {
                            $data['text'] = 'پیامی از ادمین مورد نظر فوروارد کنید:';
                        } else {
                            $data['text'] = 'ادمین موردنظر باید username داشته باشد. لطفا پیامی دیگر فوروارد کنید.';
                        }
                        $keyboard = [['بیخیال', 'بازگشت']];
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
                    $username = $message->getForwardFrom()->getUsername();
                    $tData = [];
                    $tData['chat_id'] = $chat_id;
                    if (\AdminDatabase::addHelperToChannel($channel, $user->getUsername(), $username)) {
                        $tData['text'] = 'کاربر ' . '@' . $username . ' با موفقیت به کانال' . ' @' . $channel . ' اضافه شد :)';
                    } else {
                        $tData['text'] = 'خطا در اضافه کردن کاربر ' . '@' . $username . 'به کانال' . ' @' . $channel . ' !';
                    }
                    Request::sendMessage($tData);
                    $this->conversation->stop();
                    $this->telegram->executeCommand('cancel');
                    break;
                case 4:
                    // We assume $text is the name of the admin to be deleted.
                    $channel = $this->conversation->notes['channelName'];
                    $helpers = \AdminDatabase::getHelpersFromChannel($channel, $user->getUsername());
                    if (empty($text) || !in_array($text, $helpers)) {
                        $data = [];
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ادمین مورد نظر را انتخاب کنید:';
                        Request::sendMessage($data);
                        $tData['chat_id'] = $chat_id;
                        $tData['text'] = '';
                        for ($i = 0; $i < count($helpers); $i++) {
                            $tData['text'] .= $i . '. @' . $helpers[$i] . "\n" . 'حذف: ' . '/deleteadmin' . $helpers[$i] . "\n";
                        }
                        Request::sendMessage($tData);
                        break;
                    }
                    $this->conversation->notes['helper'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $this->conversation->update();
                    $text = '';
                case 5:
                    if (empty($text) || ($text != 'بله' && $text != 'خیر')) {
                        $data = [];
                        $data['reply_to_message_id'] = $message_id;
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'آیا از حذف ' . '@' . $this->conversation->notes['helper'] . ' مطمئنید؟';
                        $keyboard = [['بله', 'خیر'], ['بیخیال']];
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
                    if ($text == 'بله') {
                        $channel = $this->conversation->notes['channelName'];
                        $helper = $this->conversation->notes['helper'];
                        if (\AdminDatabase::removeHelperFromChannel($channel, $user->getUsername(), $helper)) {
                            $data = [];
                            $data['chat_id'] = $chat_id;
                            $data['text'] = 'با موفقیت حذف شد :)';
                            Request::sendMessage($data);
                            $this->conversation->stop();
                            $this->telegram->executeCommand('cancel');
                            return true;
                        } else {
                            $data = [];
                            $data['chat_id'] = $chat_id;
                            $data['text'] = 'خطا در حذف ' . '@' . $helper . ' :(';
                            Request::sendMessage($data);
                            $this->conversation->stop();
                            $this->telegram->executeCommand('manageadmins');
                            return true;
                        }
                    } else {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'عملیات متوقف شد.';
                        Request::sendMessage($data);
                        $this->conversation->stop();
                        $this->telegram->executeCommand('manageadmins');
                        return true;
                    }
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
                        $helpers .= ','.$helper;
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
                
                $data = Database::getDatabase()->select('admin', '*', [
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

        public static function addChannel($channel, $admin) {
            return Database::getDatabase()->insert('admin', [
                'Channel' => $channel,
                'Admin' => $admin,
                'Type' => 0
            ]);
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