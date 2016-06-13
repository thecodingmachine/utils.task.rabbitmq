<?php

namespace Mouf\Utils\Task\Commands\RabbitMQ;

use Mouf\Utils\Task\Services\RabbitMQ\ConsumerService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Command to launch one RabbitMQ Consumer Service.
 */
class ConsumerCommand extends Command
{
    /**
     * @var ConsumerService
     */
    public $consumerService;

    /**
     * ConsumerCommand constructor.
     *
     * @param ConsumerService $consumerService
     */
    public function __construct(ConsumerService $consumerService)
    {
        parent::__construct();

        $this->consumerService = $consumerService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rabbitmq:consume')
            ->setDescription('Command to launch one RabbitMQ Consumer Service')
            ->addOption('error', null, InputOption::VALUE_NONE, 'If set, the command will treat the error messages');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($input->getOption('error')) {
                $nb = $this->consumerService->consumeErrors($output);
                $output->writeln($nb.' errors have been consumed');
            } else {
                $this->consumerService->loop();
            }
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
