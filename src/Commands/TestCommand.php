<?php

namespace MeroBug\Commands;

use Exception;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'merobug:test {exception?}';

    protected $description = 'Generate a test exception and send it to merobug';

    public function handle()
    {
        try {
            /** @var MeroBug $laraBug */
            $laraBug = app('merobug');

            if (config('merobug.login_key')) {
                $this->info('✓ [Larabug] Found login key');
            } else {
                $this->error('✗ [MeroBug] Could not find your login key, set this in your .env');
            }

            if (config('merobug.project_key')) {
                $this->info('✓ [Larabug] Found project key');
            } else {
                $this->error('✗ [MeroBug] Could not find your project key, set this in your .env');
                $this->info('More information on setting your project key: https://www.merobug.com/docs/how-to-use/installation');
            }

            if (in_array(config('app.env'), config('merobug.environments'))) {
                $this->info('✓ [Larabug] Correct environment found (' . config('app.env') . ')');
            } else {
                $this->error('✗ [MeroBug] Environment (' . config('app.env') . ') not allowed to send errors to MeroBug, set this in your config');
                $this->info('More information about environment configuration: https://www.merobug.com/docs/how-to-use/installation');
            }

            $response = $laraBug->handle(
                $this->generateException()
            );

            if (isset($response->id)) {
                $this->info('✓ [MeroBug] Sent exception to MeroBug with ID: '.$response->id);
            } elseif (is_null($response)) {
                $this->info('✓ [MeroBug] Sent exception to MeroBug!');
            } else {
                $this->error('✗ [MeroBug] Failed to send exception to MeroBug');
            }
        } catch (\Exception $ex) {
            $this->error("✗ [MeroBug] {$ex->getMessage()}");
        }
    }

    public function generateException(): ?Exception
    {
        try {
            throw new Exception($this->argument('exception') ?? 'This is a test exception from the MeroBug console');
        } catch (Exception $ex) {
            return $ex;
        }
    }
}
