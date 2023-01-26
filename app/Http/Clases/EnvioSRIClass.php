<?php

namespace App\Http\Clases;

use App\Http\Models\ClienteModel;
use App\Http\Clases\ConexionSRIClass;
use App\Http\Clases\DocumentoClass;
use App\Http\Models\DocumentoModel;
use App\Http\Models\ConfiguracionModel;
use App\Http\Models\RespuestaSRIModel;


class EnvioSRIClass {

    public function envioSRI() {

        $anio = date("Y");
        $mes = date("m");
        $dia = date("d");

        $ConexionSRI = new ConexionSRIClass();
        $Documento = new DocumentoClass();
        $Cliente = new ClienteClass();

        $configuracion = ConfiguracionModel::find(7);
        $correosoporte = $configuracion->valor;


        $fechaActual = date("Y-m-d");
        $fechaMesAnterior = date('Y-m-d', strtotime('-30 day'));



        $documentos = DocumentoModel::where('estado', 'FIRMADO')->where('contingencia', '0')->whereBetween('fecha_emision', [$fechaMesAnterior, $fechaActual])->take(50)->get();
        
    


        foreach ($documentos as $docs) {

 
            $idDocumento = $docs->id;
            $path = base_path() . "/" . $docs->path;
            $archivo = $docs->nombre_archivo;
            if (is_file(base_path() .'/'. $docs->path)) {
                $xml = simplexml_load_file(base_path() . '/'.$docs->path);


                $Documento->registrarLog($idDocumento, 'Procesando a SRI');
                $Documento->registrarEstado($idDocumento, 'Procesando a SRI', '');

                $resultRecibido = $ConexionSRI->recepcion($path);

										  

                $estado = 'FIRMADO';

                $mensaje = '';
                $infoAdicional='';
       

                if (isset($resultRecibido->RespuestaRecepcionComprobante->estado)) {
                    $estado = '' . $resultRecibido->RespuestaRecepcionComprobante->estado;
                }

                if ($estado == 'DEVUELTA') {
                    if (isset($resultRecibido['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['mensaje'])) {
                        $mensaje = '' . $resultRecibido['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['mensaje'];
						$infoAdicional = '' . $resultRecibido['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['mensaje'];
                    }
                    
                    


      
                    $mail='Motivo: ' . $mensaje . '<br/>'.  utf8_decode($infoAdicional);
                    
               


                    $respuestaSri = RespuestaSRIModel::where('estado', 'Devuelta')->get();
          
              
                  

                    $Documento->registrarEstado($idDocumento, 'DEVUELTA', $mensaje);
					$Documento->registrarTipoError($idDocumento,'SRI');
                    copy(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo, base_path() . '/public/Documentos/Devueltas/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                    $Documento->registrarPath($idDocumento, 'public/Documentos/Devueltas/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                    $Documento->registrarEstadoInterno($idDocumento, 'PROCESADO', $docs->codigo_interno);


                    @unlink(base_path() . '/public/Documentos/Firmados/' . $anio . '/' . $mes . '/' . $dia . '/' . $archivo);
                }
                $Documento->registrarLog($idDocumento, $estado);
                $Documento->registrarEstado($idDocumento, $estado, $mensaje);



             

            }
        }
    }

}
