<?php
namespace RabbitMQ;

use Faker\Factory;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Publisher
 * @package RabbitMQ
 */
class Publisher
{
    /**
     * @param $args
     */
    public function publish($args)
    {
        $faker = Factory::create();
        $limit = $_ENV['LIMIT'];
        $i = 0;
        while ($limit >= $i ){
            $messageBody = json_encode([
                'email' => $faker->email,
                'name' => $faker->name,
                'subscribed' => true
            ]);
            $message = new AMQPMessage(
                $messageBody,
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ]
            );
            $args->channel->basic_publish($message, $args->exchange);
            $i +=1;
        }

    }
}
