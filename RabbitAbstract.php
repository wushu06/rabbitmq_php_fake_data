<?php
namespace RabbitMQ;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class RabbitAbstract
 * @package RabbitMQ
 */
abstract class RabbitAbstract
{

    /**
     * @var AMQPStreamConnection
     */
    public $connection;

    /**
     * @var
     */
    public $channel;
    /**
     * @var string
     */
    public $queue;
    /**
     * @var \PhpAmqpLib\Connection\AbstractConnection
     */
    public $exchange;

    /**
     * @return $this
     */
    protected function connect()
    {
        $this->exchange = 'subscribers';
        $this->queue = 'custom_messaging';
        $this->connection = new AMQPStreamConnection(
            $_ENV['RABBIT_HOST'],
            $_ENV['RABBIT_PORT'],
            $_ENV['RABBIT_USERNAME'],
            $_ENV['RABBIT_PASSWORD'],
            $_ENV['RABBIT_VHOST']
        );
        $this->channel = $this->connection->channel();
        return $this;
    }

    /**
     * @return $this
     */
    protected function queue()
    {
        $this->channel->queue_declare($this->queue, false, true, false, false);
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function close()
    {
        $this->channel->close();
        $this->connection->close();
        return false;
    }
}
