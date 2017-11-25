<?php
    use \GuzzleHttp\Client;
    use Dotenv\Dotenv;

    class ProcessMessage {
        private $confidence = null;

        private $entities = [
            'greeting', 'wit/datetime',
            'wit/location', 'wit/local_search_query',
        ];

        public function __construct($message, $sender) {
            $this->message =  strtolower($message);
            $this->sender = $sender;
        }

        public function handleEntity($data, $name) {
            return isset($data['nlp']) && isset($data['nlp']['entities'])
                && isset($data['nlp']['entities'][$name])
                && isset($data['nlp']['entities'][$name][0]);
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

        public function processPostBack() {
            $message = "";

            $dotenv = new Dotenv(__DIR__);
            $dotenv->load();

            if($this->message === "what_do_you_want_to_eat") {
                $config = array(
                    'db_type' => getenv('driver'),
                    'db_host' => getenv('host'),
                    'db_name' => getenv('database'),
                    'db_username' => getenv('username'),
                    'db_password' => getenv('password'),
                );

                $db = new Database($config);
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
