<?php

namespace MonsterMQ\Support;

/**
 * This class used by client in order to log or output description of execution
 * process being ran.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Logger
{
    /**
     * Resource used by writing functions.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Logger constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (php_sapi_name() == 'cli') {
            $this->resource = fopen('php://stdout', 'w');
        } else {
            $currentYear = (new \DateTime())->format('Y');
            $currentMonth= (new \DateTime())->format('F');
            $logFilePath = dirname(__DIR__)
                .DIRECTORY_SEPARATOR.'Log'
                .DIRECTORY_SEPARATOR.$currentYear
                .DIRECTORY_SEPARATOR.$currentMonth;
            $this->resource = @fopen($logFilePath, 'a');
        }
    }

    /**
     * Writes process description message to log file or outputs to cli.
     *
     * @param string $message Message to write.
     */
    public function write(string $message)
    {
        $message = $this->prepareMessage($message);
        fwrite($this->resource, $message);
    }

    protected function prepareMessage(string $message)
    {
        $date = (new \DateTime())->format('Y-m-d H:i:s');
        $message = "[{$date}] ".$message."\n";
        return $message;
    }
}