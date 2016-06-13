<?php

namespace Mouf\Utils\Task\DI;

use Interop\Container\ServiceProvider;
use Mouf\Utils\Task\Services\RabbitMQ\Connection;
use Interop\Container\ContainerInterface;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\CallableLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Mouf\Utils\Task\Services\RabbitMQ\ConsumerService;
use Mouf\Utils\Task\Commands\RabbitMQ\ConsumerCommand;
use Mouf\Utils\Task\Services\RabbitMQ\ProducerService;

class RabbitMQServiceProvider implements ServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @see \Interop\Container\ServiceProvider::getServices()
     */
    public function getServices()
    {
        return [
            Connection::class => [self::class, 'createConnection'],
            CommandBus::class => [self::class, 'createCommandBus'],
            ConsumerService::class => [self::class, 'createConsumerService'],
            ConsumerCommand::class => [self::class, 'createConsumerCommand'],
            ProducerService::class => [self::class, 'createProducerService'],
        ];
    }

    public static function createConnection(ContainerInterface $container)
    {
        return new Connection($container->get('RABBITMQ_HOST'),
                            $container->get('RABBITMQ_PORT'),
                            $container->get('RABBITMQ_USER'),
                            $container->get('RABBITMQ_PASSWORD'),
                            $container->get('RABBITMQ_API_HOST'),
                            $container->get('RABBITMQ_API_PORT'),
                            $container->get('RABBITMQ_MAINQUEUE'),
                            $container->get('RABBITMQ_ERRORQUEUE'),
                            $container->get('RABBITMQ_MAXPRIORITY'),
                            $container->get('RABBITMQ_MAXTRIES'),
                            $container->get('RABBITMQ_ENABLE'));
    }

    public static function createCommandBus(ContainerInterface $container)
    {
        $middleware = [new CommandHandlerMiddleware(new ClassNameExtractor(),
                                    new CallableLocator(function ($className) use ($container) {
                                        $serviceName = substr($className, strrpos($className, '\\') + 1);
                                        $serviceName = str_replace('Task', 'Handler', $serviceName);

                                        return $container->get($serviceName);
                                    }), new HandleInflector())];

        return new CommandBus($middleware);
    }

    public static function createConsumerService(ContainerInterface $container)
    {
        return new ConsumerService($container->get(Connection::class), $container->get(CommandBus::class));
    }

    public static function createConsumerCommand(ContainerInterface $container)
    {
        return new ConsumerCommand($container->get(ConsumerService::class));
    }

    public static function createProducerService(ContainerInterface $container)
    {
        $producerService = new ProducerService($container->get(Connection::class));
        $producerService->setConsumerService($container->get(ConsumerService::class));

        return $producerService;
    }
}
