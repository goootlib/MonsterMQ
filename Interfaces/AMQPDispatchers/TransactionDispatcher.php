<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


interface TransactionDispatcher
{
    public function sendSelect(int $channel);

    public function receiveSelectOk();

    public function sendCommit(int $channel);

    public function receiveCommitOk();

    public function sendRollback(int $channel);

    public function receiveRollbackOk();
}