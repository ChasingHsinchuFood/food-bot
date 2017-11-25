<?php
    use \GuzzleHttp\Client;

    class ProcessMessage {
        private $confidence = null;

        private $entities = [
            'greeting', 'datetime',
            'location', 'local_search_query',
        ];

        public function __construct($message, $sender) {
            $this->message =  strtolower($message);
            $this->sender = $sender;
        }

        public function processText() {
            $json = array();
            $json["recipient"]["id"] = $this->sender;

            $needle = "hello";

            if(mb_stristr($this->message, $needle) != false) {
                $json["message"]["attachment"]["type"] = "image";
                $links = ['https://i.giphy.com/media/26u8ymPsDsnu1YWg8/giphy.webp',
                    'https://i.giphy.com/media/26u8ymPsDsnu1YWg8/giphy.webp'];
                $json["message"]["attachment"]["payload"]["url"] = $links[0];
            }
            else if(mb_strlen($this->message) <= 5) {
                $json["message"]["text"] = "You have to input more texts so that I can understand what do you mean \n 你必須打更多的字好讓我看的懂！";
            }

            return $json;
        }

        public function processGuessText($entitity) {
            $json = array();
            $json["recipient"]["id"] = $this->sender;

            if($entitity == 'greeting') {
                $json["message"]["text"] = 'Hi 你好阿！';
                return $json;
            }
        }

        public function processPostBack() {
            $message = "";

            if($this->message === "what_do_you_want_to_eat") {
                $message = "使用說明在下列網址：\n the command lists is about the following url:\n";
                $message .= "https://hsinchu.life/eat_map";
            }
            else if($this->message === "give_me_command_lists") {
                $message = "使用說明在下列網址：\n";
                $message .= "https://hsinchu.life/need_help";
            }
            else {
                $message = "invalid post back.";
            }

            return $message;
        }
    }
?>
