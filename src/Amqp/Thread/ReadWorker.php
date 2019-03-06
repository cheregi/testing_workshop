<?php

namespace App\Amqp\Thread;

class ReadWorker extends \Worker
{
    const DATA_TYPE_STOP = 0;

    /**
     * (PECL pthreads &gt;= 2.0.0)<br/>
     * The programmer should always implement the run method for objects
     * that are intended for execution.
     *
     * @link https://secure.php.net/manual/en/threaded.run.php
     * @return void
     */
    public function run()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';
    }

    public function start($options = PTHREADS_INHERIT_ALL) {
        return parent::start(PTHREADS_INHERIT_NONE);
    }

}
