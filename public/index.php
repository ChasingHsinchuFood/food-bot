<?php

    require_once "../vendor/autoload.php";

    date_default_timezone_set('Asia/Taipei');

    spl_autoload_register(function ($classname) {
        require_once ("../helper/" . $classname . ".php");
    });

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    use \peter\components\BotBuilder;

    use \GuzzleHttp\Client;

    $container = new \Slim\Container();

    $settings = $container->get('settings');

    // The Slim Framework settings

    $settings->replace([
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'debug' => true,
    ]);

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
            'cache' => false,
        ]);
        // Instantiate and add Slim specific extension

        $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
        $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

        return $view;
    };

    // remember the write permision for the app.log

    $container['logger'] = function($c) {
        $logger = new \Monolog\Logger('my_logger');
        $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
        $logger->pushHandler($file_handler);
        return $logger;
    };

    $app = new \Slim\App($container);

    $tokens = json_decode(file_get_contents("../token/token.json"), true);
    $builder = new BotBuilder($tokens["token"], $tokens["page_token"]);

    $app->get('/', function(Request $request, Response $response) {
        $response->getBody()->write("<h1>歡迎來到追竹美食</h1><h2>目前版本為：v0.0.1</h2>");

        return $response;
    });

    $app->get('/term', function (Request $request, Response $response) {
        $response->getBody()->write("<h2>歡迎使用 food-bot(追竹美食) 請遵守Facebook Messenger 條款</h2>");

        return $response;
    });

    $app->get('/webhook', function(Request $request, Response $response) {
        global $builder;
        $result = $builder->verify("msg_token");
        $response->getBody()->write($result);
    });

    $app->post('/webhook', function(Request $request, Response $response) {
        global $builder;
        $data = $builder->receiveMsg();

        //get the graph sender id
        if(isset($data['entry'][0]['messaging'][0]['sender']['id'])) {
            $sender = $data['entry'][0]['messaging'][0]['sender']['id'];
        }

        //get the returned message
        if(isset($data['entry'][0]['messaging'][0]['message']['text'])) {
            $message = $data['entry'][0]['messaging'][0]['message']['text'];
        } else if(isset($data['entry'][0]['messaging'][0]['message']['attachments'])) {
            $message = $data['entry'][0]['messaging'][0]['message']['attachments'];
        } else if(isset($data['entry'][0]['messaging'][0]['postback'])) {
            $message = $data['entry'][0]['messaging'][0]['postback']['payload'];
        } else {
            $message = "not-find.";
            $response->getBody()->write($message);

            return $response;
        }

        //process the requested message
        $process = new ProcessMessage($message, $sender);

        if(isset($data['nlp']['entities']['greetings'])) {
            if($data['nlp']['entities']['greetings']['confidence'] >= 0.9) {
                $json["message"]["text"] = 'Hello!';
            } else {
                $json["message"]["text"] = 'default logic.';
            }
        } else {
            $json = $process->processText();
        }

        $body = array();
        $body["recipient"]["id"] = $sender;
        $body["sender_action"] = "typing_on";

        $builder->statusBubble($body);

        $res = $builder->sendMsg("texts", $data, $json);
    });

    $app->get('/add/menus', function(Request $request, Response $response) {
        global $builder;
        $menus = array(
            array(
                "type" => "postback",
                "title" => "今天吃什麼？",
                "payload" => "what_do_you_want_to_eat"
            ),
            array(
                "type" => "postback",
                "title" => "給我常用指令清單",
                "payload" => "give_me_command_lists"
            ),
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

    $app->get('/need_help', function(Request $request, Response $response) {
        $help = "使用方法";
        $json = file_get_contents("../json/usage.json");
        $json = json_decode($json, true);
        $usage = $json["usage"];
        $message = "";

        $index = 1;
        $len = count($usage);

        for($i=0;$i<$len;$i++) {
            if($index % 2 === 1) {
                $message .= "<tr>";
            }
            else {
                $message .= "<tr>";
            }

            $message .= "<td>" . $usage[$i]["cmd"] . "</td>";
            $message .= "<td>" . $usage[$i]["result"] . "</td>";

            $message .= "</tr>";

            $index += 1;
        }

        $this->logger->addInfo('Need Help');
        $response = $this->view->render($response, "usage.phtml", ["help" => $message]);
    });

    $app->run();
