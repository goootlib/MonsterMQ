<?php


namespace MonsterMQ\Interfaces;


interface TableValuePacker
{
    public function packFieldTableValue(string $valueType, $value): ?string;
}