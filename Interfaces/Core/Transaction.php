<?php


namespace MonsterMQ\Interfaces\Core;


interface Transaction
{
    /**
     * Sets the transaction mode on current channel.
     */
    public function begin();

    /**
     * Commit current transaction. A new transaction starts immediately after
     * a commit.
     */
    public function commit();

    /**
     * Abandons current transaction. A new transaction starts immediately after
     * a rollback.
     */
    public function rollback();
}