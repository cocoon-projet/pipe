# Cocoon-projet/pipe

## A Propos

* cocoon-projet/pipe est un PSR-15 server request handler..
* cocoon-projet/pipe est conforme au **standard psr-15**

## Installation

via composer
```
composer require cocoon-projet/pipe
```
## Pré-requis

Php version 7.1.0 ou plus

## Utilisation

```php
require 'vendor/autoload.php';

use Cocoon\Pipe\Pipe;
use Laminas\Diactoros\ServerRequestFactory;
use function Http\Response\send;

$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$pipe = new Pipe();
$pipe->add(\Middlewares\Whoops::class);
$pipe->add(\App\Middlewares\Router::class);
// vous pouvez aussi instancier le middleware en amont
// $pipe->add(new MyMiddelware());
// ou ajouter les middlewares avec un array
// $pipe->add([\Middlewares\Whoops::class,
//            \App\Middlewares\Router::class]
//    );
$response = $pipe->handle($request);

send($response);

```