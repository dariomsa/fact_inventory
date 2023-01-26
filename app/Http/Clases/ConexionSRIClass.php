<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Clases;

//require_once 'nusoap-0.9.5/lib/nusoap.php';
require_once 'lib/nusoap.php';

use App\Http\Models\ConfiguracionModel;

class ConexionSRIClass {

    public function recepcion($documento) {
				
		$xml=file_get_contents($documento);	
		$xml64=(base64_encode($xml));
		
        $param = ["xml" => $xml64 ];
		
		try {
			$parametros = new \stdClass();
			$parametros->xml = $xml;
   						  
   		    $client = new \SoapClient('https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl');
          	$result = $client->validarComprobante($parametros);
			  \Log::info(['TIPO' => "RESPUESTA SRI", 'RESPUESTA' => $result]); 
            }
        catch(SoapFault $e)
        	{
         echo $e->getMessage();
		 \Log::info(['TIPO' => "CATCH ENVIO", 'ERROR' => $e->getMessage()]);
        	}
			
			return $result;
		
    }

   
    public function autorizar($claveAcceso) {
		
	   $param = ["claveAccesoComprobante" => $claveAcceso ];

		try {
				
   		    $client = new \SoapClient('https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl');
          	$result = $client->autorizacionComprobante($param);
			  \Log::info(['TIPO' => "AUTORIZACION SRI", 'RESPUESTA' => $result]); 
            }
        catch(SoapFault $e)
        	{
         echo $e->getMessage();
		 \Log::info(['TIPO' => "CATCH ENVIO", 'ERROR' => $e->getMessage()]);
        	}
		
        return $result;
        
    }

}
