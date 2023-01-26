<?php

namespace App\Http\Clases;

use App\Http\Models\ClienteModel;
use App\Http\Clases\PhpmailClass;

class ClienteClass {

	public function verificarCliente($documento){

		//try{
			$xml= \simplexml_load_string($documento);
		  

			$codDoc=$xml->infoTributaria->codDoc;
			$ruc='';
			$razonSocial='';
			$tipoIdentificacion='';
			$correo='';

			 for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
				if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
					$correo = "" . $xml->infoAdicional->campoAdicional[$i];
				}
			}
			$correoArray=explode(';',$correo);
			
		    \Log::useDailyFiles(storage_path().'/logs/cliente.log');
			\Log::error(['Cliente ERP Class Actualiza Correo'=>"Verificar", 'documento'=>$codDoc]);
			
			switch ($codDoc) {

				case '01':
					$ruc="".$xml->infoFactura->identificacionComprador;
					$razonSocial="".$xml->infoFactura->razonSocialComprador;
					$tipoIdentificacion="".$xml->infoFactura->tipoIdentificacionComprador;
					
					#20190415 - DPS - Actualiza información del cliente
					
					$cliente=ClienteModel::where('ruc',$ruc)->first();
					
					if (count($cliente) >0){
										
						if ($cliente->cliente_actualizado == 0){
							
							$cliente->razon_social = $xml->infoFactura->razonSocialComprador;
							$cliente->cliente_actualizado  = 1;
							$cliente->update_cliente = date("Y-m-d H:i:s");
							$cliente->save();
						}
					}
					#FIN - 20190415 - DPS - Actualiza información del cliente
					
					break;

				case '04':
					$ruc="".$xml->infoNotaCredito->identificacionComprador;
					$razonSocial="".$xml->infoNotaCredito->razonSocialComprador;
					$tipoIdentificacion="".$xml->infoNotaCredito->tipoIdentificacionComprador;
					
					#20190415 - DPS - Actualiza información del cliente
					
					$cliente=ClienteModel::where('ruc',$ruc)->first();
					if (count($cliente) >0){
						if ($cliente->cliente_actualizado == 0){
							
							$cliente->razon_social = $xml->infoNotaCredito->razonSocialComprador;
							$cliente->cliente_actualizado  = 1;
							$cliente->update_cliente = date("Y-m-d H:i:s");
							$cliente->save();
						}
					}
					#FIN - 20190415 - DPS - Actualiza información del cliente
					
					break;
				case '05':
					 $ruc="".$xml->infoNotaDebito->identificacionComprador;
					$razonSocial="".$xml->infoNotaDebito->razonSocialComprador;
					$tipoIdentificacion="".$xml->infoNotaDebito->tipoIdentificacionComprador;
					
					#20190415 - DPS - Actualiza información del cliente
					
					$cliente=ClienteModel::where('ruc',$ruc)->first();
					if (count($cliente) >0){
						if ($cliente->cliente_actualizado == 0){
							
							$cliente->razon_social = $xml->infoNotaDebito->razonSocialComprador;
							$cliente->cliente_actualizado  = 1;
							$cliente->update_cliente = date("Y-m-d H:i:s");
							$cliente->save();
						}
					}
					#FIN - 20190415 - DPS - Actualiza información del cliente
					
					break;
				case '07':
					$ruc="".$xml->infoCompRetencion->identificacionSujetoRetenido;
					$razonSocial="".$xml->infoCompRetencion->razonSocialSujetoRetenido;
					$tipoIdentificacion="".$xml->infoCompRetencion->tipoIdentificacionSujetoRetenido;
					
					#20190415 - DPS - Actualiza información del cliente
					
					$cliente=ClienteModel::where('ruc',$ruc)->first();
					if (count($cliente) >0){
						if ($cliente->cliente_actualizado == 0){
							
							$cliente->razon_social = $xml->infoCompRetencion->razonSocialComprador;
							$cliente->cliente_actualizado  = 1;
							$cliente->update_cliente = date("Y-m-d H:i:s");
							$cliente->save();
						}
					}
					#FIN - 20190415 - DPS - Actualiza información del cliente
					
					break;
			}
			//\Log::useDailyFiles(storage_path().'/logs/ClienteERPdes.log');
			//\Log::error(['Cliente ERP Class'=>"Log verificar email Cliente for antes del if", 'count email'=>$cliente]);
			
			$cliente=ClienteModel::where('ruc',$ruc)->first();
			
			if(count($cliente)==0){
				//\Log::useDailyFiles(storage_path().'/logs/ClienteERPdes.log');
				//\Log::error(['Cliente ERP Class'=>"Log verificar email", 'ruc'=>$ruc,'email'=>$correoArray[0]]);
				
				//$adicionales = $this->get_dirtel($xml);
				$Cliente= new ClienteModel();
				$Cliente->ruc=$ruc;
				$Cliente->razon_social=$razonSocial;
				//$Cliente->email=$correoArray[0];
				$Cliente->email=$correo;
				$Cliente->cod=$tipoIdentificacion;
				//$Cliente->direccion=$adicionales[0];
				//$Cliente->telefono=$adicionales[1];
				//\Log::useDailyFiles(storage_path().'/logs/ClienteERPdes.log');
				//\Log::error(['Cliente ERP Class'=>"Log verificar email Cliente", 'Cliente'=>$Cliente]);
				
				$Cliente->save();
				$this->actualiza_dirtel($Cliente->id,$xml);
			}else{ 
			
				$this->actualiza_dirtel($cliente->id,$xml);
				
				//DPS - 20181108 para actualizar correos en base
					//$Cliente = ClienteModel::where('ruc',$ruc)->first();
					
					//$correoArray = explode(';',$cliente->email);
					
					/*foreach ($correoArray as $correo1) {
							
						
							$correo_nue = ";".$correo1;
							$correo_nuevo = $correo_nue;
							
					}*/
					
					$correo_nuevo = $cliente->email.";".$correo;
					
					$correo_nuevo = explode(';',$correo_nuevo);
					$correo_nuevo = array_values(array_unique($correo_nuevo));
					$correo_nuevo = implode(";", $correo_nuevo);
					
					\Log::useDailyFiles(storage_path().'/logs/CorreosActualiza.log');
					\Log::error(['Cliente ERP Class Actualiza Correo'=>"Log verificar email Cliente", 'RUC'=>$cliente->ruc,'Correo Anterior'=>$cliente->email, 'Actualiza correo'=>$correo_nuevo]);
					
					$cliente->email = $correo_nuevo;
					$cliente->save();
					//FIN - DPS - 20181108 para actualizar correos en base
			}
			
        /*}catch(\Exception $e)
		{
			echo "\nentra a catch1\n";
			echo $e->getMessage();
			\Log::useDailyFiles(storage_path().'/logs/ClienteERP.log');
			\Log::error(['Cliente ERP Class'=>"Log verificar email Cliente", 'CATCH'=>$e->getMessage()]);
						
		}*/
	}

    public function registrarMailBienvenida($idCliente){
        $Cliente= ClienteModel::find($idCliente);

        $Cliente->enviado_mail_bienvenida=1;
        $Cliente->save();
    }

    public function actualiza_dirtel($idCliente,$xml){
        //DIRECCION Y TELEFONO JO///
        $telefono = "";
        $direccion = "";
        $codDoc=$xml->infoTributaria->codDoc;
        $campoAdicional1 = ""; $campoAdicional2 = ""; $campoAdicional3 = ""; $campoAdicional4 = ""; $campoAdicional5 = ""; $campoAdicional6 = ""; $campoAdicional7 = ""; $campoAdicional8 = ""; $campoAdicional9 = ""; $campoAdicional10 = ""; $campoAdicional11 = ""; $campoAdicional12 = ""; 
        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInternoSAP = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional1') {
                $campoAdicional1 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional2') {
                $campoAdicional2 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional3') {
                $campoAdicional3 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional4') {
                $campoAdicional4 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional5') {
                $campoAdicional5 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional6') {
                $campoAdicional6 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional7') {
                $campoAdicional7 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional8') {
                $campoAdicional8 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $campoAdicional9 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional10') {
                $campoAdicional10 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional11') {
                $campoAdicional11 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional12') {
                $campoAdicional12 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
                $correo = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }
        $unidadNegocio = $campoAdicional9;
        $correoArray=explode(';',$correo);

        if($codSociedad!='IMC' && $codDoc!='DIG' && $codSociedad!='VAR'){
            $campoAdicional4='';

        }

        switch ($codDoc) {
            case '01':
                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }
                if ($unidadNegocio == 'CLASI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }
                if ($unidadNegocio == 'PUBLI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }

                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                    if(isset($campoAdicional11)){
                        $telefono=$campoAdicional11;
                    }
                }
                break;
            case '04':
                $campoAdicional1Array = explode('|', $campoAdicional1);
                if ($unidadNegocio == 'OPTAT') {
                } else {
                    $direccion = $campoAdicional1Array[3];
                }
                break;
            case '05':
                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                } else {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                }
                break;
            case '07':
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $direccion = $campoAdicional1Array[1];
                $telefono = $campoAdicional2Array[2];
                break;
            default :
                $direccion = "";
                $telefono = "";
                break;
        }

        $Cliente = ClienteModel::find($idCliente);
        $Cliente->direccion = $direccion;
        $Cliente->telefono = $telefono;
        $Cliente->save();
        ///////
    }

    public function get_dirtel($xml){
        //DIRECCION Y TELEFONO JO///
        $telefono = "";
        $direccion = "";
        $codDoc=$xml->infoTributaria->codDoc;
        $campoAdicional1 = ""; $campoAdicional2 = ""; $campoAdicional3 = ""; $campoAdicional4 = ""; $campoAdicional5 = ""; $campoAdicional6 = ""; $campoAdicional7 = ""; $campoAdicional8 = ""; $campoAdicional9 = ""; $campoAdicional10 = ""; $campoAdicional11 = ""; $campoAdicional12 = ""; 
        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInternoSAP = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional1') {
                $campoAdicional1 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional2') {
                $campoAdicional2 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional3') {
                $campoAdicional3 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional4') {
                $campoAdicional4 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional5') {
                $campoAdicional5 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional6') {
                $campoAdicional6 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional7') {
                $campoAdicional7 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional8') {
                $campoAdicional8 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $campoAdicional9 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional10') {
                $campoAdicional10 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional11') {
                $campoAdicional11 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional12') {
                $campoAdicional12 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
                $correo = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }
        $unidadNegocio = $campoAdicional9;
        $correoArray=explode(';',$correo);

        if($codSociedad!='IMC' && $codDoc!='DIG' && $codSociedad!='VAR'){
            $campoAdicional4='';

        }

        switch ($codDoc) {
            case '01':
                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }
                if ($unidadNegocio == 'CLASI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }
                if ($unidadNegocio == 'PUBLI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                }

                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                    if(isset($campoAdicional11)){
                        $telefono=$campoAdicional11;
                    }
                }
                break;
            case '04':
                $campoAdicional1Array = explode('|', $campoAdicional1);
                if ($unidadNegocio == 'OPTAT') {
                } else {
                    $direccion = $campoAdicional1Array[3];
                }
                break;
            case '05':
                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                } else {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                }
                break;
            case '07':
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $direccion = $campoAdicional1Array[1];
                $telefono = $campoAdicional2Array[2];
                break;
            default :
                $direccion = "";
                $telefono = "";
                break;
        }

        $retornar = array();
        $retornar[] = $direccion;
        $retornar[] = $telefono;
        return $retornar;

        ///////
    }
}