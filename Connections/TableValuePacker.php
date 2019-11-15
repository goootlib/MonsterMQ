<?php

namespace MonsterMQ\Connections;

use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Interfaces\TableValuePacker as TableValuePackerInterface;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Support\FieldType;

class TableValuePacker implements TableValuePackerInterface
{
    protected $methodMap = [
        FieldType::BOOLEAN => 'packBoolean',
        FieldType::SHORT_SHORT_INT => 'packShortShortInt',
        FieldType::SHORT_SHORT_UINT => 'packShortShortUint',
        FieldType::SHORT_INT => 'packShortInt',
        FieldType::SHORT_UINT => 'packShortUint',
        FieldType::LONG_INT => 'packLongInt',
        FieldType::LONG_UINT => 'packLongUint',
        FieldType::FLOAT => 'packFloat',
        FieldType::DOUBLE => 'packDouble',
        FieldType::DECIMAL => 'packDecimal',
        FieldType::SHORT_STRING => 'packShortString',
        FieldType::LONG_STRING => 'packLongString',
        FieldType::FIELD_ARRAY => 'packFieldArray',
        FieldType::TIMESTAMP => 'packTimestamp',
        FieldType::FIELD_TABLE => 'packFieldTable'
    ];

    protected $transmitter;

    /**
     * Whether machine byte order is little-endian or not.
     *
     * @var bool
     */
    protected $isLittleEndian;

    public function __construct(BinaryTransmitterInterface $transmitter)
    {
        $this->transmitter = $transmitter;

        //Detecting machine byte order.
        $binary = unpack('S', "\x01\x00");
        $this->isLittleEndian = $binary[1] == 1;
    }

    /**
     * Corrects byte order from little endian to network and vice versa.
     *
     * @param string $binary Source binary string.
     *
     * @return string Converted binary string.
     */
    protected function correctByteOrder(string $binary): string
    {
        return $this->isLittleEndian ? strrev($binary) : $binary;
    }

    /**
     * Packs bits into binary string. Bits in AMQP accumulates into bytes.
     *
     * @param int $value Boolean value to be packed.
     *
     * @return string Binary representaion of boolean.
     */
    protected function packBoolean(int $value): string
    {
        $value = $value ? 1 : 0;
        return pack('C', $value);
    }

    /**
     * Packs signed 8 bit integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of signed short short integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packShortShortInt(int $value): string
    {
        if ($value < -128 || $value > 127 ) {
            throw new \InvalidArgumentException(
                "packShortShortInt method must receives values from -128 to 
                127. {$value} given."
            );
        }

        return pack('c', $value);
    }

    /**
     * Packs unsigned 8 bit integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of short short integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packShortShortUint(int $value): string
    {
        if ($value < 0 || $value > 255 ) {
            throw new \InvalidArgumentException(
                "packShortShortUint method must receives values from 0 to 255.
                 {$value} given."
            );
        }

        return chr($value);
    }

    /**
     * Packs 16 bit signed integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of signed short integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packShortInt(int $value): string
    {
        if ($value < -32768 || $value > 32767 ) {
            throw new \InvalidArgumentException(
                "packShortInt method must receives values from -32768 to 32767.
                 {$value} given."
            );
        }

        // Here we need to reverse bytes if our machine byte order is little-endian
        // because php do not support big-endian byte order for signed shorts.
        return $this->correctByteOrder(pack('s', $value));
    }

    /**
     * Packs 16 bit unsigned integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of short unsigned integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packShortUint(int $value): string
    {
        if ($value < 0 || $value > 65535) {
            throw new \InvalidArgumentException(
                "packShortUint method must receives values from 0 to 65535. 
                {$value} given.");
        }

        return pack('n', $value);
    }

    /**
     * Packs 32 bit signed integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of singed long integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packLongInt(int $value): string
    {
        if ($value < -2147483648 || $value > 2147483647) {
            throw new \InvalidArgumentException(
                "packLongInt method must receives values from -2147483648
                to 2147483647. {$value} given.");
        }

        return $this->correctByteOrder(pack('l', $value));
    }

    /**
     * Packs 32 bit unsigned integer into binary string.
     *
     * @param int $value Integer to be packed.
     *
     * @return string Binary representation of unsigned long integer.
     *
     * @throws \InvalidArgumentException
     */
    protected function packLongUint(int $value): string
    {
        if ($value < 0 || $value > 4294967295) {
            throw new \InvalidArgumentException(
                "packLongUint method must receives values from 0 to 4294967295.
                {$value} given.");
        }

        return pack('N', $value);
    }

    /**
     * Packs fractional numbers into binary string.
     *
     * @param int $value  Represents value.
     * @param int $places Represents position of point.
     *
     * @return string Binary representation of AMQP decimal value.
     *
     * @throws \InvalidArgumentException
     */
    protected function packDecimal(int $value, int $places): string
    {
        $binary = $this->packShortShortUint($places);
        $binary .= $this->packLongUint($value);

        return $binary;
    }

    /**
     * Packs up to 255 bytes long string as AMQP field table short string value.
     *
     * @param string $string Short string to be packed.
     *
     * @return string Binary representation of AMQP short string.
     *
     * @throws \InvalidArgumentException
     */
    protected function packShortString(string $string): string
    {
        $length = strlen($string);
        if ( $length > 255) {
            throw new \InvalidArgumentException(
                "packShortString method argument is not allowed to be larger 
                than 255 bytes. {$length} bytes given.");
        }

        $binary = $this->packShortShortUint($length);
        $binary .= $string;

        return $binary;
    }

    /**
     * Packs up to 2^32 bit length string as AMQP field table long string value.
     *
     * @param string $string Long string to be packed.
     *
     * @return string Binary representation of AMQP long string.
     *
     * @throws \InvalidArgumentException
     */
    protected function packLongString(string $string): string
    {
        $length = strlen($string);
        $limit = pow(2,32) - 1;
        if ($length > $limit) {
            throw new \InvalidArgumentException(
                'Long string is too big. It must be less than 2^32 bytes.'
            );
        }
        $binary = $this->packLongUint($length);
        $binary .= $string;

        return $binary;
    }

    /**
     * Packs PHP array as AMQP field table array value.
     *
     * @param array $array PHP array to be packed.
     *
     * @return string Binary representation of AMQP field table array value.
     *
     * @throws PackerException
     * @throws \InvalidArgumentException
     */
    protected function packFieldArray(array $array): string
    {
        $binary = '';
        foreach ($array as [$valueType, $value]) {
            $binary .= $valueType;
            $binary .= $this->packFieldTableValue($valueType, $value);
        }
        $length = strlen($binary);
        $length = $this->packLongUint($length);
        $binary = $length.$binary;

        return $binary;
    }

    /**
     * Packs unsigned up to 2^64 bit length integer into binary string.
     *
     * @param int $timestamp Integer representing timestamp to be packed.
     *
     * @return string Binary string.
     */
    protected function packTimestamp(int $timestamp): string
    {
        return pack('J', $timestamp);
    }

    /**
     * Packs nested associative php array into binary representation of AMQP
     * field table.
     *
     * @param array $dataArray Array of data to be packed.
     */
    protected function packFieldTable(array $dataArray)
    {
        $this->transmitter->sendFieldTable($dataArray);
    }

    /**
     * Packs field table value of specified type into binary string.
     *
     * @param string $valueType Type of value to be packed.
     * @param        $value     Value to be packed.
     *
     * @return string Binary string
     *
     * @throws PackerException
     * @throws \InvalidArgumentException
     */
    public function packFieldTableValue(string $valueType, $value): string
    {
        $method = $this->methodMap[$valueType];
        if (!isset($method)) {
            throw new PackerException("Unsupported field table value type {$valueType}.");
        }
        return $this->{$method}($value);
    }
}