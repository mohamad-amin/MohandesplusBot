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
    use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;

    class AddChannelCommand extends UserCommand {

        protected $name = 'addchannel';                      //your command's name
        protected $description = 'اضافه کردن کانال';          //Your command description
        protected $usage = '/addchannel';                    // Usage of your command
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

            $message = $this->getMessage();              // get Message info
            $chat = $message->getChat();
            $user = $message->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $text = $message->getText(true);
            $message_id = $message->getMessageId();      //Get message Id

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
            $state = 0;
            $data = [];
            $data['chat_id'] = $chat_id;
            if ($text == '➕ افزودن کانال') {
                $text = '';
            }

            if ($user->getUsername() == null || empty($user->getUsername())) {
                $data['text'] = 'برای استفاده از این ربات باید Username داشته باشید. از قسمت تنظیمات تلگرام یک Username برای خود بسازید.';
                $result = Request::sendMessage($data);
            } else {
                switch ($state) {
                    case 0:
                        if (empty($text)) {
                            $data['text'] = '❗️حواستون باشه که روبات (...@) رو به‌صورت ادمین (Admin) به کانال اضافه کنید.

❗️این روبات، مخصوص کانال‌های عمومی (Public Channels) است و در کانال‌های خصوصی (Private Channels) کار نمی‌کند.
➖➖➖➖➖➖➖ ‏

 👈 برای ادامه، آیدی (بدون @) کانال جدید خود را بفرستید.';
                            $keyboard = [['❌ بی‌خیال']];
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
                        if (\AdminDatabase::channelExists($text)) {
                            $data['text'] = 'این کانال قبلا اضافه شده است. اگر این کانال شماست از قسمت ارتباط با ما به ما گزارش دهید.';
                            $result = Request::sendMessage($data);
                            $this->telegram->executeCommand('cancel');
                            break;
                        }
                        $this->conversation->notes['state'] = ++$state;
                        $this->conversation->notes['channel'] = $text;
                        $text = '';
                        $this->conversation->update();
                    case 1:
                        if ($text != 'انجام شد') {
                            $data['text'] = 'برای استفاده از ربات باید این ربات را به صورت ادمین به کانال خود اضافه کنید.'
                                .' در غیر این صورت ربات برای شما کار نخواهد کرد.';
                            $data['text'] .= "\n".'حال ربات را به صورت ادمین اضافه کنید و سپس دکمه‌ی انجام شد را بزنید.';
                            $data['text'] .= "\n".'سپس ربات برای اطمینان از اینکه شما صاحب کانال هستید یک پست با متن (تست ربات) روی کانال قرار می‌دهد که شما می‌توانید به سرعت آن را پاک کنید.';
                            $keyboard = [
                                ['انجام شد'],
                                ['❌ بی‌خیال']
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
                        $channel = $this->conversation->notes['channel'];
                        $tData = [];
                        $tData['chat_id'] = '@'.$channel;
                        $tData['text'] = 'تست ربات';
                        $result = Request::sendMessage($tData);
                        if ($result) {
                            if (\AdminDatabase::addChannel($channel, $user->getUsername())) {
                                $data['text'] = 'کانال شما با موفقیت اضافه شد :)'."\n".$result;
                                $result = Request::sendMessage($data);
                                $this->conversation->cancel();
                                $this->telegram->executeCommand('manageadmins');
                            } else {
                                $data['text'] = 'خطا در اضافه کردن کانال! لطفا مجددا تلاش کنید.';
                                $result = Request::sendMessage($data);
                                $this->conversation->cancel();
                                $this->telegram->executeCommand('addchannel');
                                break;
                            }
                        } else {
                            $data['text'] = 'ربات هنوز به کانال اضافه نشده!';
                            $keyboard = [
                                ['انجام شد'],
                                ['❌ بی‌خیال']
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
                        break;
                }
            }

            return $result;

        }



    }
}
