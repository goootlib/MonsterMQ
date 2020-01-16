<?php


namespace MonsterMQ\Core;

use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\Interfaces\AMQPDispatchers\ChannelDispatcher as ChannelDispatcherInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

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
     * @var SessionInterface
     */
    protected $session;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Channel constructor.
     *
     * @param ChannelDispatcherInterface $dispatcher
     * @param SessionInterface $session
     */
    public function __construct(ChannelDispatcherInterface $dispatcher, SessionInterface $session, LoggerInterface $logger)
    {
        $this->channelDispatcher = $dispatcher;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * Open a channel for use.
     *
     * @param $channelNumber Channel to open.
     *
     * @return int|bool Returns channel number that was opened. False in case
     *                  of there no available channel numbers.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function open($channelNumber = null)
    {
        if (!is_null($channelNumber)) {
            return $this->openConcreteChannel($channelNumber);
        } else {
            return $this->openGeneratedChannel();
        }
    }

    /**
     * Opens channel which number was specified by user.
     *
     * @param int $channelNumber Specified channel number
     *
     * @return bool|int Channel number that was opened. False if specified channel
     *                  number already in use.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    protected function openConcreteChannel(int $channelNumber)
    {
        $limit = $this->session->channelMaxNumber();

        if (!$this->channelIsOpened($channelNumber) && $channelNumber < $limit) {
            $this->channelDispatcher->sendOpen($channelNumber);
            $this->channelDispatcher->receiveOpenOk();
            ChannelDispatcher::$openedChannels[] = $channelNumber;
            return $channelNumber;
        } else {
            if ($this->channelIsOpened($channelNumber)) {
                return $channelNumber;
            } else {
                return false;
            }
        }
    }

    /**
     * Opens first available channel number. Limited by number negotiated
     * with server during session establishment.
     *
     * @return int|bool Channel number that was opened. False if there was no
     *                  free channel number.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    protected function openGeneratedChannel()
    {
        $limit = $this->session->channelMaxNumber();

        for ($channelNumber = 1; $channelNumber < $limit; $channelNumber++) {
            if (!$this->channelIsOpened($channelNumber)) {
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
     * Checks whether channel is already opened.
     *
     * @param int $channelNumber Channel number to check.
     *
     * @return bool Whether channel is already opened.
     */
    public function channelIsOpened(int $channelNumber): bool
    {
        if (in_array($channelNumber, ChannelDispatcher::$openedChannels)
            || in_array($channelNumber, ChannelDispatcher::$suspendedChannels)) {
            return true;
        } else {
            return false;
        }
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