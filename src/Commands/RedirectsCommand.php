<?php

namespace SmartCms\Redirects\Commands;

use Illuminate\Console\Command;

class RedirectsCommand extends Command
{
    public $signature = 'redirects';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
