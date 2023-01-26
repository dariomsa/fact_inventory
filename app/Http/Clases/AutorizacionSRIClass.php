<?php

namespace App\Http\Clases;

use App\Http\Clases\ConexionSRIClass;
use App\Http\Clases\DocumentoClass;
use App\Http\Models\DocumentoModel;
use App\Http\Models\ConfiguracionModel;
use App\Http\Models\LogsAutorizacionModel;
use App\Http\Clases\PhpmailClass;
use App\Http\Models\RespuestaSRIModel;
use PDF;
use App\Http\Clases\ClienteClass;
use App\Http\Models\HilosModel;
use App\Http\Clases\DocumentoERPClass;
use App\Http\Clases\ClienteERPClass;
use App\Http\Models\TipoDocumentoModel;

class AutorizacionSRIClass {

    public function autorizar($numeroProceso) {
	
        $anio = date("Y");
        $mes = date("m");
        $dia = date("d");

        $fechaActual = date("Y-m-d");
        $fechaMesAnterior = date('Y-m-d', strtotime('-30 days'));
		
		$horaActual=strtotime("now");
		$hora5=strtotime("+5 seconds");

		$sql = ('SELECT codigo_interno, nombre_archivo, enviado_sri, fecha_firma, fecha_envio_sri, estado, path, estado_interno FROM documento WHERE fecha_emision = "'.$fechaActual.'" AND estado <> "AUTORIZADA"');

		$doc_path = \DB::select($sql);
		

		
		
		if (count($doc_path)>0){
			foreach ($doc_path as $docupath){
				
				$actu_docu = DocumentoModel::where('codigo_interno', $docupath->codigo_interno)
										   ->first();

			    $path_docu = 'public/Documentos/Firmados/'.date("Y").'/'.date("m").'/'.date("d").'/'.$actu_docu->nombre_archivo;

				$actu_docu->enviado_sri = 1;
				$actu_docu->fecha_envio_sri = $actu_docu->fecha_firma;
				$actu_docu->estado = 'RECIBIDA';
				$actu_docu->path = $path_docu;
				$actu_docu->estado_interno = 'Firmado';
				$actu_docu->save();
				
			}
		}


		DocumentoModel::whereIn('estado', ['RECIBIDA','Procesando a SRI','DEVUELTA'])
		    		  ->where('enviado_sri', 1)
					  ->where('contingencia', '0')
					  ->whereBetween('fecha_emision', [$fechaMesAnterior, $fechaActual])
					  ->where('hilo','0')
					  ->take(40)
					  //->sharedLock()
					  ->update(["hilo"=>$numeroProceso]);

		//$querys = \DB::getQueryLog();
		
		$documentos = DocumentoModel::whereIn('estado', ['RECIBIDA','Procesando a SRI','DEVUELTA'])
									->where('enviado_sri', 1)
									->where('contingencia', '0')
									->whereBetween('fecha_emision', [$fechaMesAnterior, $fechaActual])
									//->where('hilo', $numeroProceso)
									->take(40)
									->get();
									
										
	

        if(count($documentos)>0){

          
			
			foreach ($documentos as $docs) {
				try{


				$Documento = new DocumentoClass();
	
								
		
			$ConexionSRI = new ConexionSRIClass();
			
			
            $idDocumento = $docs->id;
            $path = base_path() . "/" . $docs->path;
            $archivo = $docs->nombre_archivo;
            $claveAcceso = $docs->clave_acceso;
            $fechaEmision = $docs->fecha_emision;
            $codInterno = $docs->codigo_interno;
            $nombreDocumento = $docs->nombre_archivo;
            $razonSocialCliente = 'DM';
            $rucCliente = '1716656952';
			
      
			
            if (is_file($path)) {
				
		

                $xml = simplexml_load_file(base_path() . '/' . $docs->path);
                $correoArray = array('sincorreo@elcomercio.com');
                $correo = '';
                for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {

                    if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
                        $correo = "" . $xml->infoAdicional->campoAdicional[$i];
                    }
                }

                $correoArray = explode(';', $correo);

                $Documento->registrarLog($idDocumento, 'Enviado a SRI para autorizacion');
                $Documento->registrarEstadoAutorizacion($idDocumento, 'Enviado a Autorizar', '',$docs->codigo_interno);
				
					
                $resultAutorizado = $ConexionSRI->autorizar($claveAcceso);
				
				//dd($resultAutorizado);

				unset($ConexionSRI);
				
	            //echo json_encode($resultAutorizado->RespuestaAutorizacionComprobante->claveAccesoConsultada);
				//die();
				
                $numeroComprobantes = 0;
                $numeroAutorizacion = '';
                $fechaAutorizacion = '';
                $comprobante = '';
                $estado = 'SIN AUTORIZACION';
				
                if (isset($resultAutorizado->RespuestaAutorizacionComprobante->numeroComprobantes)) {
                    $numeroComprobantes = ''.$resultAutorizado->RespuestaAutorizacionComprobante->numeroComprobantes;
                }					
				
                if ($numeroComprobantes == 1) {
				
                    if(isset($resultAutorizado->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->numeroAutorizacion)){
                        $numeroAutorizacion = '' . $resultAutorizado->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->numeroAutorizacion;
										
                    }
					
                    $fechaAutorizacion = ''.$resultAutorizado->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->fechaAutorizacion;
                    $estado = '' . $resultAutorizado->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->estado;
                    $comprobante = $resultAutorizado->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante;
				
                }
				
                switch ($estado) {
                    case 'AUTORIZADO':
					//JC TRY CATCH
			
						TRY{
                        $Documento->registrarLog($idDocumento, 'Autorizada por SRI');
                        $mensaje = 'AUTORIZADA POR SRI';
                        //$Documento->registrarEstadoAutorizacion($idDocumento, 'AUTORIZADA', $mensaje);
                        $Documento->registrarEstadoAutorizacion($idDocumento, 'AUTORIZADA', $mensaje, $docs->codigo_interno);
						//echo "AUTORIZADO";
                        $Documento->registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion, $docs->codigo_interno);
                        
                        $Documento->registrarLog($idDocumento, 'Mueve Documento a Autorizados');
                        
                        $Documento->moverRespuesta('AUTORIZADO', base_path() . '/public/Documentos/Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $numeroAutorizacion, $fechaAutorizacion, $comprobante);

                        $Documento->registrarLog($idDocumento, 'Termina mover a Autorizados');


                        $Documento->registrarPath($idDocumento, 'public/Documentos/Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $docs->codigo_interno);
                        $Documento->registrarEstadoInterno($idDocumento, 'PROCESADO', $docs->codigo_interno);

                        $Documento->registrarLog($idDocumento, 'Generando Html');
                        $Documento->registrarLog($idDocumento, 'Generado Html');

                        $nombrepdf = substr($archivo, 0, strlen($archivo) - 4) . '.pdf';

                        $Documento->registrarLog($idDocumento, 'Generando PDF');

						$Documento->generarPdf($idDocumento, base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $nombrepdf, 'Documentos/pdf/' . $anio . '/' . $mes . '/' . $dia, false);
						
                        $Documento->registrarLog($idDocumento, 'Generado PDF');

						}CATCH(\Exception $e)
						{
							echo "\nentra a catch1\n";
							echo $e->getMessage();
							return $e->getMessage();
						}							
							
					//JC TRY CATCH
					TRY{
						$conf = ConfiguracionModel::where('dato','=','ambiente')->first();
						$ambiente = $conf->valor;
						
						$email_envio = DocumentoModel::where('codigo_interno', $docs->codigo_interno)
															 ->first();
						//echo $ambiente;
						//Ambiente 2 es producción, se envía email.
						if ($ambiente == 2){
						
							if ($docs->cliente->enviado_mail_bienvenida == 0) {
								$Phpmail->MailBienvenida($docs->cliente->email, $razonSocialCliente, $rucCliente);
								$Cliente->registrarMailBienvenida($docs->cliente->id);
							}
							echo "\nINICIO DE ENVIO Email\n";
							
							echo $docs->cod_doc;
							

								if (is_object($Phpmail)) {
									unset($Phpmail);
									$Phpmail = new PhpmailClass();
								}
								
								$TipoDocumento = TipoDocumentoModel::find($docs['original']['cod_doc']);
								
								$cuerpo = '<html><head><meta charset="UTF-8"></head><body><table width="600">
												<tr>
													<td colspan="3">
														<img src="' . \Config::get('rutas.resources') . 'images/header-mail.png" width="600" height="148">
													</td>
												</tr>
												<!--tr>            
													<td width="10%">&nbsp;</td>
													<td width="80%">
														<table>
															<tr>
																<td colspan="3" align="right">
																	<img src="' . \Config::get('rutas.resources') . 'images/header-mail.png" width="155" height="75">
																</td>
																<td >&nbsp;&nbsp;&nbsp;</td>
																<td valign="top" align="left" style="padding-right:0px">
																<p>
																		<span class="txt1"><strong>GRUPO EL COMERCIO C.A.</strong></span><br>
																		<span class="txt2"><strong>RUC </strong>1790008851001</span><br>
																		<span><strong>MATRIZ QUITO:</strong> Av. Pedro Vicente Maldonado 11515 y El Tablón.<br>
																		<strong>Telfs.:</strong>(593-2)2670999 / 2679999<br> <strong>Fax:</strong>(593-2)2673907 / 2673075</spa   n><br>
																</p>
																</td>
															</tr>
														</table>
													</td>
												</tr-->
												<!--tr>            
													<td bgcolor="#0065a1" height="0.75" colspan="3"></td>
												</tr-->
												<tr>            
													<td width="10%">&nbsp;</td>
												</tr>
												<tr>
													<td width="10%">&nbsp;</td>
													<td width="80%" style="text-align:justify;">
														Estimado Cliente:<br>
														<strong>' . $docs->cliente->razon_social . '</strong><br><br>
														<!--p>Le recordamos que sus comprobantes de venta con firma electrónica tienen el  mismo valor y efectos jurídicos que la representación física, por lo que no se requiere de su impresión para efectos tributarios, según lo establece las Disposiciones del Reglamento de Comprobantes de Venta, Retención y Documentos Complementarios.</p-->
													</td>
													<td width="10%">&nbsp;</td>
												</tr>
												<tr>
													<td width="10%">&nbsp;</td>
													<td>
														Le informamos que le ha sido generado y autorizado por el SRI.<br>
														Su comprobante electrónico con el siguiente detalle:
													</td>
													<td width="10%">&nbsp;</td>
												</tr>
												<tr>            
													<td>&nbsp;</td>
												</tr>
												<tr>
													<td width="10%">&nbsp;</td>
													<td width="80%">
														<table>
															<!--tr>
																<td>
																	Nombre cliente: 
																</td>
																<td >&nbsp;</td>
																<td>
																	' . $docs->cliente->razon_social . '
																</td>
															</tr-->
															<tr>
																<td>
																	CC/RUC/Pass: 
																</td>
																<td >&nbsp;</td>
																<td>
																	' . $docs->cliente->ruc. '
																</td>
															</tr>
															<tr>
																<td>Tipo Documento:</td>
																<td >&nbsp;</td>
																<td >' . $TipoDocumento->descripcion . '</td>
															</tr>
															<tr>
																<td>No Documento:</td>
																<td >&nbsp;</td>
																<td >' . $docs->estab . "-" . $docs->ptoEmi . "-" . $docs->secuencial . '</td>
															</tr>
															<tr>
																<td>Fecha de emisión:</td>
																<td >&nbsp;</td>
																<td >' . $docs->fecha_emision . '</td>
															</tr>
															<tr>
																<td>Monto total :</td>
																<td >&nbsp;</td>
																<td >USD ' . $docs->valor_documento . '</td>
															</tr>
														</table>
													</td>
													<td width="10%">&nbsp;</td>         
												</tr>
												<tr>            
													<td>&nbsp;</td>
												</tr>
												<tr>            
													<td width="10%">&nbsp;</td>
													<!--td>
														Saludos Cordiales,
													</td-->
												</tr>
												<tr>            
													<td width="10%">&nbsp;</td>
													<td>
														<span class="txt1"><strong>GRUPO EL COMERCIO C.A.</strong></span>
													</td>
												</tr>
												<tr>            
													<td>&nbsp;</td>
												</tr>       
												<tr>
													<td width="10%">&nbsp;</td>
													<td>
														Para más información sobre sus comprobantes electrónicos dar <a href="' . \Config::get('rutas.front') . '">click aquí</a>
													</td>
													<td width="10%">&nbsp;</td>
												</tr>
												<tr>            
													<td>&nbsp;</td>
												</tr>
												<tr>            
													<td>&nbsp;</td>
												</tr>
												<tr>            
													<td bgcolor="#0065a1" height="20" colspan="3"></td>
												</tr>
											 </table></body></html>';
								//sleep(2);
									
								$Documento->registrarLog($idDocumento, 'Enviando Email');
								
								if ($email_envio->enviado_mail == 0){
									if (is_file(base_path() . '/public/Documentos/Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo)) {
										$Phpmail->MailGenerico_adjunto($correoArray, $cuerpo, "GRUPO EL COMERCIO - Comprobante Electrónico", $docs->cliente->email, base_path() . '/public/Documentos/Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, base_path() . '/public/Documentos/pdf/' . $anio . '/' . $mes . '/' . $dia . '/' . $nombrepdf);
									} else {
										$Phpmail->MailGenerico_adjunto($correoArray, $cuerpo, "GRUPO EL COMERCIO - Comprobante Electrónico", $docs->cliente->email, base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, base_path() . '/public/Documentos/pdf/' . $anio . '/' . $mes . '/' . $dia . '/' . $nombrepdf);
									}

									if (is_object($Phpmail)) {
										unset($Phpmail);
									}
									
									$Documento->registrarEnvioMail($idDocumento,$docs->codigo_interno);
									
									$log = new LogsAutorizacionModel ();
									$log->hilo = $numeroProceso;
									$log->codigo_interno = $docs->codigo_interno;
									$log->razon_social = $docs->cliente->razon_social;
									$log->save();
									
									$Documento->registrarLog($idDocumento, 'Enviado Email');
									
								}
								
								//$Documento->registrarEnvioMail($idDocumento,$docs->codigo_interno);
								//echo 'REGISTRA LOG';

								/////////////////////////////////////FIN ENVIO DE CORREO/////////
							// JS 24MAR2021 Se comenta }
							$Documento->registrarLog($idDocumento, 'Eliminando Archivo de Firmados');
							@unlink(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
							
						}else{
							
							if ($email_envio->enviado_mail == 0){
								$Documento->registrarEnvioMail($idDocumento,$docs->codigo_interno);
								
								$log = new LogsAutorizacionModel ();
								$log->hilo = $numeroProceso;
								$log->codigo_interno = $docs->codigo_interno;
								$log->razon_social = $docs->cliente->razon_social;
								$log->save();
							}
							
							$Documento->registrarLog($idDocumento, 'Eliminando Archivo de Firmados');
							@unlink(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
							
						}
					}
					CATCH(\Exception $e)
					{
							echo "\nentra a catch2\n";
						echo $e->getMessage();
							//die();
						return $e->getMessage();
					}						


                        break;

                    case 'NO AUTORIZADO':
                        $mensaje = '';
                        $mensajeResult = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['mensajes'];

                        if (array_key_exists('0', $mensajeResult['mensaje'])) {
                            for ($contadorMensaje = 0; $contadorMensaje < count($mensajeResult); $contadorMensaje++) {
                                $mensaje.= '' . $mensajeResult['mensaje'][$contadorMensaje]['mensaje'];
                                $mensaje.=".<br/> " . utf8_encode($mensajeResult['mensaje'][$contadorMensaje]['informacionAdicional']);
                            }
                        } else {
                            $mensaje.='' . $mensajeResult['mensaje']['mensaje'];
                            $mensaje.=".<br/> " . utf8_encode($mensajeResult['mensaje']['informacionAdicional']);
                        }


                        $Documento->registrarLog($idDocumento, 'No Autorizada por SRI');


                        $Documento->registrarLog($idDocumento, 'No Autorizada por SRI');

                        $Documento->registrarEstadoAutorizacion($idDocumento, 'NO AUTORIZADA', $mensaje, $docs->codigo_interno);
                        $numeroAutorizacion = '';
                        $fechaAutorizacion = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['fechaAutorizacion'];
                        $comprobante = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['comprobante'];

                        $Documento->registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion, $docs->codigo_interno);
                        $Documento->moverRespuesta('NO AUTORIZADO', base_path() . '/public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $numeroAutorizacion, $fechaAutorizacion, $comprobante);
                        $Documento->registrarPath($idDocumento,  'public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $docs->codigo_interno);
                        $Documento->registrarEstadoInterno($idDocumento, 'PROCESADO', $docs->codigo_interno);

                        $mail = 'Documento No Autorizado por SRI.<br/>';
                        $mail.='Información:<br/>';
                        $mail.='RUC/CI: ' . $rucCliente . '<br/>';
                        $mail.='Cliente: ' . $razonSocialCliente . '<br/>';
                        $mail.='CodInterno: ' . $codInterno . '<br/>';
                        $mail.='Fecha Emisión: ' . $fechaEmision . '<br/>';
                        $mail.='Nombre Documento: ' . $nombreDocumento . '<br/>';
                        $mail.='Motivo: ' . $mensaje . '<br/>';


                        $respuestaSri = RespuestaSRIModel::where('estado', 'NoAutoriza')->get();
                        $correoArrays = array();
                        $estado = 0;
                        foreach ($respuestaSri as $key) {
                            $correoArrays = explode(';', $key->correo);
                            $estado = $key->activo;
                        }

                        if (is_object($Phpmail)) {
                            unset($Phpmail);
                            $Phpmail = new PhpmailClass();
                        }

                        $Documento->registrarLog($idDocumento, 'Enviando Email');

                        $Phpmail->MailError($correoArrays, "No Autorizado", $mail);
                        $Documento->registrarLog($idDocumento, 'Enviado Email');

                        $Documento->registrarLog($idDocumento, 'Borrando Documento de Firmados');

                        @unlink(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                        $Documento->registrarLog($idDocumento, 'Borrado Documento de Firmados');
                        break;
					
					//DPS - 20190219 cambio para nueva respuesta SRI.
					case 'DEVUELTA':
					
						$mensaje = '';
                        $mensajeResult = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['mensajes'];

                        if (array_key_exists('0', $mensajeResult['mensaje'])) {
                            for ($contadorMensaje = 0; $contadorMensaje < count($mensajeResult); $contadorMensaje++) {
                                $mensaje.= '' . $mensajeResult['mensaje'][$contadorMensaje]['mensaje'];
                                $mensaje.=".<br/> " . utf8_encode($mensajeResult['mensaje'][$contadorMensaje]['informacionAdicional']);
                            }
                        } else {
                            $mensaje.='' . $mensajeResult['mensaje']['mensaje'];
                            $mensaje.=".<br/> " . utf8_encode($mensajeResult['mensaje']['informacionAdicional']);
                        }
						
						$Documento->registrarLog($idDocumento, $mensaje);


                        $Documento->registrarLog($idDocumento, $mensaje);

                        $Documento->registrarEstadoAutorizacion($idDocumento, 'NO AUTORIZADA', $mensaje, $docs->codigo_interno);
                        $numeroAutorizacion = '';
                        $fechaAutorizacion = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['fechaAutorizacion'];
                        $comprobante = $resultAutorizado['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['comprobante'];

                        $Documento->registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion, $docs->codigo_interno);
                        $Documento->moverRespuesta('NO AUTORIZADO', base_path() . '/public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $numeroAutorizacion, $fechaAutorizacion, $comprobante);
                        $Documento->registrarPath($idDocumento,  'public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $docs->codigo_interno);
                        $Documento->registrarEstadoInterno($idDocumento, 'PROCESADO', $docs->codigo_interno);

                        $mail = 'Documento No Autorizado por SRI.<br/>';
                        $mail.='Información:<br/>';
                        $mail.='RUC/CI: ' . $rucCliente . '<br/>';
                        $mail.='Cliente: ' . $razonSocialCliente . '<br/>';
                        $mail.='CodInterno: ' . $codInterno . '<br/>';
                        $mail.='Fecha Emisión: ' . $fechaEmision . '<br/>';
                        $mail.='Nombre Documento: ' . $nombreDocumento . '<br/>';
                        $mail.='Motivo: ' . $mensaje . '<br/>';


                        $respuestaSri = RespuestaSRIModel::where('estado', 'NoAutoriza')->get();
                        $correoArrays = array();
                        $estado = 0;
                        foreach ($respuestaSri as $key) {
                            $correoArrays = explode(';', $key->correo);
                            $estado = $key->activo;
                        }

                        if (is_object($Phpmail)) {
                            unset($Phpmail);
                            $Phpmail = new PhpmailClass();
                        }

                        $Documento->registrarLog($idDocumento, 'Enviando Email');

                        $Phpmail->MailError($correoArrays, "No Autorizado", $mail);
                        $Documento->registrarLog($idDocumento, 'Enviado Email');

                        $Documento->registrarLog($idDocumento, 'Borrando Documento de Firmados');

                        @unlink(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                        $Documento->registrarLog($idDocumento, 'Borrado Documento de Firmados');
                        
					
                    break;
					//FIN - DPS - 20190219 cambio para nueva respuesta SRI.
					
                    case 'SIN AUTORIZACION':

                        $mensaje = 'Sin Autorización por SRI';

                        $Documento->registrarLog($idDocumento, 'Sin Autorización por SRI');


                        $Documento->registrarLog($idDocumento, 'Sin Autorización por SRI');

                        //$Documento->registrarEstadoAutorizacion($idDocumento, 'SIN AUTORIZACION', $mensaje);
						
						$sql="update documento set hilo=0, estado='RECIBIDA' where id=".$idDocumento;
						\DB::select($sql);
                        $numeroAutorizacion = '';
                        $fechaAutorizacion = '';
                        $comprobante = '';

                        //$Documento->registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion);
                        /*$Documento->moverRespuesta('SIN AUTORIZACION', base_path() . '/public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $numeroAutorizacion, $fechaAutorizacion, $comprobante);
                        $Documento->registrarPath($idDocumento,  'public/Documentos/No_Autorizados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                        */
                        //$Documento->registrarEstadoInterno($idDocumento, 'PROCESADO');

                        $mail = 'Documento Sin Autorizacion por SRI.<br/>';
                        $mail.='Información:<br/>';
                        $mail.='RUC/CI: ' . $rucCliente . '<br/>';
                        $mail.='Cliente: ' . $razonSocialCliente . '<br/>';
                        $mail.='CodInterno: ' . $codInterno . '<br/>';
                        $mail.='Fecha Emisión: ' . $fechaEmision . '<br/>';
                        $mail.='Nombre Documento: ' . $nombreDocumento . '<br/>';
                        $mail.='Motivo: ' . $mensaje . '<br/>';


                        $respuestaSri = RespuestaSRIModel::where('estado', 'NoAutoriza')->get();
                        $correoArrays = array();
                        $estado = 0;
                        foreach ($respuestaSri as $key) {
                            $correoArrays = explode(';', $key->correo);
                            $estado = $key->activo;
                        }

                        /*if (is_object($Phpmail)) {
                            unset($Phpmail);
                            $Phpmail = new PhpmailClass();
                        }

                        $Documento->registrarLog($idDocumento, 'Enviando Email');

                        $Phpmail->MailError($correoArrays, "Sin autorización", $mail);
                        $Documento->registrarLog($idDocumento, 'Enviado Email');*/

                        break;
                    default :

                        $mensaje = 'Sin Autorización por SRI';

                        $Documento->registrarLog($idDocumento, 'Sin Autorización por SRI');


                        $Documento->registrarLog($idDocumento, 'Sin Autorización por SRI');

                        //$Documento->registrarEstadoAutorizacion($idDocumento, 'SIN AUTORIZACION', $mensaje);
						
						$sql="update documento set hilo=0, estado='RECIBIDA' where id=".$idDocumento;
						\DB::select($sql);
						
                        $numeroAutorizacion = '';
                        $fechaAutorizacion = '';
                        $comprobante = '';

                        //$Documento->registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion);
                        $Documento->registrarEstadoInterno($idDocumento, 'PROCESADO', $docs->codigo_interno);

                        $mail = 'Documento SIN Autorizado por SRI.<br/>';
                        $mail.='Información:<br/>';
                        $mail.='RUC/CI: ' . $rucCliente . '<br/>';
                        $mail.='Cliente: ' . $razonSocialCliente . '<br/>';
                        $mail.='CodInterno: ' . $codInterno . '<br/>';
                        $mail.='Fecha Emisión: ' . $fechaEmision . '<br/>';
                        $mail.='Nombre Documento: ' . $nombreDocumento . '<br/>';
                        $mail.='Motivo: ' . $mensaje . '<br/>';
						$mail.='Estado: '.$estado;


                        $respuestaSri = RespuestaSRIModel::where('estado', 'NoAutoriza')->get();
                        $correoArrays = array();
                        $estado = 0;
                        foreach ($respuestaSri as $key) {
                            $correoArrays = explode(';', $key->correo);
                            $estado = $key->activo;
                        }

                        /*if (is_object($Phpmail)) {
                            unset($Phpmail);
                            $Phpmail = new PhpmailClass();
                        }
                        $Documento->registrarLog($idDocumento, 'Enviando Email');

                        $Phpmail->MailError($correoArrays, "Sin Autorización", $mail);
                        $Documento->registrarLog($idDocumento, 'Enviado Email');
						*/

                        
                        break;
                }
            }
		  //}
			
				$Docum = DocumentoModel::where('codigo_interno',$docs->codigo_interno)
									   ->first();
				
				if ($Docum->estado == 'RECIBIDA' && $Docum->hilo > 0){
					
					$Docum->hilo = 0;
					$Docum->save();
					
				}
			  
			}CATCH(\Exception $e){
				
				$Docum = DocumentoModel::where('codigo_interno',$docs->codigo_interno)
									   ->first();

				$Docum->estado = 'RECIBIDA';
				$Docum->hilo = 0;
				$Docum->save();
				
			}
		  
        }	
		
		}
		
		
		/* DPS - 20190122 Se comenta por nuevo proceso en los hilos
        if(count($documentos)>0){
			
            $Hilo=HilosModel::find($idHilo);
            $Hilo->estado=0;
            $Hilo->fecha_fin=date("Y-m-d H:i:s");
            $Hilo->documentos=serialize(array());
            $Hilo->save();
        }
		FIN */
    }

}
