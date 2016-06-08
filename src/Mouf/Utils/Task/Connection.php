<?php
/**
 * Created by PhpStorm.
 * User: vaidiep
 * Date: 01/06/16
 * Time: 5:15 PM.
 */

namespace Mouf\Utils\Task\Services\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use GuzzleHttp\Client;

class Connection
{
    /**
     * Connection.
     *
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * Channel.
     *
     * @var AMQPChannel
     */
    private $channel;

    /**
     * To disable or enable the rabbitmq service.
     *
     * @var bool
     */
    private $enable = true;

    /**
     * Name of queue.
     *
     * @var string
     */
    private $mainQueue;

    /**
     * Name of error queue.
     *
     * @var string
     */
    private $errorQueue;

    /**
     * Max priority of task.
     *
     * @var number
     */
    private $maxPriority;

    /**
     * Max priority of task.
     *
     * @var number
     */
    private $maxTries;

    /**
     * Exchanger name.
     *
     * @var string
     */
    private $exchanger;

    /**
     * RabbitMQ user.
     *
     * @var string
     */
    private $user;

    /**
     * RabbitMQ password.
     *
     * @var string
     */
    private $password;

    /**
     * RabbitMQ api host.
     *
     * @var string
     */
    private $apiHost;

    /**
     * RabbitMQ api port.
     *
     * @var string
     */
    private $apiPort;

    /**
     * Connection constructor.
     * 
     * @param string $host        RabbitMq host
     * @param string $port        RabbitMq port
     * @param string $user        RabbitMq user
     * @param string $password    RabbitMq password
     * @param string $mainQueue   Name of queue
     * @param string $errorQueue  Name of error queue. If it's not set, there is no error queue
     * @param number $maxPriority By default it's 1 (no priority), you can change it
     * @param number $maxTries    Max tries if the task is in error before kill it
     * @param bool   $enable      If you want to disable RabbitMq, it it's disable this execute the task in real time 
     */
    public function __construct($host, $port, $user, $password, $apiHost, $apiPort, $mainQueue, $errorQueue = null, $maxPriority = 1, $maxTries = 5, $enable = true)
    {
        $this->user = $user;
        $this->password = $password;
        $this->apiHost = $apiHost;
        $this->apiPort = $apiPort;
        $this->enable = $enable;
        $this->mainQueue = $mainQueue;
        $this->errorQueue = $errorQueue;
        $this->maxPriority = $maxPriority;
        $this->maxTries = $maxTries;
        $this->exchanger = $this->mainQueue.'.dead_letter';
        if ($this->enable) {
            //Create connection
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password);

            //Create channel
            $this->channel = $this->connection->channel();

            //Don't dispatch a new message to a worker until it has processed and acknowledged the previous one
            $this->channel->basic_qos(null, 1, null);

            //Declare Error Queue
            $this->channel->exchange_declare($this->exchanger, 'fanout', false, false, false);
            if ($this->errorQueue) {
                $this->channel->queue_declare($this->errorQueue, false, true, false, false, false, array('x-max-priority' => ['I', $this->exchanger]));
                $this->channel->queue_bind($this->errorQueue, $this->exchanger);
            }

            //Declare Main Queue with Dead Letter Exchanges (http://www.rabbitmq.com/dlx.html)
            $this->channel->queue_declare($this->mainQueue, false, true, false, false, false, array('x-dead-letter-exchange' => array('S', $this->exchanger), 'x-max-priority' => ['I', $this->exchanger]));
        }
    }

    public function getMainQueue()
    {
        return $this->mainQueue;
    }

    public function getErrorQueue()
    {
        return $this->errorQueue;
    }

    public function getMaxTries()
    {
        return $this->maxTries;
    }

    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * Connection desstructor.
     */
    public function __destruct()
    {
        if ($this->enable) {
            $this->channel->close();
            $this->connection->close();
        }
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param $queueName
     */
    public function getNumberOfMessages($queueName)
    {
        $client = new Client();
        $res = $client->get('http://'.$this->apiHost.':'.$this->apiPort.'/api/queues', [
            'auth' => [$this->user, $this->password],
        ]);
        $queues = json_decode($res->getBody(), true);

        foreach ($queues as $queue) {
            if ($queue['name'] == $queueName) {
                return $queue['messages_ready'];
            }
        }

        return 0;
    }
}
