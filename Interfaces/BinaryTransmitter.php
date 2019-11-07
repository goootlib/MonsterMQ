<?php


namespace MonsterMQ\Interfaces;


interface BinaryTransmitter
{
    /**
     * Enables data buffering.
     */
    public function enableBuffering();

    /**
     * Disables data accumulation in buffer.
     */
    public function disableBuffering();

    /**
     * Returns length of accumulated data.
     */
    public function bufferLength(): int;

    /**
     * Translates and sends unsigned integer as 8 bits.
     *
     * @param int $number Must be between 0 and 255.
     */
    public function sendOctet(int $number);

    /**
     * Translates and sends unsigned integer as 16 bits.
     *
     * @param int $number Must be between 0 and 65535.
     */
    public function sendShort(int $number);

    /**
     * Translates and sends unsigned integer as 32 bits.
     *
     * @param int $number Must be between 0 and 2^32-1.
     */
    public function sendLong(int $number);

    /**
     * Receives bit from network. AMQP converts bits into bytes. So we need to do backward conversion.
     *
     * @return int Bit read from network.
     */
    public function receiveBit (): int;

    /**
     * Receives 8 bits from network and translates it to unsigned integer.
     *
     * @return int
     */
    public function receiveOctet(): int;

    /**
     * Receives 16 bits from network and translates it to unsigned integer.
     *
     * @return int
     */
    public function receiveShort(): int;

    /**
     * Receives 32 bits from network and translates it to unsigned integer.
     *
     * @return int
     */
    public function receiveLong(): int;

    /**
     * Receives 64 bits from network and translates it to unsigned integer.
     *
     * @return int
     */
    public function receiveLongLong(): int;

    /**
     * Reads 256-byte maximum string from network. Short string type contains first octet which indicates
     * the length of string.
     *
     * @return string
     */
    public function receiveShortStr(): string;

    /**
     * Reads 2^32 maximum length string. Long strings contain first 32 bits which indicating the length of string.
     *
     * @return string
     */
    public function receiveLongStr(): string;

    /**
     * Receives Field Table parameter from server response.
     *
     * @param bool $returnSize Whether to return size of returning data.
     * @return array           Associative array representing
     * field table.
     */
    public function receiveFieldTable($returnSize = false): array;

    /**
     * Reads raw, untranslated data from network.
     *
     * @param int $bytes Amount of data to read.
     * @return string    Untranslated raw data.
     */
    public function receiveRaw($bytes): string;

    /**
     * Sends raw untranslated data through network.
     *
     * @param string $data Data to be sent.
     * @return int         Amount of data has been sent.
     */
    public function sendRaw($data): int;
}