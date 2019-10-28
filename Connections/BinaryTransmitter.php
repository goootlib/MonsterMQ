<?php

namespace MonsterMQ\Connections;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Interfaces\Socket;
use MonsterMQ\Support\FieldTableParser;
use MonsterMQ\Support\FieldType;
use MonsterMQ\Support\NumberConverter;

require dirname(__DIR__)."\\Support\\FieldTableParser.php";

class BinaryTransmitter
{
    use FieldTableParser;

    /**
     * Instance of socket connection which provide
     * API for sending and receiving raw data.
     * @var \MonsterMQ\Interfaces\Socket
     */
    protected $socket;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * Translates and sends unsigned integer as 8 bits.
     * @param int $number Must be between 0 and 255.
     */
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

    /**
     * Translates and sends unsigned integer as 16 bits.
     * @param int $number Must be between 0 and 65535.
     */
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

    /**
     * Translates and sends unsigned integer as 32 bits.
     * @param int $number Must be between 0 and 4294967295.
     */
    public function sendLong(int $number)
    {
        if($number > 4294967295 || $number < 0){
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of long value. Valid range is 0 - 4 294 967 295.'
            );
        }

        $binary = pack('N', $number);
        $this->socket->writeRaw($binary);
    }

    /**
     * Receives 8 bits from network and translates it
     * to unsigned integer.
     * @return int
     */
    public function receiveOctet()
    {
        $binaryData = $this->socket->readRaw(1);
        $translatedData = unpack('C',$binaryData);
        return $translatedData[1];
    }

    /**
     * Receives 16 bits from network and translates it
     * to unsigned integer.
     * @return int
     */
    public function receiveShort()
    {
        $binaryData = $this->socket->readRaw(2);
        $translatedData = unpack('n', $binaryData);
        return $translatedData[1];
    }

    /**
     * Receives 32 bits from network and translates it
     * to unsigned integer.
     * @return int
     */
    public function receiveLong()
    {
        $binaryData = $this->socket->readRaw(4);
        $translatedData = unpack('N',$binaryData);
        return $translatedData[1];
    }

    /**
     * Reads 256-byte maximum string from network.
     * Short string type contains first octet
     * which indicates the length of string.
     * @return string
     */
    public function receiveShortStr()
    {
        $rawLength = $this->socket->readRaw(1);
        list(,$length) = unpack('C', $rawLength);
        $data = $this->socket->readRaw($length);
        return $data;
    }

    /**
     * Reads 2^32 maximum length string.
     * Long strings contain first 32 bits which
     * indicating the length of string.
     * @return string
     */
    public function receiveLongStr()
    {
        $lenght = $this->receiveLong();
        $string = $this->receiveRaw($lenght);
        return $string;
    }

    /**
     * Receives 64 bits from network
     * and translates it to unsigned integer.
     * @return int
     */
    public function receiveLongLong()
    {
        $rawData = $this->receiveRaw(8);
        $translated = unpack('J', $rawData);
        return $translated[1];
    }

    public function receiveFieldTable($returnWithSize = false)
    {
        $tableSize = $this->receiveLong();
        $readLength = 0;
        while ($readLength < $tableSize){
            $key = $this->receiveShortStr();
            //Add 1 byte as length indicator of key
            $readLength += 1;
            //Add key length
            $readLength += strlen($key);

            $valueType = chr($this->receiveOctet());
            //Add 1 octet as value type
            $readLength += 1;
            $valueWithLength = $this->getFieldTableValueWithLength($valueType);
            //Add length of Field Table value
            $readLength += $valueWithLength[1];
            $result[$key] = $valueWithLength[0];
        }

        if($returnWithSize == true){
            return [$result, $tableSize];
        }else{
            return $result;
        }

    }

    /**
     * Receives bit from network. AMQP converts bits into bytes.
     * So we need to do backward conversion.
     * @return int
     */
    public function receiveBit()
    {
        $rawByte = $this->receiveRaw(1);
        $value = $rawByte | 1 ? 1 : 0;
        return $value;
    }
    /**
     * Reads raw, untranslated data from network.
     * @param $bytes Amount of data to read.
     */
    public function receiveRaw($bytes)
    {
        $this->socket->readRaw($bytes);
    }

    /**
     * Sends raw data to network.
     * @param $data Data to be sent.
     */
    public function sendRaw($data)
    {
        $this->socket->writeRaw($data);
    }
}