<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Clases;

class GeneralClass {

    private $mensaje;

    public function __construct() {
        $this->mensaje = '';
    }

    public function validarXmlCorrecto($xml) {
        $returnValue = array();
        $returnValue[0] = TRUE;
        $returnValue[1] = '';
        $mensaje = '';
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($xml);
        if ($sxe === false) {

            foreach (libxml_get_errors() as $error) {
                $mensaje.= $error->message . "/";
            }
            $mensaje = substr($mensaje, 0, strlen($mensaje) - 1);
            $returnValue[0] = FALSE;
            $returnValue[1] = $mensaje;
        }
        return $returnValue;
    }

    public function espaciosBlanco($doc) {
        $xml = \simplexml_load_string($doc, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        /* echo "\n\n\n\n";

          var_dump($array);

          die();
         */


        $keys = array_keys($array);

        $returnValue[0] = TRUE;
        $returnValue[1] = '';

        $this->mensaje = '';

        $this->verInfoTags($array);


        $this->verContenidoTags($doc);

        $this->verificarCorreo($doc);


        if ($this->mensaje != '') {
            $returnValue[0] = FALSE;
            $returnValue[1] = $this->mensaje;
        }

        return $returnValue;
    }

    private function verificarCorreo($doc) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($doc);
        $campoAdicional = $xml->infoAdicional->campoAdicional;
        for ($i = 0; $i < count($campoAdicional); $i++) {
            if ($campoAdicional[$i]->attributes() == 'CorreoCliente') {
                $correo = "" . $campoAdicional[$i];
                $correoArray = explode(";", $correo);
                for ($j = 0; $j < count($correoArray); $j++) {
                    if ($correoArray[$j] < trim($correoArray[$j])) {
                        $this->mensaje.='Error en el tag correo|';
                    }
                }
            }
        }
    }

    private function verInfoTags($array) {

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                //si es un array sigo recorriendo
                if (count($value) == 0) {
                    $this->mensaje.="Error en el tag " . $key . "|";
                } else {
                    $this->verInfoTags($value);
                }
            } else {
                //si es un elemento lo muestro



                if ($value == "" || substr($value, 0, 1) == " " || substr($value, 0, 1) == "." || $value < trim($value)) {
                    $this->mensaje.="Error en el tag " . $key . " " . $value . "|";
                }
            }
        }
    }

    private function verContenidoTags($doc) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($doc);
        $infoTributaria = $xml->infoTributaria;
        $infoAdicional = $xml->infoAdicional;
        //$detalles=$xml->detalles;
        if (count($infoTributaria) > 0) {
            $codDoc = $xml->codDoc;

            if (count($infoAdicional) > 0) {

                switch ($codDoc) {
                    case '01':
                        $info = $xml->infoFactura;
                        if (count($info) == 0) {
                            $this->mensaje.="Error en el tag infoFactura|";
                        }

                        break;
                    case '04':
                        $info = $xml->infoNotaCredito;
                        if (count($info) == 0) {
                            $this->mensaje.="Error en el tag infoNotaCredito|";
                        }
                        break;
                    case '05':
                        $info = $xml->infoNotaDebito;
                        if (count($info) == 0) {
                            $this->mensaje.="Error en el tag infoNotaDebito|";
                        }
                        break;
                    case '07':
                        $info = $xml->infoCompRetencion;
                        if (count($info) == 0) {
                            $this->mensaje.="Error en el tag infoCompRetencion|";
                        }
                        break;
                }

                /*  if(count($detalles)==0 && $codDoc!='07'){
                  $this->mensaje.="Error en el tag detalles|";
                  }
                 */
            } else {
                $this->mensaje.="Error en el tag infoAdicional|";
            }
        } else {
            $this->mensaje.="Error en el tag infoTributaria|";
        }
    }

    public function generarClaveAcceso($doc) {
        $xml = new \SimpleXMLElement($doc, null, true);
        $clave = "";
        $fechaEmision;

        $codDoc = "" . $xml->infoTributaria->codDoc;
        $ruc = "" . $xml->infoTributaria->ruc;
        $secuencial = "" . $xml->infoTributaria->secuencial;
        $serie = "" . $xml->infoTributaria->estab . "" . $xml->infoTributaria->ptoEmi;



        switch ($codDoc) {

            case '01':
                $fechaEmision = str_replace('/', '', $xml->infoFactura->fechaEmision);
                break;

            case '04':
                $fechaEmision = str_replace('/', '', $xml->infoNotaCredito->fechaEmision);
                break;
        }


        $clave.=$fechaEmision;

        $clave.=$codDoc;

        $clave.=$ruc;

        $clave.=$this->ambiente;

        $clave.=$serie;

        $clave.=$secuencial;

        $clave.=$this->codigoNumerico;

        $clave.=$this->emision;
        $digitoVerificador = $this->obtenerModulo11($clave);

        $clave.=$digitoVerificador;




        return $clave;
    }

    private function obtenerModulo11($x_claveAcceso) {
        $x = 2;
        $sumatorio = 0;
        for ($i = strlen($x_claveAcceso) - 1; $i >= 0; $i--) {
            if ($x > 7) {
                $x = 2;
            }
            $sumatorio = $sumatorio + ($x_claveAcceso[$i] * $x);
            $x++;
        }
        $digito = bcmod($sumatorio, 11);
        $digito = 11 - $digito;

        switch ($digito) {
            case 10:
                $digito = "1";
                break;
            case 11:
                $digito = "0";
                break;
        }

        return $digito;
    }

    public function validarValores($documento) {
        if ($this->verificarValoresDetalle($documento)) {
            echo "Paso";
        } else {
            echo "No paso";
        }
    }

    private function comprobarImporteTotal($documento) {
        /* $xml= \simplexml_load_string($documento);

          $codDoc="".$xml->infoTributaria->codDoc;


          switch ($codDoc) {

          case '01':
          $totalSinImpuestos=(double)($xml->infoFactura);



          break;

          case '04':
          $fechaEmision = str_replace('/', '', $xml->infoNotaCredito->fechaEmision);
          $this->docType = 'NC';
          break;
          case '05':
          $fechaEmision = str_replace('/', '', $xml->infoNotaDebito->fechaEmision);
          $this->docType = 'ND';
          break;
          case '07':
          $fechaEmision = str_replace('/', '', $xml->infoCompRetencion->fechaEmision);
          $this->docType = 'CR';
          break;
          } */
    }

    private function verificarValoresDetalle($documento) {
        $xml = \simplexml_load_string($documento);

        $detalle = $xml->detalles->detalle;



        for ($i = 0; $i < count($detalle); $i++) {
            $cantidad = (int) ("" . $detalle[$i]->cantidad);
            $precioUnitario = (double) ("" . $detalle[$i]->precioUnitario);
            $descuento = (double) ("" . $detalle[$i]->descuento);
            $precioTotal = (double) ("" . $detalle[$i]->precioTotalSinImpuesto);

            if (($cantidad * $precioUnitario) - $descuento != $precioTotal) {
                return false;
            }
        }
        return true;
    }

    public function validarErrores($doc) {
        $margenPositivo = 0.01;
        $margenNegativo = -0.01;


        $xml = simplexml_load_string($doc);
        $valido = true;
        $mensaje = '';

        $codDoc = "" . $xml->infoTributaria->codDoc;
        switch ($codDoc) {
            case '01':
                $etiqueta = 'infoFactura';
                break;
            case '04':
                $etiqueta = 'infoNotaCredito';
                break;
            case '05':
                $etiqueta = 'infoNotaDebito';
                break;
            case '06':
                $etiqueta = 'infoGuiaRemision';
                break;
            case '07':
                $tipoIdentificacionComprador = "" . $xml->{$etiqueta}->tipoIdentificacionSujetoRetenido;
                $identificacionComprador = "" . $xml->{$etiqueta}->identificacionSujetoRetenido;
                $etiqueta = 'infoCompRetencion';
                break;
        }
        $tipoIdentificacionComprador = "" . $xml->{$etiqueta}->tipoIdentificacionComprador;
        $identificacionComprador = "" . $xml->{$etiqueta}->identificacionComprador;

//VERIFICAR CEDULA Y RUC        


        switch ($tipoIdentificacionComprador) {
            case '05':
                if (strlen($identificacionComprador) != 10) {
                    $mensaje.="<br/>" . 'Inconsistencia en identificacin';
                } else {
                    $strCedula = $identificacionComprador;
                    if (is_null($strCedula) || empty($strCedula)) {//compruebo si que el numero enviado es vacio o null
                        $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                    } else {
                        if (is_numeric($strCedula)) {
                            $total_caracteres = strlen($strCedula); // se suma el total de caracteres
                            if ($total_caracteres == 10) {//compruebo que tenga 10 digitos la cedula
                                $nro_region = substr($strCedula, 0, 2); //extraigo los dos primeros caracteres de izq a der
                                if ($nro_region >= 1 && $nro_region <= 24) {// compruebo a que region pertenece esta cedula//
                                     $ult_digito = substr($strCedula, -1, 1); //extraigo el ultimo digito de la cedula
		//extraigo los valores pares//
									$valor2 = substr($strCedula, 1, 1);
									$valor4 = substr($strCedula, 3, 1);
									$valor6 = substr($strCedula, 5, 1);
									$valor8 = substr($strCedula, 7, 1);
									$suma_pares = ($valor2 + $valor4 + $valor6 + $valor8);
		//extraigo los valores impares//
									$valor1 = substr($strCedula, 0, 1);
									$valor1 = ($valor1 * 2);
									if ($valor1 > 9) {
										$valor1 = ($valor1 - 9);
									} else {
										
									}
									$valor3 = substr($strCedula, 2, 1);
									$valor3 = ($valor3 * 2);
									if ($valor3 > 9) {
										$valor3 = ($valor3 - 9);
									} else {
										
									}
									$valor5 = substr($strCedula, 4, 1);
									$valor5 = ($valor5 * 2);
									if ($valor5 > 9) {
										$valor5 = ($valor5 - 9);
									} else {
										
									}
									$valor7 = substr($strCedula, 6, 1);
									$valor7 = ($valor7 * 2);
									if ($valor7 > 9) {
										$valor7 = ($valor7 - 9);
									} else {
										
									}
									$valor9 = substr($strCedula, 8, 1);
									$valor9 = ($valor9 * 2);
									if ($valor9 > 9) {
										$valor9 = ($valor9 - 9);
									} else {
										
									}

									$suma_impares = ($valor1 + $valor3 + $valor5 + $valor7 + $valor9);
									$suma = ($suma_pares + $suma_impares);
									if(strlen($suma)>1){
										$dis = substr($suma, 0, 1); //extraigo el primer numero de la suma
										$dis = (($dis + 1) * 10); //luego ese numero lo multiplico x 10, consiguiendo asi la decena inmediata superior
									}else{
										$dis = 10;    
									}
									$digito = ($dis - $suma);
									if ($digito == 10) {
										$digito = '0';
									} else {
										
									}//si la suma nos resulta 10, el decimo digito es cero
									//echo $digito;
									if ($digito == $ult_digito) {//comparo los digitos final y ultimo
                                        //echo "Cedula Correcta";
                                    } else {
										\Log::useDailyFiles(storage_path().'/logs/Algoritmo.log');
										\Log::error(['GeneralClass'=>"Log verificar algoritmo", 'SUMA'=>$suma,'DIS'=>$dis,'Digito'=>$digito,'ult_digito'=>$ult_digito]);
                                        $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                                    }
                                } else {
                                    //echo "Este Nro de Cedula no corresponde a ninguna provincia del ecuador";
                                    $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                                }
                            } else {
                                //echo "Es un Numero y tiene solo".$total_caracteres;
                                $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                            }
                        } else {
                            //echo "Esta Cedula no corresponde a un Nro de Cedula de Ecuador";
                            $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                        }
                    }
                }
                break;
            case '04':
                if (strlen($identificacionComprador) != 13)
                    $mensaje.="<br/>" . 'Inconsistencia en identificacion';
                break;
            default:
                # code...
                break;
        }
//FIN VERIFICAR CEDULA Y RUC
//VERIFICAR FORMAS DE PAGO


        if ($codDoc == '01') {
            if (isset($xml->{$etiqueta}->pagos)) {
                $pagos = $xml->{$etiqueta}->pagos->pago;
                $sumaPagos = 0;
                foreach ($pagos as $pago) {
                    $sumaPagos+=(double) $pago->total;
                }
				$sumaPagos=round($sumaPagos,2);
				$importeTotal=(double) $xml->{$etiqueta}->importeTotal;
				$importeTotal=round($importeTotal,2);

                /*if (round($sumaPagos, 2) != round((double) $xml->{$etiqueta}->importeTotal, 2)) {
                    $totalCalculado = round((double) $xml->{$etiqueta}->importeTotal, 2);
                    if (round($sumaPagos, 2) - $totalCalculado != $margenPositivo) {
                        if (round($sumaPagos, 2) - $totalCalculado != $margenNegativo) {
                            $mensaje.="<br/>" . 'Inconsistencia en formas de pago';
                        }
                    }
                }*/
				if($sumaPagos!=$importeTotal){
					$diferencia=round(($sumaPagos-$importeTotal),2);
					if($diferencia!= $margenPositivo){
						if($diferencia!=$margenNegativo){
							$mensaje.="<br/>" . 'Inconsistencia en formas de pago';
							
							
						}
					}
				}
            }
			
        }
//FIN VERIFICAR FORMAS DE PAGO

        /*$totalSinImpuestos = (double) ($xml->{$etiqueta}->totalSinImpuestos);
        $totalDescuento = (double) ($xml->{$etiqueta}->totalDescuento);
        $sumatoriaImporteTotal = 0;
		*/


/// CALCULO PARA VERIFICAR LA SUMA DE LOS IMPORTES TOTALES 

        switch ($codDoc) {
            case '01':
				$totalSinImpuestos = round((double) ($xml->{$etiqueta}->totalSinImpuestos),2);
				$totalDetalleSinImpuesto=0;
				$valorImpuestos=0;
				$importeTotal=round((double)$xml->{$etiqueta}->importeTotal,2);
                
				foreach($xml->detalles->detalle as $det){
					$totalDetalleSinImpuesto+=(double)$det->precioTotalSinImpuesto;
					//\Log::useDailyFiles(storage_path().'/logs/Calculos.log');
					//\Log::error(['GeneralClass'=>"Log verificar calculos en el foreach", 'detalle general'=>$det, 'totalDetalleSinImpuesto foreach'=>$totalDetalleSinImpuesto, 'detalle'=>$det->precioTotalSinImpuesto]);
				
				}
				$totalDetalleSinImpuesto=round($totalDetalleSinImpuesto,2);
				$diferencia=$totalSinImpuestos-$totalDetalleSinImpuesto;
				$diferencia=round($diferencia,2);
				
				//\Log::useDailyFiles(storage_path().'/logs/Calculos.log');
				//\Log::error(['GeneralClass'=>"Log verificar calculos", 'totalDetalleSinImpuesto'=>$totalDetalleSinImpuesto, 'totalSinImpuestos'=>$totalSinImpuestos]);
				
				//if($totalDetalleSinImpuesto!=$totalSinImpuestos){
				//	if($diferencia!=$margenPositivo){
				//		if($diferencia!=$margenNegativo){
				//			$mensaje.="<br/>" . 'Diferencia de totales Detalles con total';
				//		}
				//	}
				//}
				
				foreach($xml->{$etiqueta}->totalConImpuestos->totalImpuesto as $impuesto){
					$valorImpuestos+=(double)$impuesto->valor;
				}
				$valorImpuestos=round($valorImpuestos,2);
				
				$totalCalculado=round(($totalSinImpuestos+$valorImpuestos),2);
				$diferencia=$totalCalculado-$importeTotal;
				$diferencia=round($diferencia,2);
				//if($totalCalculado!=$importeTotal){
				//	if($diferencia!=$margenPositivo){
				//		if($diferencia!=$margenNegativo){
				//			$mensaje.="<br/>" . 'Diferencia de totales';
				//		}
				//	}
				//}

                break;
            case '04':
				
				$totalSinImpuestos = round((double) ($xml->{$etiqueta}->totalSinImpuestos),2);
				$totalDetalleSinImpuesto=0;
				$valorImpuestos=0;
				$importeTotal=round((double)$xml->{$etiqueta}->valorModificacion,2);
                
				foreach($xml->detalles->detalle as $det){
					$totalDetalleSinImpuesto+=(double)$det->precioTotalSinImpuesto;
				}
				$totalDetalleSinImpuesto=round($totalDetalleSinImpuesto,2);
				$diferencia=$totalSinImpuestos-$totalDetalleSinImpuesto;
				$diferencia=round($diferencia,2);
				
				if($totalDetalleSinImpuesto!=$totalSinImpuestos){
					if($diferencia!=$margenPositivo){
						if($diferencia!=$margenNegativo){
							$mensaje.="<br/>" . 'Diferencia de totales Detalles con total';
						}
					}
				}
				
				foreach($xml->{$etiqueta}->totalConImpuestos->totalImpuesto as $impuesto){
					$valorImpuestos+=(double)$impuesto->valor;
				}
				$valorImpuestos=round($valorImpuestos,2);
				
				$totalCalculado=round(($totalSinImpuestos+$valorImpuestos),2);
				$diferencia=$totalCalculado-$importeTotal;
				$diferencia=round($diferencia,2);
				if($totalCalculado!=$importeTotal){
					if($diferencia!=$margenPositivo){
						if($diferencia!=$margenNegativo){
							$mensaje.="<br/>" . 'Diferencia de totales';
						}
					}
				}
                
				

                break;
            case '05':
                $totalSinImpuestos = round((double) ($xml->{$etiqueta}->totalSinImpuestos),2);
				$valorImpuestos=0;
				$importeTotal=round((double)$xml->{$etiqueta}->valorTotal,2);
				foreach($xml->{$etiqueta}->impuestos->impuesto as $impuesto){
					$valorImpuestos+=(double)$impuesto->valor;
				}
				$valorImpuestos=round($valorImpuestos,2);
				$totalCalculado=round(($totalSinImpuestos+$valorImpuestos),2);
				$diferencia=$totalCalculado-$importeTotal;
				$diferencia=round($diferencia,2);
				if($totalCalculado!=$importeTotal){
					if($diferencia!=$margenPositivo){
						if($diferencia!=$margenNegativo){
							$mensaje.="<br/>" . 'Diferencia de totales';
						}
					}
				}			
				
                break;
            case '07':
                
                break;
            case '06':
                break;
            default:
                $sumatoriaTotalSinImpuesto = 0;
                $sumatoriaTotalDescuento = 0;
                foreach ($xml->detalles->detalle as $detalle) {
                    foreach ($detalle->impuestos->impuesto as $impuesto) {
                        if ("" . $impuesto->codigo == "2") {
                            $sumatoriaTotalSinImpuesto+=(double) $impuesto->baseImponible + (double) $impuesto->valor;
                        } elseif ("" . $impuesto->codigo == "0") {
                            $sumatoriaTotalDescuento+=(double) $impuesto->baseImponible + (double) $impuesto->valor;
                        }
                    }
                }
                if (round($sumatoriaTotalSinImpuesto + $sumatoriaTotalDescuento, 2) != round($sumatoriaImporteTotal, 2)) {
                    $totalPrevisto = round($sumatoriaTotalSinImpuesto + $sumatoriaTotalDescuento, 2);
                    $totalCalculado = round($sumatoriaImporteTotal, 2);
                    if ($totalPrevisto - $totalCalculado != $margenPositivo) {
                        if ($totalPrevisto - $totalCalculado != $margenNegativo) {
                            $mensaje.="<br/>" . 'Error al verificar valores en detalle';
                        }
                    }
                }
                break;
        }
        if ($mensaje == '') {
            return json_encode(array('ver' => true, 'mensaje' => ''));
        } else {
			
            return json_encode(array('ver' => false, 'mensaje' => $mensaje));
        }
    }

}
