<?php

namespace MeroBug\Logger;

use Throwable;
use Monolog\Logger;
use MeroBug\MeroBug;
use Monolog\Handler\AbstractProcessingHandler;

class MeroBugHandler extends AbstractProcessingHandler
{
    /** @var MeroBug */
    protected $laraBug;

    /**
     * @param MeroBug $laraBug
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(MeroBug $laraBug, $level = Logger::ERROR, bool $bubble = true)
    {
        $this->laraBug = $laraBug;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $this->laraBug->handle(
                $record['context']['exception']
            );

            return;
        }
    }
}
