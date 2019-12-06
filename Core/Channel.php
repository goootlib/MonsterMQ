<?php


namespace MonsterMQ\Core;

use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\Interfaces\AMQPDispatchers\ChannelDispatcher as ChannelDispatcherInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;

/**
 * This class responsible for channel handling operations
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Channel
{
    /**
     * Channel dispatcher instance.
     *
     * @var ChannelDispatcherInterface
     */
    protected $channelDispatcher;

    /**
     * Negotiated with server maximum channel number.
     *
     * @var int
     */
    protected $session;

    /**
     * Channel constructor.
     *
     * @param ChannelDispatcherInterface $dispatcher
     */
    public function __construct(ChannelDispatcherInterface $dispatcher, SessionInterface $session)
    {
        $this->channelDispatcher = $dispatcher;
        $this->session = $session;
    }

    /**
     * Open a channel for use.
     *
     * @param int|null $channelNumber Channel to open.
     *
     * @return int|bool Returns channel number that was opened. False in case
     *                  of there no available channel numbers.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function open(int $channelNumber = null)
    {
        $limit = $this->session->channelMaxNumber();

        if (!is_null($channelNumber)) {

            if (!in_array($channelNumber, ChannelDispatcher::$openedChannels)
                && !in_array($channelNumber, ChannelDispatcher::$suspendedChannels)
                && $channelNumber < $limit
            ) {
                $this->channelDispatcher->sendOpen($channelNumber);
                $this->channelDispatcher->receiveCloseOk();
                return $channelNumber;
            } else {
                if (in_array($channelNumber, ChannelDispatcher::$openedChannels)
                    || in_array($channelNumber, ChannelDispatcher::$suspendedChannels)) {
                    return $channelNumber;
                } else {
                    return false;
                }
            }
        }

        for ($channelNumber = 1; $channelNumber < $limit; $channelNumber++) {
            if (!in_array($channelNumber, ChannelDispatcher::$openedChannels)) {
                $this->channelDispatcher->sendOpen($channelNumber);
                $this->channelDispatcher->receiveOpenOk();
                ChannelDispatcher::$openedChannels[] = $channelNumber;
                return $channelNumber;
            } else {
                continue;
            }
        }

        return false;
    }

    /**
     * Requests server to reenable flow on specified channel.
     *
     * @param int $channel Channel to reenable.
     *
     * @return bool Whether flow was enabled.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function continue(int $channel): bool
    {
        $this->channelDispatcher->sendFlow($channel, true);
        $isActive = $this->channelDispatcher->receiveFlowOk();
        return $isActive;
    }

    /**
     * Closes selected channel.
     *
     * @param int $channel Channel to close.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function close(int $channel)
    {
        $this->channelDispatcher->sendClose($channel);
        $this->channelDispatcher->receiveCloseOk();
    }
}