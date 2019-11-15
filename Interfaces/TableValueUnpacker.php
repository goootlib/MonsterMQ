<?php


namespace MonsterMQ\Interfaces;


interface TableValueUnpacker
{
    /**
     * Fetches value along with value size from Field Table.
     *
     * @param string $valueType Type of Field Table value to fetch.
     *
     * @return array|null First element contains value, second - its size.
     */
    public function getFieldTableValue(string $valueType): ?array;
}