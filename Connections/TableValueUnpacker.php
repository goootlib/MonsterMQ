<?php


namespace MonsterMQ\Connections;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Interfaces\TableValueUnpacker as TableValueUnpackerInterface;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Support\FieldType;

/**
 * This class translates binary field table values into corresponding PHP
 * values.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class TableValueUnpacker implements TableValueUnpackerInterface
{
    /**
     * Map defining which table value will be handled by which method.
     *
     * @var array
     */
    protected $methodMap = [
        FieldType::BOOLEAN => 'getBoolean',
        FieldType::SHORT_SHORT_INT => 'getShortShortInt',
        FieldType::SHORT_SHORT_UINT => 'getShortShortUint',
        FieldType::SHORT_INT => 'getShortInt',
        FieldType::SHORT_UINT => 'getShortUint',
        FieldType::LONG_INT => 'getLongInt',
        FieldType::LONG_UINT => 'getLongUint',
        FieldType::FLOAT => 'getFloat',
        FieldType::DOUBLE => 'getDouble',
        FieldType::DECIMAL => 'getDecimal',
        FieldType::SHORT_STRING => 'getShortString',
        FieldType::LONG_STRING => 'getLongString',
        FieldType::FIELD_ARRAY => 'getFieldArray',
        FieldType::TIMESTAMP => 'getTimestamp',
        FieldType::FIELD_TABLE => 'getFieldTable'
    ];

    /**
     * Instance of binary transmitter.
     *
     * @var \MonsterMQ\Interfaces\BinaryTransmitter
     */
    protected $transmitter;

    public function __construct(BinaryTransmitterInterface $transmitter)
    {
        $this->transmitter = $transmitter;
    }

    /**
     * Bits in AMQP are sent as octets. This function fetches octet from
     * network and then cast it to boolean.
     *
     * @return array First element contains boolean value, second - its size,
     *               which is always 1 byte.
     */
    protected function getBoolean(): array
    {
        return [(bool) $this->transmitter->receiveBit(), 1];
    }

    /**
     * Fetches short short signed integer.
     *
     * @return array First element contains number, second - its size, which is
     *               always 1 byte.
     */
    protected function getShortShortInt(): array
    {
        if ($this->transmitter->bufferingEnabled()) {
            $raw = $this->transmitter->retrieveFromBuffer(1);
        } else {
            $raw = $this->transmitter->receiveRaw(1);
        }
        $translated = unpack('c', $raw);
        return [$translated[1], 1];
    }

    /**
     * Fetches short short unsigned integer.
     *
     * @return array First element contains number, second - its size, which
     *               is always 1 byte.
     */
    protected function getShortShortUint(): array
    {
        return [$this->transmitter->receiveOctet(), 1];
    }

    /**
     * Fetches short signed integer.
     *
     * @return array First element contains number, second - its size, which is
     *               always 2 bytes.
     */
    protected function getShortInt(): array
    {
        $unconverted = $this->transmitter->receiveShort();
        //Since php do not support Big-Endian signed shorts
        //we need to do this conversion by helper.
        $converted = NumberConverter::toSignedShort($unconverted);
        return [$converted, 2];
    }

    /**
     * Fetches short unsigned integer.
     *
     * @return array First element contains number, second - its size, which is
     *               always 2 bytes.
     */
    protected function getShortUint(): array
    {
        return [$this->transmitter->receiveShort(), 2];
    }

    /**
     * Fetches long signed integer.
     *
     * @return array First element contains number, second - its size, which is
     *               always 4 bytes.
     */
    protected function getLongInt(): array
    {
        $unconverted = $this->transmitter->receiveLong();
        //Since php do not support Big-Endian signed longs
        //we need to do this conversion by helper.
        $converted = NumberConverter::toSignedLong($unconverted);
        return [$converted, 4];
    }

    /**
     * Fetches long unsigned integer field.
     *
     * @return array First element contains number, second - its size, which is
     *               always 4 bytes.
     */
    protected function getLongUint(): array
    {
        return [$this->transmitter->receiveLong(), 4];
    }

    /**
     * Fetches float number field.
     *
     * @return array First element contains number, second - its size, which is
     *               always 4 bytes.
     */
    protected function getFloat(): array
    {
        if ($this->transmitter->bufferingEnabled()) {
            $raw = $this->transmitter->retrieveFromBuffer(4);
        } else {
            $raw = $this->transmitter->receiveRaw(4);
        }
        $translated = unpack('G', $raw);
        return [$translated[1], 4];
    }

    /**
     * Fetches double number field.
     *
     * @return array First element contains number, second - its size, which is
     *               always 8 bytes.
     */
    protected function getDouble(): array
    {
        if ($this->transmitter->bufferingEnabled()) {
            $raw = $this->transmitter->retrieveFromBuffer(8);
        } else {
            $raw = $this->transmitter->receiveRaw(8);
        }
        $translated = unpack('E', $raw);
        return [$translated[1], 8];
    }

    /**
     * Fetches decimal field.
     *
     * @return array First element contain number, second - its size, which is
     *               always 5 bytes.
     */
    protected function getDecimal(): array
    {
        $places = $this->transmitter->receiveOctet();
        $unconvertedLong = $this->transmitter->receiveLong();
        $unconvertedSignedLong = NumberConverter::toSignedLong($unconvertedLong);
        $converted = NumberConverter::toDecimal($places, $unconvertedSignedLong);
        return [$converted, 5];
    }

    /**
     * Fetches short string field.
     *
     * @return array First element contains string, second - its size, which
     *               might be up to 256 bytes.
     */
    protected function getShortString(): array
    {

        $data = $this->transmitter->receiveShortStr();
        $length = strlen($data) + 1;
        return [$data, $length];
    }

    /**
     * Fetches long string field.
     *
     * @return array First element contains string, second - its size.
     */
    protected function getLongString(): array
    {
        $data = $this->transmitter->receiveLongStr();
        $length = strlen($data) + 4;
        return [$data, $length];
    }

    /**
     * Fetches timestamp field.
     *
     * @return array First element contains timestamp second - its size, which
     *               always 8 byte.
     */
    protected function getTimestamp(): array
    {
        return [$this->transmitter->receiveLongLong(), 8];
    }

    /**
     * Fetches array field.
     *
     * @return array First element contains array, second - its size.
     */
    protected function getFieldArray(): array
    {
        $length = $this->transmitter->receiveLong();
        $read = 0;
        while ($read < $length) {
            if ($this->transmitter->bufferingEnabled()) {
                $valueType = $this->transmitter->retrieveFromBuffer(1);
            } else {
                $valueType = $this->transmitter->receiveRaw(1);
            }
            $read += 1;
            $valueWithSize = $this->getFieldTableValue($valueType);
            $read += $valueWithSize[1];
            $resultArray[] = $valueWithSize[0];
        }
        return [$resultArray, $read];
    }

    /**
     * Fetches nested AMQP Field Table.
     *
     * @return array Nested AMQP Field Table.
     */
    protected function getFieldTable(): array
    {
         return $this->transmitter->receiveFieldTable(true);
    }

    /**
     * Fetches value along with value size from Field Table.
     *
     * @param string $valueType Type of Field Table value to fetch.
     *
     * @return array|null First element contains value, second - its size.
     */
    public function getFieldTableValue(string $valueType): ?array
    {
        $method = $this->methodMap[$valueType];

        if (!isset($method)) {
            throw new \InvalidArgumentException(
                "Unsupported AMQP field table value type - {$valueType}"
            );
        }

        if ($valueType == FieldType::VOID) {
            return null;
        }
        $valueWithSize = $this->{$method}();
        return $valueWithSize;
    }

}