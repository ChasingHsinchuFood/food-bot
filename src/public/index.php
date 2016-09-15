<?php
    require "../../vendor/autoload.php";

    require "../../helper/processmessage.php";

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    use \peter\components\BotBuilder;

    use \GuzzleHttp\Client;

    $app = new \Slim\App;

    $tokens = json_decode(file_get_contents("../../token/token.json"), true);
    $builder = new BotBuilder($tokens["token"], $tokens["page_token"]);

    $app->get('/life-bot/term', function (Request $request, Response $response) {
        $response->getBody()->write("<h2>歡迎使用 life-bot 請遵守Facebook Messenger 條款</h2>");

        return $response;
    });

    $app->get('/life-bot/webhook', function(Request $request, Response $response) {
        global $builder;                                                                                                                                   
        $result = $builder->verify("msg_token");   
        $response->getBody()->write($result);
    });

    $app->post('/life-bot/webhook', function(Request $request, Response $response) {
        global $builder;                                                                                                                                   
        $data = $builder->receiveMsg();                                                                                                                  
                                                                                                                          
        //get the graph sender id
        if(isset($data['entry'][0]['messaging'][0]['sender']['id']))                                                                                                                     
            $sender = $data['entry'][0]['messaging'][0]['sender']['id'];                                                                                       
                                                                                                                                                               
        //get the returned message                                                                                                                    
        if(isset($data['entry'][0]['messaging'][0]['message']['text']))                                                                                  
            $message = $data['entry'][0]['messaging'][0]['message']['text'];                                                                     
        else if(isset($data['entry'][0]['messaging'][0]['message']['attachments'])) {                                                                     
            $message = $data['entry'][0]['messaging'][0]['message']['attachments'];
        }
        else if(isset($data['entry'][0]['messaging'][0]['postback'])) {
            $message = $data['entry'][0]['messaging'][0]['postback']['payload'];
        }
        else {                                                                                                                                          
            $message = "not-find.";
            $response->getBody()->write($message);
            return $response;
        }                                                                                                                  

        file_put_contents("./msg.txt", $message);

        $process = new ProcessMessage($message, $sender);                                                       
        $json = $process->processText();                                                                                                                 
                                                                                                                                                               
        $body = array();                                                                                                                                
        $body["recipient"]["id"] = $sender;                                                                                                                
        $body["sender_action"] = "typing_on";

        $builder->statusBubble($body);                                                                                           
        $builder->sendMsg("texts", $data, $json);

    });

    $app -> get('/life-bot/add/menus', function(Request $request, Response $response) {
        global $builder;
        $menus = array(                                                                                                             
            array(                                                                                                                                        
                "type" => "postback",                                                                                                                     
                "title" => "幫助",                                                                                                                  
                "payload" => "need_your_help"                                                                            
            ),                                                                                                                                            
            array(                                                                                                                                        
                "type" => "postback",                                                                                                                     
                "title" => "城市對照表,公車動態用",                                                                                                     
                "payload" => "city_lists"                                                                                        
            ),
            array(
                "type" => "postback",
                "title" => "給我一隻狗或貓",
                "payload" => "give_me_dog_cat"
            ),
            array(
                "type" => "postback",
                "title" => "給我常用指令清單",
                "payload" => "give_me_command_lists"
            )
        );

        $data = $builder->addMenu($menus);

        if($data === true) {
            $response->getBody()->write("add-menu-success");
        }
        else {
            $response->withAddedHeader('Content-type', 'application/json');      
            $response->getBody()->write(json_encode($data));
        }

        return $response;
    });

    $app->run();

?>
