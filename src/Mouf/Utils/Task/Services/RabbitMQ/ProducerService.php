<?php

namespace Mouf\Utils\Task\Services\RabbitMQ;

use Mouf\Utils\Task\Task;
use PhpAmqpLib\Message\AMQPMessage;

class ProducerService
{
    /**
     * RabbitMQ connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * RabbitMQ COnsumer Service.
     *
     * @var ConsumerService
     */
    private $consumerService;

    /**
     * ProducerService constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set Consumer Service.
     *
     * @param ConsumerService $consumerService
     */
    public function setConsumerService(ConsumerService $consumerService)
    {
        $this->consumerService = $consumerService;
    }

    public function send(Task $task)
    {
        if ($this->connection->getEnable()) {
            $string = $task->serialize();
            $msg = new AMQPMessage($string, array('delivery_mode' => 2, 'priority' => $task->getPriority()));
            $this->connection->getChannel()->basic_publish($msg, '', $this->connection->getMainQueue());
        } else {
            $this->consumerService->getCommandBus()->handle($task);
        }
    }
}
