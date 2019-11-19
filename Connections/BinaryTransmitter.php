<?php

namespace MonsterMQ\Connections;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Interfaces\Stream as StreamInterface;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\TableValueUnpacker as TableValueUnpackerInterface;
use MonsterMQ\Interfaces\TableValuePacker as TableValuePackerInterface;
use MonsterMQ\Connections\TableValueUnpacker;
use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Support\FieldType;
use MonsterMQ\Support\NumberConverter;

/**
 * This class helps to receive and send data(through network)
 * translated to binary format.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class BinaryTransmitter implements BinaryTransmitterInterface
{

    /**
     * Instance of socket connection which provide API for sending and
     * receiving raw data.
     *
     * @var \MonsterMQ\Interfaces\Stream
     */
    protected $socket;

    /**
     * Instance of field table value unpacker, responsible for parsing table
     * values.
     *
     * @var TableValueUnpackerInterface
     */
    protected $tableValueUnpacker;

    /**
     * Field table value packer instance responsible for translating PHP arrays
     * into AMQP field tables.
     *
     * @var TableValuePacker
     */
    public $tableValuePacker;

    /**
     * Whether to store data in buffer instead of sending or receiving it
     * through network.
     *
     * @var bool
     */
    protected $accumulate = false;

    /**
     * Buffer that contains accumulated data.
     *
     * @var string
     */
    protected $buffer = "";

    public function __construct(
        StreamInterface $socket,
        TableValueUnpackerInterface $unpacker = null,
        TableValuePackerInterface $packer = null
    ){
        $this->socket = $socket;
        $this->tableValueUnpacker = $unpacker ?? new TableValueUnpacker($this);
        $this->tableValuePacker = $packer ?? new TableValuePacker($this);

    }

    /**
     * Sends or accumulate in buffer unsigned integer as 8 bits.
     *
     * @param int $number Must be between 0 and 255.
     */
    public function sendOctet(int $number)
    {
        if ($number > 255 || $number < 0) {
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of octet value. Valid 
                range is 0 - 255.'
            );
        }

        $binary = chr($number);
        $this->sendRaw($binary);
    }

    /**
     * Sends or accumulate in buffer unsigned integer as 16 bits.
     *
     * @param int $number Must be between 0 and 65535.
     */
    public function sendShort(int $number)
    {
        if ($number > 65535 || $number < 0) {
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of short value. Valid 
                range is 0 - 65535.'
            );
        }

        $binary = pack('n', $number);
        $this->sendRaw($binary);
    }

    /**
     * Sends or accumulate in buffer unsigned integer as 32 bits.
     *
     * @param int $number Must be between 0 and 2^32-1.
     */
    public function sendLong(int $number)
    {
        if ($number > 4294967295 || $number < 0) {
            throw new InvalidArgumentException(
                'Number '.$number.' out of valid range of long value. Valid 
                range is 0 - 4 294 967 295.'
            );
        }

        $binary = pack('N', $number);
        $this->sendRaw($binary);
    }

    /**
     * Sends or accumulate in buffer string up to 256 bytes length.
     *
     * @param string $value
     */
    public function sendShortStr(string $value)
    {
        $length = strlen($value);
        if ($length > 255) {
            throw new InvalidArgumentException(
                "Short string is too big. It must be equal or less then 255 
                bytes long. {$length} bytes given.");
        }

        $this->sendOctet($length);
        $this->sendRaw($value);
    }

    /**
     * Sends or accumulate in buffer strings up to 2^32 bits length.
     *
     * @param string $value Value to be sent.
     */
    public function sendLongStr(string $value)
    {
        $length = strlen($value);
        $limit = pow(2,32) - 1;
        if ($length > $limit) {
            throw new InvalidArgumentException(
                'Long string is too big. It must be less than 2^32 bytes.'
            );
        }
        $this->sendLong($length);
        $this->sendRaw($value);

    }

    /**
     * Translates PHP array into binary representation of AMQP field table
     * and then sends it or accumulate in buffer. Values of source array are
     * arrays first element of which are value type indicator
     * (see MonsterMQ\Support\FieldType) and the second is values itself.
     *
     * @param array $dataArray Given array which will be translated.
     *
     * @throws PackerException
     */
    public function sendFieldTable(array $dataArray)
    {
        $binaryData = "";
        foreach ($dataArray as $key => $valueWithType) {
            $binaryData .= $this->tableValuePacker->packFieldTableValue('s', $key);
            $binaryData .= $valueWithType[0];
            $binaryData .= $this->tableValuePacker->packFieldTableValue($valueWithType[0], $valueWithType[1]);
        }

        $length = strlen($binaryData);
        $this->sendLong($length);
        $this->sendRaw($binaryData);

    }

    /**
     * Receives byte from network or from buffer. AMQP converts bits into
     * bytes. So we need to do backward conversion.
     *
     * @return int Translated bit.
     */
    public function receiveBit(): int
    {
        $rawByte = $this->receiveRaw(1);

        $value = @($rawByte & 1) ? 1 : 0;
        return $value;
    }

    /**
     * Receives 8 bits from network or buffer and translates it to unsigned
     * integer.
     *
     * @return int
     */
    public function receiveOctet(): int
    {
        $binaryData = $this->receiveRaw(1);

        $translatedData = unpack('C', $binaryData);
        return $translatedData[1];
    }

    /**
     * Receives 16 bits from network or buffer and translates it to unsigned
     * integer.
     *
     * @return int
     */
    public function receiveShort(): int
    {
        $binaryData = $this->receiveRaw(2);

        $translatedData = unpack('n', $binaryData);
        return $translatedData[1];
    }

    /**
     * Receives 32 bits from network or buffer and translates it to unsigned
     * integer.
     *
     * @return int
     */
    public function receiveLong(): int
    {
        $binaryData = $this->receiveRaw(4);

        $translatedData = unpack('N', $binaryData);
        return $translatedData[1];
    }

    /**
     * Receives 64 bits from network or buffer and translates it to unsigned
     * integer.
     *
     * @return int
     */
    public function receiveLongLong(): int
    {
        $rawData = $this->receiveRaw(8);

        $translated = unpack('J', $rawData);
        return $translated[1];
    }

    /**
     * Reads up to 256 byte length string from network or buffer.
     * Short string type contains first octet which indicates the length of
     * string.
     *
     * @return string
     */
    public function receiveShortStr(): string
    {
        $rawLength = $this->receiveRaw(1);
        list(, $length) = unpack('C', $rawLength);
        $data = $this->receiveRaw($length);

        return $data;
    }

    /**
     * Reads 2^32 maximum length string. Long strings contain first 32 bits
     * indicating the length of string.
     *
     * @return string
     */
    public function receiveLongStr(): string
    {
        $length = $this->receiveLong();
        $string = $this->receiveRaw($length);

        return $string;
    }

    /**
     * Receives Field Table parameter from server response.
     *
     * @param bool $returnSize Whether to return size of returning data.
     *
     * @return array Associative array representing AMQP field table.
     */
    public function receiveFieldTable($returnSize = false) :array
    {
        $tableSize = $this->receiveLong();
        //Size of size indicator also included
        $readLength = 4;
        while ($readLength < $tableSize) {
            $key = $this->receiveShortStr();
            //Add 1 byte as length indicator of key
            $readLength += 1;
            //Add key length
            $readLength += strlen($key);

            $valueType = $this->receiveRaw(1);
            //Add 1 octet as value type
            $readLength += 1;
            $valueWithLength = $this->tableValueUnpacker->getFieldTableValue($valueType);
            //Add length of Field Table value
            $readLength += $valueWithLength[1];
            $result[$key] = $valueWithLength[0];
        }

        if ($returnSize == true) {
            return [$result, $tableSize];
        } else {
            return $result;
        }

    }

    /**
     * Sends length of buffer through network.
     */
    public function sendBufferLength()
    {
        $transmitter = new BinaryTransmitter($this->socket);
        $transmitter->sendLong($this->bufferLength());
    }

    /**
     * Sends data accumulated in buffer and clears it.
     */
    public function sendBuffer()
    {
        $this->socket->writeRaw($this->buffer);
        unset($this->buffer);
    }

    /**
     * Retrieves data from internal buffer.
     *
     * @param  int    $bytes Amount of data to retrieve in bytes.
     * @return string|null
     */
    protected function retrieveFromBuffer(int $bytes): ?string
    {
        if (!empty($this->buffer)) {
            $retrieved = substr($this->buffer,0, $bytes);
            $this->buffer = substr($this->buffer, $bytes);
            return $retrieved;
        } else {
            return null;
        }
    }

    /**
     * Receives binary data into buffer.
     *
     * @param int $bytes Amount of data to be received.
     */
    public function receiveIntoBuffer(int $bytes)
    {
        $this->buffer = $this->socket->readRaw($bytes);
    }

    /**
     * Enables data buffering.
     */
    public function enableBuffering()
    {
        $this->accumulate = true;
    }

    /**
     * Disables data accumulation in buffer.
     */
    public function disableBuffering()
    {
        $this->accumulate = false;
    }

    /**
     * Whether buffering enabled or not.
     *
     * @return bool
     */
    public function bufferingEnabled(): bool
    {
        return $this->accumulate;
    }

    /**
     * Returns size of data accumulated in buffer.
     *
     * @return int
     */
    public function bufferLength(): int
    {
        return strlen($this->buffer);
    }

    /**
     * Reads raw untranslated data from network.
     *
     * @param int $bytes Amount of data to read.
     * @return string    Untranslated raw data.
     */
    public function receiveRaw(int $bytes) :string
    {
        if ($this->accumulate) {
            return $this->retrieveFromBuffer($bytes);
        }
        return $this->socket->readRaw($bytes);
    }

    /**
     * Sends raw untranslated data through network.
     *
     * @param string $data Data to be sent.
     * @return int         Amount of data has been sent.
     */
    public function sendRaw(string $data): ?int
    {
        if ($this->accumulate) {
            $this->buffer .= $data;
            return null;
        }
        return $this->socket->writeRaw($data);
    }


}