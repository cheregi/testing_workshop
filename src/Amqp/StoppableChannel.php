<?php

namespace App\Amqp;

use PhpAmqpLib\Channel\AMQPChannel;

class StoppableChannel extends AMQPChannel
{
    public $continueWait = true;

    /**
     * @param int $timeout
     *
     * @return array|mixed
     */
    public function next_frame($timeout = 0)
    {
        $this->debug->debug_msg('waiting for a new frame');

        if (!empty($this->frame_queue)) {
            return array_shift($this->frame_queue);
        }

        if (!$this->continueWait) {
            return ['close', []];
        }

        return $this->connection->wait_channel($this->channel_id, $timeout);
    }

    public function wait($allowed_methods = null, $non_blocking = false, $timeout = 0)
    {
        $this->debug->debug_allowed_methods($allowed_methods);

        $deferred = $this->process_deferred_methods($allowed_methods);
        if ($deferred['dispatch'] === true) {
            return $this->dispatch_deferred_method($deferred['queued_method']);
        }

        // No deferred methods?  wait for new ones
        while (true) {
            list($frame_type, $payload) = $this->next_frame($timeout);

            if ($frame_type === 'close') {
                return;
            }

            $this->validate_method_frame($frame_type);
            $this->validate_frame_payload($payload);

            $method_sig = $this->build_method_signature($payload);
            $args = $this->extract_args($payload);

            $this->debug->debug_method_signature('> %s', $method_sig);

            $amqpMessage = $this->maybe_wait_for_content($method_sig);

            if ($this->should_dispatch_method($allowed_methods, $method_sig)) {
                return $this->dispatch($method_sig, $args, $amqpMessage);
            }

            // Wasn't what we were looking for? save it for later
            $this->debug->debug_method_signature('Queueing for later: %s', $method_sig);
            $this->method_queue[] = array($method_sig, $args, $amqpMessage);

            if ($non_blocking) {
                break;
            }
        }
    }

    public function stopWait()
    {
        $this->continueWait = false;
    }

    public function isStopped()
    {
        return !$this->continueWait;
    }
}
