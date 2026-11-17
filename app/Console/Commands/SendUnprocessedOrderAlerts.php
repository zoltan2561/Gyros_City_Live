<?php

namespace App\Console\Commands;

use App\Services\UnprocessedOrderAlerts;
use Illuminate\Console\Command;

class SendUnprocessedOrderAlerts extends Command
{
    protected $signature = 'orders:alert-unprocessed';
    protected $description = 'Email administrators about online orders pending for at least ten minutes';

    public function handle(UnprocessedOrderAlerts $alerts): int
    {
        $result = $alerts->send();
        $this->line(now()->format('Y-m-d H:i:s T') . ' | status=' . $result['status'] . ' | pending=' . $result['count']);
        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
