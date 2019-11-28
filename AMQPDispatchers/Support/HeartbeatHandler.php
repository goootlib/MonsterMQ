<?php


namespace MonsterMQ\AMQPDispatchers\Support;


/**
 * Method of this trait responsible for heartbeat handling.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
trait HeartbeatHandler
{
    /**
     * Skips heartbeat frames, and returns frame types of other incoming
     * frames.
     *
     * @return int Frame type of incoming frame.
     */
    protected function receiveFrameType(): int
    {
        while (!is_null($frametype = $this->transmitter->receiveOctet())) {
            if ($frametype == static::HEARTBEAT_FRAME_TYPE) {
                $this->transmitter->receiveShort();
                $this->transmitter->receiveLong();
                $this->validateFrameDelimiter();
                continue;
            } else {
                return $frametype;
            }
        }
    }
}