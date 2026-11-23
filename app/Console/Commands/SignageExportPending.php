<?php

namespace App\Console\Commands;

use App\Services\Signage\SignageExport;
use Illuminate\Console\Command;

class SignageExportPending extends Command
{
    protected $signature = 'signage:export-pending';
    protected $description = 'Run the reserved admin MP4 export from the host scheduler';

    public function handle(SignageExport $exports): int
    {
        $exports->dispatchPending();
        return self::SUCCESS;
    }
}
