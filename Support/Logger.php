<?php

namespace MonsterMQ\Support;

use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

/**
 * This class used by client in order to log or output description of execution
 * process being ran.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Logger implements LoggerInterface
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

            $logDirPath = dirname(__DIR__)
                .DIRECTORY_SEPARATOR.'Log'
                .DIRECTORY_SEPARATOR.$currentYear;

            $logFilePath = $logDirPath.DIRECTORY_SEPARATOR.$currentMonth;

            if (!file_exists($logDirPath)) {
                @mkdir($logDirPath, 0777, true);
            }
            $this->resource = @fopen($logFilePath, 'a');
        }
    }

    /**
     * Writes process description message to log file or outputs to cli.
     *
     * @param string $message  Message to write.
     * @param bool   $noFormat Whether to add date and new line transition to
     *                         message.
     *
     * @throws \Exception
     */
    public function write(string $message, $noFormat = false)
    {
        $message = $this->prepareMessage($message, $noFormat);
        @fwrite($this->resource, $message);
    }

    /**
     * @param string $message  Message to prepare.
     * @param bool   $noFormat Whether to add date and new line transition to
     *                         message.
     * @return string          Prepared message.
     *
     * @throws \Exception
     */
    protected function prepareMessage(string $message, $noFormat = false)
    {
        if ($noFormat) {
            return $message;
        }
        $date = (new \DateTime())->format('Y-m-d H:i:s:v');
        $message = "[{$date}] ".$message."\n";
        return $message;
    }
}