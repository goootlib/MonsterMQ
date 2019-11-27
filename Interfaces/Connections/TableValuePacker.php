<?php


namespace MonsterMQ\Interfaces\Connections;


interface TableValuePacker
{
    public function packFieldTableValue(string $valueType, $value): ?string;
}