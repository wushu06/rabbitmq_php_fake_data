<?php
namespace RabbitMQ;

/**
 * Class Consumer
 * @package RabbitMQ
 */
class Consumer extends RabbitAbstract
{
    /**
     * @var
     */
    public $message;

    /**
     * @var \RabbitMQ\Database
     */
    private $db;

    /**
     * Consumer constructor.
     */
    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * @param $args
     */
    public function consume($args)
    {
        $consumerTag = 'consumer';
        $args->channel->basic_consume($args->queue, $consumerTag, false, false, false, false, [$this, 'processMessage']);

        register_shutdown_function([$this,'close'], $args->channel, $args->connection);

        while ($args->channel->is_consuming()) {
            $args->channel->wait();
        }
    }

    /**
     * @param $message
     */
    public function processMessage($message)
    {
        $this->execute($message);
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        // Send a message with the string "quit" to cancel the consumer.
        if ($message->body === 'quit') {
            $message->getChannel()->basic_cancel($message->getConsumerTag());
        }
    }

    /**
     * @param $message
     */
    public function execute($message)
    {
        $body = json_decode($message->body);
        $email = $body->email;
        $name = $body->name;
        $table = $_ENV['DB_TABLE'];
        //file_put_contents((__DIR__).'/data/'.$email.'.json', $message->body);
        $this->db->query('INSERT INTO '.$table.' (`email`, `is_subscribed`, `name`, `updated_at`) 
                        VALUES (? ,?, ?, ?)', $email, 1, $name, date("Y-m-d h:i:s", time()));
    }
}
