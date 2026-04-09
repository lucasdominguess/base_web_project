<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanAll extends Command
{
    protected $signature = 'clean:all';
    protected $description = 'Limpa todos os caches do Laravel e autoload do composer';

    public function handle()
    {
        $this->info('Limpando caches do Laravel...');

        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');
        $this->call('clear-compiled');
        $this->call('optimize:clear');

        $this->info('Caches do Laravel limpos.');

        // $this->info('Atualizando autoload do Composer...');
        // exec('composer dump-autoload');

        // $this->info('Autoload atualizado.');
        // $this->info('Tudo limpo com sucesso.');
    }
}
