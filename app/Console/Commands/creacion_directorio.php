<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class creacion_directorio extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'creacion_directorio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proceso para crear el arbol de directorios por dia';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $anio = date('Y');
        $mes = date('m');
        $dia = date('d');
		echo base_path() . '/public/Documentos/Firmados/' . $anio; 
		
        if (!file_exists(base_path() . '/public/Documentos/Firmados/' . $anio)) {

            mkdir(base_path() . '/public/Documentos/Errados/' . $anio, 0777, true);
            mkdir(base_path() . '/public/Documentos/Firmados/' . $anio, 0777, true);
            mkdir(base_path() . '/public/Documentos/Autorizados/' . $anio, 0777, true);
            mkdir(base_path() . '/public/Documentos/No_Autorizados/' . $anio, 0777, true);
            mkdir(base_path() . '/public/Documentos/Devueltas/' . $anio, 0777, true);
            mkdir(base_path() . '/public/Documentos/Duplicados/' . $anio, 0777, true);
        }

        if (!file_exists(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes)) {

            mkdir(base_path() . '/public/Documentos/Errados/' . $anio . '/' . $mes, 0777, true);
            mkdir(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes, 0777, true);
            mkdir(base_path() . '/public/Documentos/Autorizados/' . $anio . '/' . $mes, 0777, true);
            mkdir(base_path() . '/public/Documentos/No_Autorizados/' . $anio . '/' . $mes, 0777, true);
            mkdir(base_path() . '/public/Documentos/Devueltas/' . $anio . '/' . $mes, 0777, true);
            mkdir(base_path() . '/public/Documentos/Duplicados/' . $anio . '/' . $mes, 0777, true);
        }

        if (!file_exists(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia)) {

            mkdir(base_path() . '/public/Documentos/Errados/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
            mkdir(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
            mkdir(base_path() . '/public/Documentos/Autorizados/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
            mkdir(base_path() . '/public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
            mkdir(base_path() . '/public/Documentos/Devueltas/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
            mkdir(base_path() . '/public/Documentos/Duplicados/' . $anio . '/' . $mes . '/' . $dia, 0777, true);
        }
    }

}