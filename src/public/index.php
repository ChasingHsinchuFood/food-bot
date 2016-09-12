<?php
    require "../../vendor/autoload.php";
    //require "../../helper/processmessage.php";

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    $app = new \Slim\App;

    $app->get('/life-bot/term', function (Request $request, Response $response) {
        $response->getBody()->write("<h2>歡迎使用 life-bot 請遵守Facebook Messenger 條款</h2>");

        return $response;
    });

    $app->run();

?>
