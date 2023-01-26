<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Clases\FirmadorClass;
use App\Http\Clases\GeneralClass;
use App\Http\Models\ContingenciaModel;
use App\Http\Clases\ConexionSRIClass;
use App\Http\Clases\DocumentoClass;
use App\Http\Clases\ClienteClass;
use App\Http\Models\ConfiguracionModel;
use App\Http\Clases\PhpmailClass;
use App\Http\Models\RespuestaSRIModel;
use App\Http\Models\DocumentoModel;

class FirmarXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'FirmarXml';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Firmar Xml';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $anio = date("Y");
        $mes = date("m");
        $dia = date("d");
 
        $General = new GeneralClass();
        $Firmador = new FirmadorClass();
        $Documento = new DocumentoClass();
        $ConexionSRI = new ConexionSRIClass();
        $Cliente = new ClienteClass();
        ///
        $configuracion = ConfiguracionModel::find(7);
        $correosoporte = $configuracion->valor;
        $linea = '';
        $configuracion = ConfiguracionModel::where('dato', 'fecha_caducidad')->get();
        $fechaCaducidad = time();
        foreach ($configuracion as $conf) {
            # code...
            $fechaCaducidad = strtotime($conf->valor);
        }
        ///
        
        
        
        if (time() < $fechaCaducidad) {

            $directorio = opendir(\Config::get('rutas.documentos') . 'Originales'); //ruta actual

            $path = \Config::get('rutas.documentos') . 'Originales';

            while ($archivo = readdir($directorio)) { //obtenemos un archivo y luego otro sucesivamente
                if (!is_dir($archivo)) {//verificamos si es o no un directorio
                    $data[] = array($archivo, date("Y-m-d H:i:s", filemtime($path . '/' . $archivo)));
                    $files[] = $archivo;
                    $dates[] = date("Y-m-d H:i:s", filemtime($path . '/' . $archivo));
                }
            }
            closedir($directorio);
            if (empty($data))
            {
                 $this->info('NO EXISTEN DOCUMENTOS PARA ENVIAR');
                 die();                 
            }    
            array_multisort($dates, SORT_ASC, $data);


            

            foreach ($data as $lista) {
                $archivo = $lista[0];
                $documento = \Config::get('rutas.documentos') . 'Originales/' . $archivo;
                $path = \Config::get('rutas.documentos') . 'Originales/' . $archivo;
                $xml = file_get_contents($documento);
                
                echo $xml;
                
                $validacion = $General->validarXmlCorrecto($xml);
              
                if ($validacion[0]) {
                    $espaciosBlanco = $General->espaciosBlanco($xml);

                    if ($espaciosBlanco[0]) {

                        if (!$Documento->verificarNoIngresado($documento)) {
                            
                            $this->info('ENVIA A FIRMAR');
                            
                       
                            $xmlFirmado = $Firmador->firmar($documento);
                            
                            echo $xmlFirmado[1]->save(\Config::get('rutas.documentos') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
							
							chmod(\Config::get('rutas.documentos') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo,0775);


                            if (!$Documento->verificarClaveAcceso(\Config::get('rutas.documentos') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo)) {

                                $claveAcceso = $Documento->verClaveAcceso(\Config::get('rutas.documentos') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);

                                echo $claveAcceso;

                                $Documento->ingresarDocumento(\Config::get('rutas.documentos') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $archivo);
                                $idDocumento = $Documento->verIdUltimo();

                                $Documento->registrarLog($idDocumento, 'Firmado');
								$codigo = DocumentoModel::find($idDocumento);
                                $Documento->registrarPath($idDocumento, \Config::get('rutas.path') . 'Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $codigo->codigo_interno);
                                $Documento->registrarEstadointerno($idDocumento, 'Firmado', $codigo->codigo_interno);
                            } else {

                                $idDocumento = $Documento->verIdPorNumeroLegal($documento);
                                $DocEstado = DocumentoModel::find($idDocumento);

                                echo "Esta clave de acceso ya esta registrada";

                                echo $idDocumento;



                                if ($DocEstado->estado == 'NO AUTORIZADO') {

                                  //  $linea.= $Documento->getDatosRespuesta($documento, '', '0', '0', 'X', '', 'XML', 'Documento Duplicado') . "\r\n";

                                    //fwrite($file, $linea . "\r\n");
                                }

                                copy($documento, \Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
								
								chmod(\Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo,0775);

                                $xml2 = simplexml_load_string($xml);

                                $mail = '<h4>Documento Duplicado.</h4><br/>';
                                $mail.='<strong>Información:</strong><br/><br/>';
                                $mail.='<strong>Documento:</strong>  ' . $archivo . '<br/>';
                                $mail.='<strong>Número Legal:</strong>  ' . $xml2->infoTributaria->estab . '-' . $xml2->infoTributaria->ptoEmi . '-' . $xml2->infoTributaria->secuencial . '<br/>';
                                $mail.='<strong>Motivo:</strong>  Esta clave de acceso ya esta registrada en el sistema.<br/>';

                                $mail.='<strong>Ubicación:</strong>  ' . \Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;

                                $respuestaSri = RespuestaSRIModel::where('estado', 'Interno')->get();
                                $correoArray = array();
                                $estado = 0;
                                foreach ($respuestaSri as $key) {
                                    $correoArray = explode(';', $key->correo);
                                    $estado = $key->activo;
                                }

                                if ($estado == 1) {

                                    $Phpmail->MailError($correoArray, "Error Firmar Documento", $mail);
                                }
                            }
                            unlink(\Config::get('rutas.documentos') . 'Originales/' . $archivo);
                            $dataresponse = array("status" => "firmado");
                            return json_encode($dataresponse);
                            exit();
                            
                        } else {

                            $idDocumento = $Documento->verIdPorNumeroLegal($documento);
                            $DocEstado = DocumentoModel::find($idDocumento);

                            echo "este documento ya fue ingresado a la DB";

                            if ($DocEstado->estado == 'NO AUTORIZADO') {
                                //fwrite($file, $linea . "\r\n");
                              //  $linea.= $Documento->getDatosRespuesta($documento, '', '0', '0', 'X', '', 'XML', 'Documento Duplicado') . "\r\n";
                            }

                            copy($documento, \Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
							
							chmod(\Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo,0775);
                            unlink(\Config::get('rutas.documentos') . 'Originales/' . $archivo);


                            $xml2 = simplexml_load_string($xml);
                            $mail = '<h4>Documento Duplicado.</h4><br/>';
                            $mail.='<strong>Información:</strong><br/><br/>';
                            $mail.='<strong>Documento:</strong>  ' . $archivo . '<br/>';
                            $mail.='<strong>Número Legal:</strong>  ' . $xml2->infoTributaria->estab . '-' . $xml2->infoTributaria->ptoEmi . '-' . $xml2->infoTributaria->secuencial . '<br/>';
                            $mail.='<strong>Motivo:</strong>  Esta clave de acceso ya esta registrada en el sistema.<br/>';

                            $mail.='<strong>Ubicación:</strong>  ' . \Config::get('rutas.documentos') . 'Duplicados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;
                            $respuestaSri = RespuestaSRIModel::where('estado', 'Interno')->get();

                            $correoArray = array();
                            $estado = 0;
                            foreach ($respuestaSri as $key) {
                                $correoArray = explode(';', $key->correo);
                                $estado = $key->activo;
                            }

                            if ($estado == 1) {
                                $Phpmail->MailError($correoArray, "Error Firmar Documento", $mail);
                            }
                        }
                    } else {

                        echo "El original es:" . $documento;

						$Documento->ingresarDocumentoDevuelta($documento, $archivo, $espaciosBlanco[1], false);//DPS
						
                       // $linea.= $Documento->getDatosRespuesta($documento, '', '0', '0', 'X', '', 'XML', $espaciosBlanco[1]) . "\r\n";

                        //fwrite($file, $linea . "\r\n");

                        copy($documento, \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
						
						chmod(\Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo,0775);
						
                        unlink(\Config::get('rutas.documentos') . 'Originales/' . $archivo);
                        $this->info('Existe un error en el archivo ' . $archivo . ': ' . $espaciosBlanco[1] . '. Y se encuentra en: ' . \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);

                        $Documento->registrarError(\Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $espaciosBlanco[1]);

                        //$mail = "<h2>ERROR FIRMAR XML</h2><br><br>Existe un error en el archivo " . $archivo . ': ' . $espaciosBlanco[1] . '.<br> Y se encuentra en: ' . \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;

                        $mail = '<h4>Documento con errores al firmar.</h4><br/>';
                        $mail.='<strong>Información:</strong><br/><br/>';
                        $mail.='<strong>Documento:</strong>  ' . $archivo . '<br/>';
                        $mail.='<strong>Motivos:</strong>  ' . $espaciosBlanco[1] . '<br/>';
                        $mail.='<strong>Ubicación: </strong> ' . \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;
                        $this->info('ERROR AL FIRMAR DOCUMENTO 1'. $espaciosBlanco[1]);
                        die();  
                        


                         //$Phpmail->MailError($correoArray, "Error Firmar Documento", $mail);
                        
                    }
                } else {

                    copy($documento, \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
					chmod(\Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo,0775);
                    unlink(\Config::get('rutas.documentos') . 'Originales/' . $archivo);
                    $this->info('Existe un error en el archivo ' . $archivo . ': ' . $validacion[1] . '. Y se encuentra en: ' . \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo . '\n');
                    $Documento->registrarErrorXml(\Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, $validacion[1]);
                    //$mail = "<h2>ERROR FIRMAR XML</h2><br><br>Documento: ".$documento."<br>Existe un error en el archivo" . $archivo . ': ' . $validacion[1] . ". Y se encuentra en: <br>".\Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;

                    $mail = 'Documento con errores al firmar.<br/>';
                    $mail.='Información:<br/>';
                    $mail.='Documento: ' . $archivo . '<br/>';
                    $mail.='Motivo: ' . $validacion[1] . '<br/>';
                    $mail.='Ubicación: ' . \Config::get('rutas.documentos') . 'Errados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo;


                    $respuestaSri = RespuestaSRIModel::where('estado', 'Interno')->get();
                    $correoArray = array();
                    $estado = 0;
                    foreach ($respuestaSri as $key) {
                        $correoArray = explode(';', $key->correo);
                        $estado = $key->activo;
                    }

                    if ($estado == 1) {

                        $Phpmail->MailError($correoArray, "Error Firmar Documento", $mail);
                    }
                }
            }
        } else {

            echo "No pudo firmar porque el certificado expiro";
        }
        
        ///
        
        return Command::SUCCESS;
    }
}
