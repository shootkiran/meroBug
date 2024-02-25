<?php

namespace MeroBug\Logger;

use Throwable;
use Monolog\Logger;
use MeroBug\MeroBug;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class MeroBugHandler extends AbstractProcessingHandler
{
    /** @var MeroBug */
    protected $meroBug;

    /**
     * @param MeroBug $meroBug
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(MeroBug $meroBug, $level = Logger::ERROR, bool $bubble = true)
    {
        $this->meroBug = $meroBug;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(LogRecord $record): void
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $this->meroBug->handle(
                $record['context']['exception']
            );

            return;
        }
    }
}
