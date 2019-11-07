<?php


namespace MonsterMQ\Connections;


use http\Exception\InvalidArgumentException;

trait FieldTablePacker
{
    /**
     * Packs bits into binary string.
     *
     * @param  int $value
     * @return string
     */
    public function packBoolean(int $value): string
    {
        $value = $value ? 1 : 0;
        return pack('C', $value);
    }

    /**
     * Packs signed 8 bit integer into binary string.
     *
     * @param  int    $value
     * @return string
     */
    public function packShortShortInt(int $value): string
    {
        if ($value < -128 || $value > 127 ) {
            throw new InvalidArgumentException(
                "packShortShortInt method must receives values from -128 to 
                127. {$value} given."
            );
        }

        return pack('c', $value);
    }

    /**
     * Packs unsigned 8 bit integer into binary string.
     *
     * @param  int    $value
     * @return string
     */
    public function packShortShortUint(int $value): string
    {
        if ($value < 0 || $value > 255 ) {
            throw new InvalidArgumentException(
                "packShortShortUint method must receives values from 0 to 255.
                 {$value} given."
            );
        }

        return pack('C', $value);
    }

    /**
     * Packs 16 bit signed integer into binary string.
     *
     * @param  int    $value
     * @return string
     */
    public function packShortInt(int $value): string
    {
        if ($value < -32768 || $value > 32767 ) {
            throw new InvalidArgumentException(
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
     * @param  int          $value
     * @return string
     */
    public function packShortUint(int $value): string
    {
        if ($value < 0 || $value > 65535) {
            throw new InvalidArgumentException(
                "packShortUint method must receives values from 0 to 65535. 
                {$value} given.");
        }

        return pack('n', $value);
    }


}