<?php
    require "../../vendor/autoload.php";
    //require "../../helper/processmessage.php";

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    $app = new \Slim\App;

    $app -> get('/life-bot', function(Request $request, Response $response) {
        $response->getBody()->write("<h2>歡迎來到 life-bot ，請遵守 Facebook Messenger 使用條款</h2>");
    });

    $app->run();

?>
