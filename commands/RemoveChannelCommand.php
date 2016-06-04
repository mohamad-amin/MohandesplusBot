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

    class RemoveChannelCommand extends UserCommand {

        protected $name = 'removechannel';                      //your command's name
        protected $description = 'حذف کانال';          //Your command description
        protected $usage = '/removechannel';                    // Usage of your command
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
            $text = $message->getText();

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
            $state = 0;
            $data = [];
            $data['chat_id'] = $chat_id;
            if ($text == '➖ حذف کانال') {
                $text = '';
            }

            if ($user->getUsername() == null || empty($user->getUsername())) {
                $data['text'] = 'برای استفاده از این ربات باید Username داشته باشید. از قسمت تنظیمات تلگرام یک Username برای خود بسازید.';
                $result = Request::sendMessage($data);
            } else {
                switch ($state) {
                    case 0: {
                        $channels = \AdminDatabase::getAdminsChannels($user->getUsername());
                        if (empty($text) || !in_array($text, $channels)) {
                            if (count($channels) < 1) {

                            } else {
                                $data['text'] = 'کانالی را که میخواهید حذف کنید انتخاب کنید:';
                                $i = 0;
                                foreach ($channels as $key) {
                                    $j = (int) floor($i/3);
                                    $keyboard[$j][$i % 3] = $key;
                                    $i++;
                                }
                                $keyboard[] = ['بیخیال'];
                                $data['reply_markup'] = new ReplyKeyboardMarkup(
                                    [
                                        'keyboard' => $keyboard,
                                        'resize_keyboard' => true,
                                        'one_time_keyboard' => true,
                                        'selective' => true
                                    ]
                                );
                                $result = Request::sendMessage($data);
                            }
                            break;
                        }
                        if (\AdminDatabase::removeChannel($text, $user->getUsername())) {
                            $data['text'] = 'با موفقیت حذف شد :)';
                            $result = Request::sendMessage($data);
                            $this->telegram->executeCommand('cancel');
                        } else {
                            $data['text'] = 'خطا در حذف کانال :( لطفا مجددا تلاش کنید.'."\n".$text;
                            Request::sendMessage($data);
                        }
                        break;
                    }
                }
            }

            return $result;

        }



    }
}
