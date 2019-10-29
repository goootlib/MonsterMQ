<?php


namespace MonsterMQ\Support;

/**
 * This class represents mapping
 * of field types of field tables which
 * used as arguments of some AMQP methods.
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class FieldType
{
    const BOOLEAN = 't';

    const SHORT_SHORT_INT = 'b';

    const SHORT_SHORT_UINT = 'B';

    const SHORT_INT ='U';

    const SHORT_UINT = 'u';

    const LONG_INT = 'I';

    const LONG_UINT = 'i';

    const FLOAT = 'f';

    const DOUBLE = 'd';

    const DECIMAL = 'D';

    const SHORT_STRING = 's';

    const LONG_STRING = 'S';

    const FIELD_ARRAY = 'A';

    const TIMESTAMP = 'T';

    const FIELD_TABLE = 'F';

    const VOID = 'V';
}