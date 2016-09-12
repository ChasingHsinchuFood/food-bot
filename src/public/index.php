<?php
        require "vendor/autoload.php";
        require "helper/processmessage.php";

        use peter\components\BotBuilder;
        use GuzzleHttp\Client;

        $tokens = json_decode(file_get_contents(__DIR__ . "/token/token.json"), true);
        $builder = new BotBuilder($tokens["token"], $tokens["page_token"]);

        Flight::route('GET /', function() {
            header("Content-Type: text/plain; charset=utf8");
            echo '歡迎來到 unit-testing-bot 僅供測試使用，請遵守 Facebook Messenger 的使用條款';
        });

        Flight::route('GET /webhook', function() {
            global $builder;
            echo $builder -> verify("msg_token");
        });

        Flight::route('POST /webhook', function() {
            global $builder;
            $data = $builder -> receiveMsg();
            
            //get the graph sender id
            $sender = $data['entry'][0]['messaging'][0]['sender']['id'];

            //get the returned message
            if(isset($data['entry'][0]['messaging'][0]['message']['text']))
                $message = $data['entry'][0]['messaging'][0]['message']['text'];
            else if(isset($data['entry'][0]['messaging'][0]['message']['attachments']))
                $message = $data['entry'][0]['messaging'][0]['message']['attachments'];
            else
                $message = "not-find.";

            $process = new processMessage($message, $sender);
            $json = $process -> processText();

            $body = array();
            $body["recipient"]["id"] = $sender;
            $body["sender_action"] = "typing_on";

            $builder -> statusBubble($body);
            $builder -> sendMsg("texts", $data, $json);
        });

        Flight::route('GET /greeting', function() {
            global $builder;
            $greetingTxt = "Welcome to my bot!";
            $res = $builder -> addGreeting($greetingTxt);
            if($res === true) {
                echo "successful setting";
            }
            else {
                var_dump($res);
            }
        });

        Flight::route('GET /delete/greeting', function() {
            global $builder;
            $res = $builder -> delGreeting();
            if($res === true) {
                echo "successful remove setting";
            }
            else {
                var_dump($res);
            }
        });

        Flight::route('GET /menu', function() {
             global $builder;
             $menus = array(
                 array(
                     "type" => "postback",
                     "title" => "Help(幫助)",
                     "payload" => "DEVELOPER_DEFINED_PAYLOAD_FOR_HELP"
                 ),
                 array(
                     "type" => "postback",
                     "title" => "About(關於)",
                     "payload" => "DEVELOPER_DEFINED_PAYLOAD_FOR_ABOUT"
                 )
             );
             $res = $builder -> addMenu($menus);
             if($res === true) {
                 echo "successful setting";
             }
             else {
                 var_dump($res);
             }
        });

        Flight::route('GET /delete/menu', function() {
            global $builder;
            $res = $builder -> delMenu();
            if($res === true) {
                echo "successful remove setting";
            }
            else {
                var_dump($res);
            }
        });

        Flight::map('notFound', function() {
            // Display custom 404 page
            header("Content-Type: text/plain");
            echo "Not Found.";
        });

        Flight::start();


?>
