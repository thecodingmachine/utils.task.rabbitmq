# Task manage by RabbitMq

This package is used to manage task in RabbitMq.

## Installation

```
composer require thecodingmachine/utils.task.rabbitmq
```

Once installed, you need to register the [`TheCodingMachine\RabbitMQServiceProvider`](src/DI/RabbitMQServiceProvider.php) into your container.

If your container supports Puli integration, you have nothing to do. Otherwise, refer to your framework or container's documentation to learn how to register *service providers*.
For Mouf, please see the last part.

### Required
This package can be used without RabbitMQ, but all task are executed in real time.
To use RabbitMq please install it on your environment.
After this add the RabbitMQ management to compute the task number in it.
```
rabbitmq-plugins enable rabbitmq_management
```

## Introduction

This service provider is meant to provide all the class used to create task.

## Expected values / services

This *service provider* expects the following configuration / services to be available:

| Name                 | Compulsory      | Description                            |
|----------------------|------------|----------------------------------------|
| `RABBITMQ_HOST`      |  **yes**    | The RabbitMQ host.                     |
| `RABBITMQ_PORT`      |  **yes**    | The RabbitMQ user.                     |
| `RABBITMQ_USER`      |  **yes**    | The RabbitMQ password.                 |
| `RABBITMQ_PASSWORD`  |  **yes**    | The RabbitMQ port.                     |
| `RABBITMQ_API_HOST`  |  **yes**    | The RabbitMQ management host.          |
| `RABBITMQ_API_PORT`  |  **yes**    | The RabbitMQ management port.          |
| `RABBITMQ_MAINQUEUE` |  **yes**    | The RabbitMQ main queue name           |

You can edit the connection instance to add an error queue, if it's enable or not, the max tries by error and the priority.

## Provided services

This *service provider* provides the following services:

| Service name                                         | Description                          |
|------------------------------------------------------|--------------------------------------|
| `Mouf\Utils\Task\Services\RabbitMQ\Connection`       | RabbitMQ connection   |
| `League\Tactician\CommandBus`                        | Bus to manage the task type   |
| `Mouf\Utils\Task\Services\RabbitMQ\ConsumerService`  | Class to consum the RabbitMQ queue   |
| `Mouf\Utils\Task\Commands\RabbitMQ\ConsumerCommand`  | Class to add a command to consum the RabbitMQ queue   |
| `Mouf\Utils\Task\Services\RabbitMQ\ProducerService`  | Class to product a task in the RabbitMQ queue   |


## Extended services

This *service provider* does not extend any service.


## Use it

You can add an error queue if you add the errorQueue name in the "Mouf\Utils\Task\Services\RabbitMQ\Connection" instance.

After this, you can create your task. It's really simple, create a new instance with an extends to Mouf\Utils\Task\Task. This class must be serialize.
This class is to get your information, in this example to get a carId
```
<?php
namespace MyProject\Tasks;

class GenerateCarTask extends Task
{
    protected $carId;

    /**
     * GenerateCarTask constructor.
     * @param $carId
     */
    public function __construct($carId)
    {
        $this->carId = $carId;
    }

    /**
     * @return mixed
     */
    public function getCarId()
    {
        return $this->carId;
    }
}
```

Create the same class like Task instead of Task to Handler. Add a function handler:
```
<?php
namespace MyProject\Tasks;

class GenerateCarHandler
{
    /**
     * GenerateCarHandler constructor.
     */
    public function __construct()
    {
        /* Your instance if you want */
    }

    /**
     * @param GenerateCarTask $task
     * @return bool
     * @throws \Exception
     */
    public function handle(GenerateCarTask $task)
    {
        $carId = $task->getCarId();

        /* Your code */

        return true;
    }
}
```

## Mouf installation

Mouf 2.0 doesn't use the provider. So you must create each instance.
Go to the Mouf interface
Add the constant : 
- RABBITMQ_HOST
- RABBITMQ_PORT
- RABBITMQ_USER
- RABBITMQ_PASSWORD
- RABBITMQ_API_HOST
- RABBITMQ_API_PORT
- RABBITMQ_MAINQUEUE

In the Mouf interface, click on "Instance", "Create a new instance by PHP code" and create the followig elements:

| Instance name            | Code   |
|-----------------|------------|
| RABBITMQ_HOST      | return RABBITMQ_HOST  |
| RABBITMQ_PORT       | RABBITMQ_PORT  |
| RABBITMQ_USER       | RABBITMQ_USER  |
| RABBITMQ_PASSWORD       | RABBITMQ_PASSWORD  |
| RABBITMQ_API_HOST       | RABBITMQ_API_HOST  |
| RABBITMQ_API_PORT       | RABBITMQ_API_PORT  |
| RABBITMQ_MAINQUEUE       | RABBITMQ_MAINQUEUE  |
| Mouf\Utils\Task\Services\RabbitMQ\Connection       | return RabbitMQServiceProvider::createConnection($container)  |
| League\Tactician\CommandBus       | return RabbitMQServiceProvider::createCommandBus($container)  |
| Mouf\Utils\Task\Services\RabbitMQ\ConsumerService       | return RabbitMQServiceProvider::createConsumerService($container)  |
| Mouf\Utils\Task\Commands\RabbitMQ\ConsumerCommand       | return RabbitMQServiceProvider::createConsumerCommand($container)  |
| Mouf\Utils\Task\Services\RabbitMQ\ProducerService       | return RabbitMQServiceProvider::createProducerService($container)  |

To finish, search the "console" instance (class Mouf\Console\ConsoleApplication) and the "Mouf\Utils\Task\Commands\RabbitMQ\ConsumerCommand" isntance to the "commands" array.
