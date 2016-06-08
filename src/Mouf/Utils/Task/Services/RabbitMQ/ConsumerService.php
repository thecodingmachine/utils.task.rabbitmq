<?php

namespace Mouf\Utils\Task\Services\RabbitMQ;

use Mouf\Utils\Task\Task;
use League\Tactician\CommandBus;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerService
{
    /**
     * RabbitMQ connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Command Bus.
     *
     * @var CommandBus
     */
    private $commandBus;

    /**
     * ConsumerService constructor.
     *
     * @param Connection $connection
     * @param CommandBus $commandBus
     */
    public function __construct(Connection $connection, CommandBus $commandBus)
    {
        $this->connection = $connection;
        $this->commandBus = $commandBus;
    }

    /**
     *
     */
    public function loop()
    {
        $callback = function ($msg) {
            try {
                $task = Task::unserialize($msg->body);

                //Handle Task
                $this->commandBus->handle($task);

                //Ack message
                $this->ack($msg);

                // Log Success: TODO: add logger in future
                $ret = '['.date('Y-m-d H:i:s').'] Queue '.$this->connection->getMainQueue().' : '.$msg->body.PHP_EOL;
                file_put_contents(LOG_PATH.'task.log', $ret, FILE_APPEND);
            } catch (\Exception $e) {
                //No Ack message
                $this->noAck($msg);

                // Log Error
                $ret = '['.date('Y-m-d H:i:s').'] ERREUR Queue '.$this->connection->getMainQueue().' : '.$msg->body.PHP_EOL;
                file_put_contents(LOG_PATH.'task.log', $ret, FILE_APPEND);
            }
        };

        $this->connection->getChannel()->basic_consume($this->connection->getMainQueue(), '', false, false, false, false, $callback);

        while (count($this->connection->getChannel()->callbacks)) {
            $this->connection->getChannel()->wait();
        }
    }

    /**
     * @param OutputInterface $output
     */
    public function consumeErrors(OutputInterface $output)
    {
        $nb = $this->connection->getNumberOfMessages($this->connection->getErrorQueue());
        $try = 0;
        $callback = function ($msg) use ($output) {
            try {
                $task = Task::unserialize($msg->body);

                //Handle Task
                $this->commandBus->handle($task);

                //Ack message
                $this->ack($msg);

                //Log Success
                $output->writeln('['.date('Y-m-d H:i:s').'] Queue '.$this->connection->getErrorQueue().' : '.$msg->body);
            } catch (\Exception $e) {
                //Ack message (we have to create a new message because we will update nbTries)
                $this->ack($msg);

                $task = Task::unserialize($msg->body);
                if ($task->getNumberOfTries() >= $this->connection->getMaxTries()) {
                    //Don't try anymore
                    //Put message body in a file for later try
                    file_put_contents(LOG_PATH.'task_error.log', $msg->body.PHP_EOL, FILE_APPEND);
                } else {
                    //Queue a new message with try++
                    $task->addTry();
                    $string = $task->serialize();
                    $msg = new AMQPMessage($string, array('delivery_mode' => 2, 'priority' => $task->getPriority()));
                    $this->connection->getChannel()->basic_publish($msg, '', $this->connection->getErrorQueue());
                }

                //Log Error
                $output->writeln('['.date('Y-m-d H:i:s').'] ERREUR Queue '.$this->connection->getErrorQueue().' : '.$msg->body);
            }
        };

        $this->connection->getChannel()->basic_consume($this->connection->getErrorQueue(), '', false, false, false, false, $callback);

        while (count($this->connection->getChannel()->callbacks)) {
            if ($try < $nb) {
                $this->connection->getChannel()->wait();
                $try = $try + 1;
            } else {
                return $nb;
            }
        }
    }

    /**
     * @param $msg
     */
    private function ack($msg)
    {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * @param $msg
     * @param bool|false $requeue
     */
    private function noAck($msg, $requeue = false)
    {
        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], true, $requeue);
    }

    /**
     * @return CommandBus
     */
    public function getCommandBus()
    {
        return $this->commandBus;
    }
}
