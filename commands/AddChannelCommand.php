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
                    case 0: {
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
                        } else {
                            if (\AdminDatabase::addChannel($text, $user->getUsername())) {
                                $data['text'] = 'کانال شما اضافه شد. برای استفاده از ربات باید این ربات را به صورت ادمین به کانال خود اضافه کنید.'
                                    .' در غیر این صورت ربات برای شما کار نخواهد کرد.';
                                $result = Request::sendMessage($data);
                                $this->telegram->executeCommand('cancel');
                            } else {
                                $data['text'] = 'خطا در اضافه کردن کانال! لطفا مجددا تلاش کنید.آیدی کانال (بدون @) را وارد کنید:'."\n".$text;
                                $result = Request::sendMessage($data);
                            }
                        }
                        break;
                    }
                }
            }

            return $result;

        }



    }
}
