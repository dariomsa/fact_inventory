<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Http\Clases\ConexionSRIClassDM;

class envioSriDM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'envioSriDM';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'envioSriDM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       
        $EnvioSRI = new ConexionSRIClassDM();
        $EnvioSRI->recepcion();
 
    }
}