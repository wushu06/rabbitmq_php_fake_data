<?php

namespace RabbitMQ;

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
/**
 * Class Index
 * @package RabbitMQ
 */
class Index extends RabbitAbstract
{
    /**
     * Index constructor.
     */
    public function __construct()
    {
        Dotenv::createImmutable(__DIR__)->load();
    }

    /**
     * @throws \Exception
     */
    public function publish()
    {
        $publisher = new Publisher();
        $args = $this->connect()->queue();
        $publisher->publish($args);
        $this->close();
    }

    /**
     * @return void
     */
    public function consume()
    {
        $consumer = new Consumer();
        $args = $this->connect()->queue();
        $consumer->consume($args);
    }
}
$index = new Index();

echo 'Publishing..' . PHP_EOL;

try {
    $index->publish();
} catch (\Exception $e) {
    echo sprintf('Something went wrong: %s', $e->getMessage());
}

echo 'Consuming...' . PHP_EOL;

try {
    $index->consume();
} catch (\Exception $e) {
    sprintf('Something went wrong: %s', $e->getMessage());
}
