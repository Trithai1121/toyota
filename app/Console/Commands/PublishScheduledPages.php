<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

class PublishScheduledPages extends Command
{
    protected $signature = 'pages:publish-scheduled';

    protected $description = 'Publish website pages whose scheduled time has arrived';

    public function handle(): int
    {
        Page::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update([
                'status' => 'published',
                'is_published' => true,
                'updated_at' => now(),
            ]);

        return self::SUCCESS;
    }
}
