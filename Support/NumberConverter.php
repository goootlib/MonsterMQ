<?php


namespace MonsterMQ\Support;


class NumberConverter
{

    /**
     * Translates Bid-Endian unsigned shorts to signed.
     * Since PHP does not support such unpacking.
     * @param int $shortUnsigned Source unsigned short value.
     * @return int Signed short value.
     */
    public static function toSignedShort (int $unsignedShort)
    {
        if($unsignedShort >= pow(2,15)){
            $signedShort = $unsignedShort - pow(2,16);
        }else{
            return $unsignedShort;
        }

        return $signedShort;
    }

    /**
     * Translatte Big-Endian unsigned longs to signed.
     * Since PHP does not support such unpacking.
     * @param int $unsignedLong Source unsigned long value.
     * @return int Signed long value
     */
    public static function toSignedLong (int $unsignedLong)
    {
        if($unsignedLong >= pow(2,31)){
            $signedLong = $unsignedLong - pow(2,32);
        }else{
            return $unsignedLong;
        }

        return $signedLong;
    }

    /**
     * This function converts integer to a number
     * with floating point. Which used for currency rates,
     * prices etc.
     * @param $places Number of digits after the dot.
     * @param $number Source unconverted integer.
     * @return string|null Decimal number.
     */
    public static function toDecimal($places, $number)
    {
        return bcdiv($number, bcpow(10, $places));
    }
}