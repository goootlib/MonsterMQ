<?php


namespace MonsterMQ\Connections;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Interfaces\Socket;

class BinaryTransmitter
{
    protected $socket;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    public function sendOctet(int $number)
    {
        if($number > 255 || $number < 0){
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of octet value. Valid range is 0 - 255.'
            );
        }

        $binary = pack('C',$number);
        $this->socket->writeRaw($binary);
    }

    public function sendShort(int $number)
    {
        if($number > 65535 || $number < 0){
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of short value. Valid range is 0 - 65535.'
            );
        }

        $binary = pack('n',$number);
        $this->socket->writeRaw($binary);
    }

    public function sendLong(int $number)
    {
        if($number > 4294967295 || $number < 0){
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of long value. Valid range is 0 - 4 294 967 295.'
            );
        }

        $binary = pack('N',$number);
        $this->socket->writeRaw($binary);
    }

    public function receiveOctet()
    {
        $binaryData = $this->socket->readRaw(1);
        $translatedData = unpack('C',$binaryData);
        return $translatedData[1];
    }

    public function receiveShort()
    {
        $binaryData = $this->socket->readRaw(2);
        $translatedData = unpack('n',$binaryData);
        return $translatedData[1];
    }

    public function receiveLong()
    {
        $binaryData = $this->socket->readRaw(4);
        $translatedData = unpack('N',$binaryData);
        return $translatedData[1];
    }

    public function receiveShortStr()
    {
        $rawLength = $this->socket->readRaw(1);
        list(,$length) = unpack('C', $rawLength);
        $data = $this->socket->readRaw($length);
        return $data;
    }

    public function receiveRaw($bytes)
    {
        $this->socket->readRaw($bytes);
    }

    public function sendRaw($data)
    {
        $this->socket->writeRaw($data);
    }
}