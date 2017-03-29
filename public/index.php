<?php
    require "../vendor/autoload.php";

    spl_autoload_register(function ($classname) {
        require ("../helper/" . $classname . ".php");
    });

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    use \peter\components\BotBuilder;

    use \GuzzleHttp\Client;

    $container = new \Slim\Container();

    $container['notFoundHandler'] = function ($container) {
        //https://i.imgur.com/j7wPeJs.png
        return function ($request, $response) use ($container) {
            $contents = file_get_contents("../templates/404.html");
            return $container['response']
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->write($contents);
        };
    };

    // Register Twig View helper

    $container['view'] = function ($c) {
        $view = new \Slim\Views\Twig('../templates', [
            'cache' => './'
        ]);

        // Instantiate and add Slim specific extension

        $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
        $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

        return $view;
    };

    $container['logger'] = function($c) {
        $logger = new \Monolog\Logger('my_logger');
        $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
        $logger->pushHandler($file_handler);
        return $logger;
    };

    $app = new \Slim\App($container);

    $tokens = json_decode(file_get_contents("../token/token.json"), true);
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

        $process = new ProcessMessage($message, $sender);
        $json = $process->processText();

        $body = array();
        $body["recipient"]["id"] = $sender;
        $body["sender_action"] = "typing_on";

        $builder->statusBubble($body);

        $res = $builder->sendMsg("texts", $data, $json);

    });

    $app->get('/life-bot/add/menus', function(Request $request, Response $response) {
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
            return $response;
        }
        else {
            $newResponse = $response->withAddedHeader('Content-type', 'application/json');
            $newResponse->getBody()->write(json_encode($data));
            return $newResponse;
        }
    });

    $app->get('/life-bot/city_lists', function(Request $request, Response $response) {
        $json = file_get_contents("../json/cities.json");
        $json = json_decode($json, true);
        $cities = $json["cities"];
        $message = "";

        $index = 1;
        foreach($cities as $key => $value) {
            if($index % 2 === 1)
                $message .= "<tr class='pure-table-odd'>";
            else
                $message .= "<tr>";

            foreach($value as $city => $en) {
                $message .= "<td>" . $index . "</td>";
                $message .= "<td>" . $city . "</td>";
                $message .= "<td>" . $en . "</td>";
                $index += 1;
            }
            $message .= "</tr>";
        }

        $this->logger->addInfo("City lists");
        $cities = $message;
        $response = $this->view->render($response, "cities.phtml", ["cities" => $cities]);

        return $response;

    });

    $app->get('/life-bot/need_help', function(Request $request, Response $response) {
        $help = "使用方法";
        $json = file_get_contents("../json/usage.json");
        $json = json_decode($json, true);
        $usage = $json["usage"];
        $message = "";

        $index = 1;
        $len = count($usage);

        for($i=0;$i<$len;$i++) {
            if($index % 2 === 1) {
                $message .= "<tr class='pure-table-odd'>";
            }
            else {
                $message .= "<tr>";
            }

            $message .= "<td>" . $usage[$i]["cmd"] . "</td>";
            $message .= "<td>" . $usage[$i]["result"] . "</td>";
            $message .= "<td>" . $usage[$i]["ps"] . "</td>";

            $message .= "</tr>";

            $index += 1;
        }

        $this->logger->addInfo('Need Help');
        $response = $this->view->render($response, "usage.phtml", ["help" => $message]);
    });

    //query dynamic bus estimate time
    $app->get('/life-bot/bus/city/{city_name}/route/{route_name}', function(Request $request, Response $response, $args) {
        $cityName = $args["city_name"];
        $routeName = $args["route_name"];

        $messages = array("公車", $cityName, $routeName);
        $busTime = new BusTime($messages);
        $message = $busTime->getEstTime();

        //$this->logger->addInfo("Dynamic City Bus");
        //$response = $this->view->render($response, "buses.phtml", ["buses" => $message]);
        $newResponse = $response->withAddedHeader('Content-type', 'application/json');
        $newResponse->getBody()->write(json_encode($message));

        return $newResponse;
    });

    $app->get('/life-bot/bus/inter-city/route/{route_name}', function(Request $request, Response $response, $args) {
        $routeName = $args["route_name"];

        $messages = array("客運", $routeName);
        $busTime = new BusTime($messages);
        $message = $busTime->getEstTime();

        $this->logger->addInfo("Inter City Bus");
        $response = $this->view->render($response, "buses.phtml", ["buses" => $message]);

        return $response;
    });

    $app->run();

?>
