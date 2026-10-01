<?php

declare(strict_types=1);

namespace spawaskar\Tags\Console\Commands;

use Illuminate\Console\Command;

class TagsCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'tags:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package tags.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Tags placeholder command executed.');

        return self::SUCCESS;
    }
}
