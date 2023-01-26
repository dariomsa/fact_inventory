<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * AUTHOR : DM
 */

namespace App\Http\Clases;

require_once 'lib/nusoap.php';



class ConexionSRIClassDM {

    public function recepcion() {
		
		

		$xml=file_get_contents('/var/www/html/public/Documentos/nuev10.xml');	
		$xml64=(base64_encode($xml));
		
        $param = ["xml" => $xml64 ];
		
		try {
		$parametros = new \stdClass();
		$parametros->xml = $xml;
   
								
							  
        $client = new \SoapClient('https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl');
          $result = $client->validarComprobante($parametros);
		dd($result);
 
            }
        catch(SoapFault $e)
        {
         echo $e->getMessage();
        }
        	
		
    }

   
    public function autorizar($claveAcceso) {
		
		$Configuracion = ConfiguracionModel::where('dato','ambiente')->get();
		$ambiente = 1;
		
		foreach($Configuracion as $conf){
			$ambiente=$conf->valor;
		}
		
		if($ambiente==1){
			//Desarrollo
			 $client2 = new \nusoap_client(\Config::get('webService.autorizarDesa'),TRUE);
			 
		}else{
			if($ambiente==2){
				//Produccion
				//$client2 = new \nusoap_client('https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantes?wsdl',TRUE);
				$client2 = new \nusoap_client(\Config::get('webService.autorizarPro'),TRUE);
				
			}
		}       

        $err = $client2->getError();
		
        if ($err) {
            echo 'Error en Constructor' . $err;
        }

        $param = array('claveAccesoComprobante' => $claveAcceso);
		//echo($client2->getProxyClassCode());
		/*\Log::useDailyFiles(storage_path().'/logs/SRIAuto.log');
		\Log::error(['Parametros'=>$param]);*/
		
       	$response= $client2->call('autorizacionComprobante', $param);
		
		/*\Log::useDailyFiles(storage_path().'/logs/SRIAuto.log');
		\Log::error(['Respuesta'=>$response]);*/

        return $response;

        
    }

}
