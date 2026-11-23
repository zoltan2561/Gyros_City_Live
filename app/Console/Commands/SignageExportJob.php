<?php

namespace App\Console\Commands;

use App\Services\Signage\SignageExport;
use Illuminate\Console\Command;

class SignageExportJob extends Command
{
    protected $signature = 'signage:export-job {job}';
    protected $description = 'Run one private admin MP4 export in a detached process';

    public function handle(SignageExport $exports): int
    {
        return $exports->run($this->argument('job')) ? self::SUCCESS : self::FAILURE;
    }
}
