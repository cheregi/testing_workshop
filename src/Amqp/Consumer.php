<?php

namespace App\Amqp;

use App\Amqp\Thread\ReadWorker;
use App\Amqp\Thread\Task;
use Psr\Log\LoggerInterface;

class Consumer
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Pool
     */
    private $pool;

    /**
     * PHP 5 allows developers to declare constructor methods for classes.
     * Classes which have a constructor method call this method on each newly-created object,
     * so it is suitable for any initialization that the object may need before it is used.
     *
     * Note: Parent constructors are not called implicitly if the child class defines a constructor.
     * In order to run a parent constructor, a call to parent::__construct() within the child constructor is required.
     *
     * param [ mixed $args [, $... ]]
     *
     * @link https://php.net/manual/en/language.oop5.decon.php
     *
     * @param array           $config
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function start(bool &$continue)
    {
        $task = new Task($this->config, $continue);
        $this->logger->debug('Start reader');
        $this->pool = new \Pool(1, ReadWorker::class, [__DIR__ . '/../../vendor/autoload.php']);
        $this->pool->submit($task);

        return $task;
    }

    public function close()
    {
        $this->pool->shutdown();
    }

}
