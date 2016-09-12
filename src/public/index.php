<?php
    require "../../vendor/autoload.php";
    
    //require "helper/processmessage.php";
        
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    $app = new \Slim\App;
    $app->get('/life-bot/src/public/hello/{name}', function (Request $request, Response $response) {
        $name = $request->getAttribute('name');
        $response->getBody()->write("Hello, $name");

        return $response;
    });

    $app->run();

?>
