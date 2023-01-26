<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Clases\EnvioSRIClass;

class envioSri2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'envioSri2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'envioSri2';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "Hola enviador\n";
        $EnvioSRI = new EnvioSRIClass();
        $EnvioSRI->envioSRI();
        echo "chao enviador\n";

    }
}