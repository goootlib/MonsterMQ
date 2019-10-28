<?php


namespace MonsterMQ\Support;


trait FieldTableParser
{
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

    protected function getBoolean()
    {
        return [(bool) $this->receiveBit(), 1];
    }

    protected function getShortShortInt()
    {
        $raw = $this->receiveRaw(1);
        $translated = unpack('c', $raw);
        return [$translated[1], 1];
    }

    protected function getShortShortUint()
    {
        return [$this->receiveOctet(), 1];
    }

    protected function getShortInt()
    {
        $unconverted = $this->receiveShort();
        //Since php do not support Big-Endian signed shorts
        //we need to do this conversion by helper.
        $converted = NumberConverter::toSignedShort($unconverted);
        return [$converted, 2];
    }

    protected function getShortUint()
    {
        return [$this->receiveShort(), 2];
    }

    protected function getLongInt()
    {
        $unconverted = $this->receiveLong();
        //Since php do not support Big-Endian signed longs
        //we need to do this conversion by helper.
        $converted = NumberConverter::toSignedLong($unconverted);
        return [$converted, 4];
    }

    protected function getLongUint()
    {
        return [$this->receiveLong(), 4];
    }

    protected function getFloat()
    {
        $raw = $this->receiveRaw(4);
        $translated = unpack('G', $raw);
        return [$translated[1], 4];
    }

    protected function getDouble()
    {
        $raw = $this->receiveRaw(8);
        $translated = unpack('E', $raw);
        return [$translated[1], 8];
    }

    protected function getDecimal()
    {
        $places = $this->receiveOctet();
        $unconvertedLong = $this->receiveLong();
        $unconvertedSignedLong = NumberConverter::toSignedLong($unconvertedLong);
        $converted = NumberConverter::toDecimal($places, $unconvertedSignedLong);
        return [$converted, 5];
    }

    protected function getShortString()
    {

        $data = $this->receiveShortStr();
        $length = strlen($data) + 1;
        return [$data, $length];
    }

    protected function getLongString()
    {
        $data = $this->receiveLongStr();
        $length = strlen($data) + 4;
        return [$data, $length];
    }

    protected function getTimestamp()
    {
        return [$this->receiveLongLong(), 8];
    }

    protected function getFieldArray()
    {
        $length = $this->receiveLong();
        $read = 0;
        while ($read < $length) {
            $valueType = char($this->receiveOctet());
            $read += 1;
            $valueWithSize = $this->getFieldValue($valueType);
            $read += $valueWithSize[1];
            $resultArray[] = $valueWithSize[0];
        }
        return [$resultArray, $read];
    }

    public function getFieldTable()
    {
         return $this->receiveFieldTable(true);
    }

    public function getFieldTableValueWithLength($valueType)
    {
        $method = $this->methodMap[$valueType];
        $valueWithSize = $this->{$method};
        return $valueWithSize;
    }
}