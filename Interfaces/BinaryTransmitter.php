<?php


namespace MonsterMQ\Interfaces;


interface BinaryTransmitter
{
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
     * Sends string up to 256 bytes length.
     *
     * @param string $value
     */
    public function sendShortStr(string $value);

    /**
     * Sends strings up to 2^32 bits length.
     *
     * @param string $value Value to be sent.
     */
    public function sendLongStr(string $value);

    /**
     * Sends AMQP field table translated from php array.
     *
     * @param array $dataArray
     */
    public function sendFieldTable(array $dataArray);

    /**
     * Receives byte from network. AMQP converts bits into bytes. So first we
     * need to convert byte into bit.
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
     * Reads 256-byte maximum string from network. Short string type contains
     * first octet which indicates the length of string.
     *
     * @return string
     */
    public function receiveShortStr(): string;

    /**
     * Reads 2^32 maximum length string. Long strings contain first 32 bits
     * which indicating the length of string.
     *
     * @return string
     */
    public function receiveLongStr(): string;

    /**
     * Receives Field Table parameter from server response.
     *
     * @param bool $returnSize Whether to return size of returning data.
     *
     * @return array Associative array representing field table.
     */
    public function receiveFieldTable(bool $returnSize = false): array;

    /**
     * Sends data accumulated in buffer and clears it.
     */
    public function sendBuffer();

    /**
     * Receives binary data into buffer.
     *
     * @param int $bytes Amount of data to be received.
     */
    public function receiveIntoBuffer(int $bytes);

    /**
     * Enables data buffering.
     */
    public function enableBuffering();

    /**
     * Disables data accumulation in buffer.
     */
    public function disableBuffering();

    /**
     * Whether buffering enabled or not.
     *
     * @return bool
     */
    public function bufferingEnabled(): bool;

    /**
     * Returns length of accumulated data.
     *
     * @return int
     */
    public function bufferLength(): int;

    /**
     * Reads raw, untranslated data from network.
     *
     * @param int $bytes Amount of data to read.
     *
     * @return string Untranslated raw data.
     */
    public function receiveRaw(int $bytes): string;

    /**
     * Sends raw untranslated data through network.
     *
     * @param string $data Data to be sent.
     *
     * @return int Amount of data has been sent.
     */
    public function sendRaw(string $data): ?int;
}