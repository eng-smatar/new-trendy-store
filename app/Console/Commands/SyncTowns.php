<?php

namespace App\Console\Commands;

use App\Models\Town;
use App\Services\Mart\MartClient;
use Illuminate\Console\Command;

class SyncTowns extends Command
{
    protected $signature = 'mart:sync-towns';

    protected $description = 'Refresh delivery towns and fees (Mart "city" = district, "state" = town)';

    public function handle(MartClient $mart): int
    {
        $rows = [];

        foreach ($mart->get('states')['data']['cities'] as $district) {
            foreach ($district['states'] as $town) {
                $rows[] = [
                    'id' => $town['id'],
                    'district_id' => $district['id'],
                    'district_name' => trim($district['name']),
                    'name' => trim($town['name']),
                    'fees' => json_encode($town['delivery']['fees'] ?? []),
                    'available' => (bool) ($town['delivery']['available'] ?? false),
                ];
            }
        }

        if (! $rows) {
            $this->error('Mart returned no towns; nothing changed.');

            return self::FAILURE;
        }

        Town::upsert($rows, ['id']);
        Town::whereNotIn('id', array_column($rows, 'id'))->update(['available' => false]);
        $this->info('Synced '.count($rows).' towns.');

        return self::SUCCESS;
    }
}
