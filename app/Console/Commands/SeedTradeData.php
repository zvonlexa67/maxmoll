<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedTradeData extends Command
{
    protected $signature = 'seed:trade';
    protected $description = 'Заполнить БД складами, товарами и остатками';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('db:seed');
        $this->info('Торговые данные успешно заполнены!');
    }
}
