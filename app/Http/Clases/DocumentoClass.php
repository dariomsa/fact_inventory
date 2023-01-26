<?php


namespace App\Http\Clases;

error_reporting(E_ALL);

echo (\Config::get("rutas.local")); 

require_once \Config::get("rutas.local") . 'assets/php/tcpdf/Docs/fac_elec.php';

use App\Http\Models\DocumentoModel;
use App\Http\Models\ClienteModel;
use App\Http\Models\LogsModel;
use App\Http\Models\ErrorModel;
use App\Http\Clases\PhpmailClass;
use App\Http\Models\Doc_x_docsusModel;
use App\Http\Models\Doc_x_guiaRemision;
use App\Http\Models\FormaPagoModel;
use App\Http\Models\DocumentoInvalidoModel;
use App\Http\Clases\MigracionClass;
use App\Http\Models\MensajeJudicialModel;
class DocumentoClass {

    public function ingresarDocumento($documento, $nombreArchivo) {
        $xml = new \SimpleXmlElement(file_get_contents($documento));

        $codDoc = "" . $xml->infoTributaria->codDoc;
        $identificacionComprador = "";
        $fechaEmision = date("Y-m-d");
        $importeTotal = 0.0;
        $unidadNegocio = '';
        $codInterno = '';
        $codSociedad = '';
        $emision = '1';

        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $secuencial = "" . $xml->infoTributaria->secuencial;

        $subtotalSinIVA = 0.00;
        $valorIva = 0.00;


        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $emision = $key->valor;
        }

        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $unidadNegocio = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional7') {
                $campoAdicional7Array = explode('|', "" . $xml->infoAdicional->campoAdicional[$i]);
                $valorCR = $campoAdicional7Array[0];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional2') {
                $campoAdicional2Array = explode('|', "" . $xml->infoAdicional->campoAdicional[$i]);
            }
        }


        switch ($codDoc) {
            case '01':
                $identificacionComprador = "" . $xml->infoFactura->identificacionComprador;
                $fechaEmision = "" . $xml->infoFactura->fechaEmision;
                $importeTotal = "" . $xml->infoFactura->importeTotal;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor);
                break;

            case '04':
                $identificacionComprador = "" . $xml->infoNotaCredito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaCredito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaCredito->valorModificacion;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoNotaCredito->totalConImpuestos->totalImpuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoNotaCredito->totalConImpuestos->totalImpuesto->valor);
                break;

            case '05':
                $identificacionComprador = "" . $xml->infoNotaDebito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaDebito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaDebito->valorTotal;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoNotaDebito->impuestos->impuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoNotaDebito->impuestos->impuesto->valor);


                break;

            case '06':
                $identificacionComprador = "" . $xml->destinatarios->destinatario->identificacionDestinatario;
                $fechaEmision = date('Y-m-d');
                $importeTotal = 0;
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                break;

            case '07':
                $identificacionComprador = "" . $xml->infoCompRetencion->identificacionSujetoRetenido;
                $fechaEmision = "" . $xml->infoCompRetencion->fechaEmision;
                $importeTotal = $valorCR;
                $claveAcceso = $xml->infoTributaria->claveAcceso;
                break;
        }

    
        $idCliente = 1;

        $DocAux = DocumentoModel::where('estab', $estab)->where('ptoEmi', $ptoEmi)->where('secuencial', $secuencial)->get();

       //DPS - 20190424 - se comenta para que no actualice el registro ya ingresado
        /*if (count($DocAux) > 0) {
            foreach ($DocAux as $key) {
                $idDoc = $key->id;
            }
            $Documento = DocumentoModel::find($idDoc);
        } else {

            $Documento = new DocumentoModel();
        }*/
		//FIN - DPS - 20190424 - se comenta para que no actualice el registro ya ingresado
		
		if (count($DocAux) == 0) {
			
			$Documento = new DocumentoModel();
			$Documento->cliente_id = $idCliente;
			$Documento->nombre_archivo = $nombreArchivo;
			$Documento->cod_doc = $codDoc;
			$Documento->clave_acceso = "" . $claveAcceso;
			$Documento->estab = "" . $xml->infoTributaria->estab;
			$Documento->ptoEmi = "" . $xml->infoTributaria->ptoEmi;
			$Documento->secuencial = "" . $xml->infoTributaria->secuencial;
			$Documento->fecha_emision = date("Y-m-d", strtotime(str_replace('/', '-', $fechaEmision)));
			//$Documento->codigo_principal="".$xml->detalles->detalle->codigoPrincipal;
			$Documento->unidad_negocio = $unidadNegocio;
			$Documento->codigo_interno = $codInterno;
			$Documento->cod_sociedad = $codSociedad;
			$Documento->valor_documento = $importeTotal;
			$Documento->fecha_firma = date("Y-m-d H:i:s");
			$Documento->estado = "FIRMADO";
			$Documento->mensaje_estado = '';
			$Documento->enviado_sri = '0';
			$Documento->subtotal = $subtotalSinIVA;
			$Documento->valor_iva = $valorIva;
			$Documento->hilo = 0;

			$Documento->numero_legal=$estab.$ptoEmi.$secuencial;
			$Documento->error='';

			if ($emision == '1') {
				$Documento->contingencia = '0';
			} else {
				$Documento->contingencia = '1';
			}
			//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
			//\Log::error(['DocumentoClass'=>"Log verificar al insertar",'Documento'=>$Documento]);
			$Documento->save();
			if ($codDoc == '07') {
				$Documento1 = new Doc_x_docsusModel();
				$Documento1->doc_id = $Documento->id;
				$Documento1->doc_sustento = $campoAdicional2Array[0];
				$Documento1->save();
			}
			if ($codDoc == '06') {
				$Documento1 = new Doc_x_docsusModel();
				$Documento1->doc_id = $Documento->id;
				$Documento1->doc_sustento = $xml->destinatarios->destinatario->numDocSustento;
				$Documento1->save();
				$Documento1 = new Doc_x_guiaRemision();
				$Documento1->doc_id = $Documento->id;
				$Documento1->dirPartida = $xml->infoGuiaRemision->dirPartida;
				$Documento1->razonSocialTransportista = $xml->infoGuiaRemision->razonSocialTransportista;
				$Documento1->tipoIdentificacionTransportista = $xml->infoGuiaRemision->tipoIdentificacionTransportista;
				$Documento1->rucTransportista = $xml->infoGuiaRemision->rucTransportista;
				$Documento1->fechaIniTransporte = $xml->infoGuiaRemision->fechaIniTransporte;
				$Documento1->fechaFinTransporte = $xml->infoGuiaRemision->fechaFinTransporte;
				$Documento1->placa = $xml->infoGuiaRemision->placa;
				$Documento1->save();
			}
		}
    }

    public function verificarNoIngresado($documento) {
        $xml = simplexml_load_file($documento);
        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $secuencial = "" . $xml->infoTributaria->secuencial;
        $codInterno = '';
        $estado = '';
        $idDocumento = 0;


        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        $sql = "select * from documento where estab='" . $estab . "' and ptoEmi='" . $ptoEmi . "' and secuencial='" . $secuencial . "' and estado in('AUTORIZADA','NO AUTORIZADA')";

        //$Documento = DocumentoModel::where('estab', $estab)->where('ptoEmi', $ptoEmi)->where('secuencial', $secuencial)->where('estado','<>','AUTORIZADA')->where('estado','<>','NO AUTORIZADA')->get();
        $Documento = \DB::select($sql);

        if (count($Documento) > 0) {
            return true;
        } else {
            $sql = "select * from documento where codigo_interno='" . $codInterno . "' and estado ='AUTORIZADA'";
            $Documento1 = \DB::select($sql);

            if (count($Documento1) > 0) {
                return true;
            } else {

                $Documentos=DocumentoModel::where('codigo_interno',$codInterno)->get();

                foreach ($Documentos as $doc) {
                    # code...

                    $DocInvalido = new DocumentoInvalidoModel();

                    //echo $doc->id;

                    $DocInvalido->cliente_id = $doc->cliente_id;
                    $DocInvalido->nombre_archivo = $doc->nombre_archivo;
                    $DocInvalido->clave_acceso = $doc->clave_acceso;
                    $DocInvalido->cod_doc = $doc->cod_doc;
                    $DocInvalido->estab = $doc->estab;
                    $DocInvalido->ptoEmi = $doc->ptoEmi;
                    $DocInvalido->secuencial = $doc->secuencial;
                    $DocInvalido->fecha_emision = $doc->fecha_emision;
                    $DocInvalido->codigo_principal = $doc->codigo_principal;
                    $DocInvalido->unidad_negocio = $doc->unidad_negocio;
                    $DocInvalido->codigo_interno = $doc->codigo_interno;
                    $DocInvalido->cod_sociedad = $doc->cod_sociedad;
                    $DocInvalido->valor_documento = $doc->valor_documento;
                    $DocInvalido->fecha_firma = $doc->fecha_firma;
                    $DocInvalido->enviado_sri = $doc->enviado_sri;
                    $DocInvalido->fecha_envio_sri = $doc->fecha_envio_sri;
                    $DocInvalido->enviado_mail = $doc->enviado_mail;
                    $DocInvalido->fecha_envio_mail = $doc->fecha_envio_mail;
                    $DocInvalido->usuario_creacion = $doc->usuario_creacion;
                    $DocInvalido->usuario_modifica = $doc->usuario_modifica;
                    $DocInvalido->estado = $doc->estado;
                    $DocInvalido->mensaje_estado = $doc->mensaje_estado;
                    $DocInvalido->numero_autorizacion = $doc->numero_autorizacion;
                    $DocInvalido->fecha_autorizacion = $doc->fecha_autorizacion;
                    $DocInvalido->contingencia = $doc->contingencia;
                    $DocInvalido->migrado = $doc->migrado;
                    $DocInvalido->path = $doc->path;
                    $DocInvalido->html = $doc->html;
                    $DocInvalido->estado_interno = $doc->estado_interno;
                    $DocInvalido->pendiente_respuesta = $doc->pendiente_respuesta;
                    $DocInvalido->subtotal = $doc->subtotal;
                    $DocInvalido->valor_iva = $doc->valor_iva;
					$DocInvalido->error=$doc->error;

                    $DocInvalido->save();

                    $DOCValido = DocumentoModel::find($doc->id);
                    $DOCValido->delete();
                }

                return false;
            }
        }
        return false;
    }

    public function verIdPorNumeroLegal($documento) {

        $xml = simplexml_load_file($documento);
        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $secuencial = "" . $xml->infoTributaria->secuencial;

        $sql = "select * from documento where estab='" . $estab . "' and ptoEmi='" . $ptoEmi . "' and secuencial='" . $secuencial . "'";

        //$Documento = DocumentoModel::where('estab', $estab)->where('ptoEmi', $ptoEmi)->where('secuencial', $secuencial)->where('estado','<>','AUTORIZADA')->where('estado','<>','NO AUTORIZADA')->get();
        $Documento = \DB::select($sql);
        $idDocumento = 0;
        foreach ($Documento as $doc) {
            $idDocumento = $doc->id;
        }

        return $idDocumento;
    }

    public function verificarClaveAcceso($documento) {
        $xml = simplexml_load_file($documento);
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;

        $sql = "select * from documento where clave_acceso='" . $claveAcceso . "' and estado in('AUTORIZADA','NO AUTORIZADA')";

        //$Documento = DocumentoModel::where('secuencial', $secuencial)->get();
        $Documento = \DB::select($sql);
        if (count($Documento) > 0) {
            return true;
        }
        return false;
    }

    public function registrarEstado($idDocumento, $estado, $mensaje) {
        /*
          $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
          $idDocumento = 0;
          foreach ($Documento as $doc) {
          $idDocumento = $doc->id;
          } */

        $Documento = DocumentoModel::find($idDocumento);
        $Documento->enviado_sri = 1;
        $Documento->fecha_envio_sri = date("Y-m-d H:i:s");
        $Documento->estado = $estado;
        $Documento->mensaje_estado = $mensaje;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 12",'Documento'=>$Documento]);
		
        $Documento->save();
    }

    public function verClaveAcceso($documento) {
        $xml = simplexml_load_file($documento);
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;

        return $claveAcceso;
    }

    public function registrarAutorizado($idDocumento, $numeroAutorizacion, $fechaAutorizacion, $codInterno) {
        /*
          $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
          $idDocumento = 0;
          foreach ($Documento as $doc) {
          $idDocumento = $doc->id;
          }
         */

        //$Documento = DocumentoModel::find($idDocumento);
        $Documento = DocumentoModel::where('id',$idDocumento)
								   ->where('codigo_interno',$codInterno)
								   ->first();
								   
        $Documento->numero_autorizacion = $numeroAutorizacion;
        $Documento->fecha_autorizacion = $fechaAutorizacion;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 13",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function moverRespuesta($estado, $path, $numeroAutorizacion, $fechaAutorizacion, $comprobante) {

        $xml = new \DOMDocument();
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        $xml->xmlStandalone = true;
        $xml->validateOnParse = true;
        $xml->encoding = 'utf-8';
        $xml->version = '1.0';

        $autorizacion = $xml->createElement('autorizacion');
        $estadoNode = $xml->createElement('estado', $estado);
        $autorizacion->appendChild($estadoNode);
        $numeroAutorizacionNode = $xml->createElement('numeroAutorizacion', $numeroAutorizacion);
        $autorizacion->appendChild($numeroAutorizacionNode);
        $fechaAutorizacionNode = $xml->createElement('fechaAutorizacion', $fechaAutorizacion);
        $autorizacion->appendChild($fechaAutorizacionNode);
        $comprobanteCDATA = $xml->createCDATASection(utf8_encode($comprobante));
        $comprobanteNode = $xml->createElement('comprobante');
        $comprobanteNode->appendChild($comprobanteCDATA);
        $autorizacion->appendChild($comprobanteNode);
        $xml->appendChild($autorizacion);
        $xml->save($path);
    }

    public function registrarContingencia() {
        $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
        $idDocumento = 0;
        foreach ($Documento as $doc) {
            $idDocumento = $doc->id;
        }

        $Documento = DocumentoModel::find($idDocumento);

        $Documento->contingencia = 1;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 14",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function verIdUltimo() {
        $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
        $idDocumento = 0;
        foreach ($Documento as $doc) {
            $idDocumento = $doc->id;
        }
        return $idDocumento;
    }

    public function registrarLog($idDocumento, $log) {
        $Log = new LogsModel();
        $Log->documento_id = $idDocumento;
        $Log->log = $log;
        $Log->save();
    }

    public function registrarPath($idDocumento, $path, $codInterno) {
        //$Documento = DocumentoModel::find($idDocumento);
        $Documento = DocumentoModel::where('id',$idDocumento)
								   ->where('codigo_interno', $codInterno)
								   ->first();
        $Documento->path = $path;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 15",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function registrarErrorXml($documento, $error) {
        $Error = new ErrorModel();
        $Error->path = $documento;
        $Error->error = $error;

        $Error->save();
    }

    public function registrarError($documento, $error) {
        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);

        $numeroLegal = "" . $xml->infoTributaria->estab . $xml->infoTributaria->ptoEmi . $xml->infoTributaria->secuencial;
        $codInterno = "";

        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {

            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        $Error = new ErrorModel();
        $Error->path = $documento;
        $Error->error = $error;
        $Error->numero_legal = $numeroLegal;
        $Error->codigo_interno = $codInterno;

        $Error->save();
    }

    public function getDatosRespuesta($doc, $numeroAutorizacion = '', $fechaAutorizacion = '', $horaAutorizacion = '', $rechazo = '', $contingencia = '0', $origenError = '', $mensaje = '') {

        $string = file_get_contents($doc);
        $xml = simplexml_load_string($string);

        $codSociedad = '';
        $codInterno = '';
        $estab = '';
        $ptoEmi = '';
        $tipoEmision = '';
        $claveAcceso = '';
        $ambiente = '';
        $tipoDoc = '';

        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $tipoEmision = $key->valor;
        }

        $sql = "select valor from configuracion where dato='ambiente'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $ambiente = $key->valor;
        }

        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {

            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        //$tipoEmision="".$xml->infoTributaria->tipoEmision;
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
        //$ambiente="".$xml->infoTributaria->ambiente;
        $tipoDoc = "" . $xml->infoTributaria->codDoc;

        $mensaje = preg_replace("[\n|\r|\n\r]", '', $mensaje);

        $retorno = $codSociedad . ";" . $codInterno . ";" . $estab . ";" . $ptoEmi . ";" . $tipoEmision . ";" . $claveAcceso . ";" . $numeroAutorizacion . ";" . $ambiente . ";" . $fechaAutorizacion . ";" . $horaAutorizacion . ";" . $rechazo . ";" . $contingencia . ";" . $origenError . ";" . $mensaje . ";" . $tipoDoc;

        return $retorno;
    }

    public function generarHtml($idDocumento, $documento) {
		
        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);
		
		if(!isset($xml->infoAdicional->campoAdicional)){
			$xml = simplexml_load_string($xml->comprobante);//DPS
		}
		
        $Doc = DocumentoModel::find($idDocumento);
        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        //echo $Doc->codigo_interno;
        $numeroAutorizacion = $Doc->numero_autorizacion;
        $fechaAutorizacion = date("d/m/Y H:i:s", strtotime($Doc->fecha_autorizacion));
        $fechaEmision = date("d/m/Y", strtotime($Doc->fecha_emision));
        $razonSocial = $Doc->cliente->razon_social;
        $ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $codSociedad = '';
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';
        $numDocSustento = '';
        $numeroReferencia = '';
        $anunciante = '';
        $rucAnunciante = '';
        $tipo = '';
        $subtipo = '';
        $pagina = '';
        $color = '';
        $pago = '';
        $fechaPago = '';
        $valorPago = '';
        $emisorPago = '';
        $documentoPago = '';
        $observacionPago = '';
        $contrato = '';
        $otros = '';
        $agencia = '';
        $titulo = '';
        $subtotal2 = '';
        $porcentajeAdicionales = '0.00';
        $porcentajeDescuento = '0.00';
        $porcentajeOtros = '0.00';
        $porcentajeAgencia = '0.00';
        $porcentajeContrato = '0.00';
        $formaPago = '';
        $destinatario = "";
        $destino = "";
        $razonSocialTransportista = '';
        $rucTransportista = '';
        $placa = '';
        $dirEstablecimiento = '';
        $fechaIniTransporte = '';
        $fechaFinTransporte = "";
		
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
        }

        switch ($codDoc) {
            case '01':
                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    if (isset($campoAdicional1Array[2])) {
                        $ciudad = $campoAdicional1Array[2];
                    }
                    if (isset($campoAdicional1Array[1])) {
                        $localidad = $campoAdicional1Array[1];
                    }

                    $linea = $this->generarDetalleOPTFAC($xml->detalles->detalle);
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;

                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '';

                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;

                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    $campoAdicional3 = '';
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_optativo.html');
                }
				
                if ($unidadNegocio == 'CLASI') {
					
                    $campoAdicional4Array = explode('|', $campoAdicional4);

                    if (isset($campoAdicional4Array[0])) {
                        $medio = $campoAdicional4Array[0];
                    }
                    if (isset($campoAdicional4Array[1])) {
                        $codigoSeccion = $campoAdicional4Array[1];
                    }
                    if (isset($campoAdicional4Array[2])) {
                        $seccion = $campoAdicional4Array[2];
                    }
                    if (isset($campoAdicional4Array[3])) {
                        $marca = $campoAdicional4Array[3];
                    }
                    if (isset($campoAdicional4Array[4])) {
                        $modelo = $campoAdicional4Array[4];
                    }
										
                    $linea = $this->generarDetalleCLASIFAC($xml->detalles->detalle);
					
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $subtotalAD = $campoAdicional11Array[2];
                    $descuentos = $xml->infoFactura->totalDescuento;
                    $subtotalDD = $xml->infoFactura->totalSinImpuestos;
                    $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = $xml->infoFactura->importeTotal;
                    // $campoAdicional3='';
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_clasi.html');
                }
                //echo $unidadNegocio;
                if ($unidadNegocio == 'PUBLI') {
                    if ($codSociedad == 'IMC') {

                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        if (isset($campoAdicional1Array[2])) {
                            $ciudad = $campoAdicional1Array[2];
                        }

                        if (isset($campoAdicional1Array[3])) {
                            $direccion = $campoAdicional1Array[3];
                        }


                        $campoAdicional3Array = explode('|', $campoAdicional3);
                        $subTotal = $campoAdicional3Array[0];

                        if (isset($campoAdicional3Array[1])) {
                            $adicionales = $campoAdicional3Array[1];
                        }

                        if (isset($campoAdicional3Array[2])) {
                            $descuentos = $campoAdicional3Array[2];
                        }

                        if (isset($campoAdicional3Array[3])) {
                            $subtotal12 = $campoAdicional3Array[3];
                        }

                        if (isset($campoAdicional3Array[4])) {
                            $subtotal0 = $campoAdicional3Array[4];
                        }

                        if (isset($campoAdicional3Array[5])) {
                            $iva = $campoAdicional3Array[5];
                        }

                        if (isset($campoAdicional3Array[6])) {
                            $total = $campoAdicional3Array[6];
                        }

                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        if (isset($campoAdicional2Array[1])) {
                            $formaPago = $campoAdicional2Array[1];
                        }
                        $campoAdicional3 = '';
                        $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                    } else {
                        $campoAdicional7Array = explode('|', $campoAdicional7);
                        $numeroReferencia = $campoAdicional7Array[0];

                        $campoAdicional8Array = explode('|', $campoAdicional8);
                        $cliente = $campoAdicional8Array[0];

                        if (isset($campoAdicional8Array[1])) {
                            // $ruc = $campoAdicional8Array[1];
                            $rucAnunciante = $campoAdicional8Array[1];
                        }

                        $anunciante = $campoAdicional8Array[0];

                        $direccion = $campoAdicional10;

                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        $ciudad = $campoAdicional1Array[0];
                        if (isset($campoAdicional1Array[1])) {
                            $titulo = $campoAdicional1Array[1];
                        }
                        if (isset($campoAdicional1Array[3])) {
                            $fechaEmision = $campoAdicional1Array[3];
                        }
                        if (isset($campoAdicional1Array[2])) {
                            $medio = $campoAdicional1Array[2];
                        }

                        $campoAdicional4Array = explode('|', $campoAdicional4);
                        $tipo = $campoAdicional4Array[0];
                        if (isset($campoAdicional4Array[1])) {
                            $subtipo = $campoAdicional4Array[1];
                        }
                        if (isset($campoAdicional4Array[2])) {
                            $seccion = $campoAdicional4Array[2];
                        }
                        if (isset($campoAdicional4Array[3])) {
                            $pagina = $campoAdicional4Array[3];
                        }
                        if (isset($campoAdicional4Array[4])) {
                            $color = $campoAdicional4Array[4];
                        }

                        $campoAdicional11Array = explode('|', $campoAdicional11);
                        $subTotal = $campoAdicional11Array[0];

                        if (isset($campoAdicional11Array[1])) {
                            $porcentajeAdicionales = $campoAdicional11Array[1];
                        }
                        if (isset($campoAdicional11Array[2])) {
                            $adicionales = $campoAdicional11Array[2];
                        }
                        if (isset($campoAdicional11Array[3])) {
                            $subtotalAD = $campoAdicional11Array[3];
                        }

                        if (isset($campoAdicional11Array[4])) {
                            $porcentajeContrato = $campoAdicional11Array[4];
                        }

                        if (isset($campoAdicional11Array[5])) {
                            $contrato = $campoAdicional11Array[5];
                        }

                        if (isset($campoAdicional11Array[6])) {
                            $porcentajeOtros = $campoAdicional11Array[6];
                        }

                        if (isset($campoAdicional11Array[7])) {
                            $otros = $campoAdicional11Array[7];
                        }

                        if (isset($campoAdicional11Array[8])) {
                            $subtotal2 = $campoAdicional11Array[8];
                        }
                        if (isset($campoAdicional11Array[9])) {
                            $porcentajeAgencia = $campoAdicional11Array[9];
                        }

                        if (isset($campoAdicional11Array[10])) {
                            $agencia = $campoAdicional11Array[10];
                        }

                        if (isset($campoAdicional11Array[11])) {
                            $subtotalDD = $campoAdicional11Array[11];
                        }

                        $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                        $total = $xml->infoFactura->importeTotal;

                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        $pagosArray = explode(' ', $campoAdicional2Array[0]);
                        if (isset($pagosArray[0])) {
                            $pago = $pagosArray[0];
                        }
                        if (isset($pagosArray[1])) {
                            $fechaPago = $pagosArray[1];
                        }
                        if (isset($pagosArray[2])) {
                            $valorPago = $pagosArray[2];
                        }
                        if (isset($pagosArray[3])) {
                            $emisorPago = $pagosArray[3];
                        }
                        if (isset($pagosArray[4])) {
                            $documentoPago = $pagosArray[4];
                        }

                        if (isset($campoAdicional2Array[1])) {
                            $observacionPago = $campoAdicional2Array[1];
                        }

                        $linea = $this->generarDetallePUBLIFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_publicidad.html');
                    }
                }

				//JCARRILLO 02-ABR-2018
                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI' && $unidadNegocio != 'GENPU') {
                    $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                    $campoAdicional1Array = explode('|', $campoAdicional1);
					if(isset($campoAdicional1Array[2])){
						$ciudad = $campoAdicional1Array[2];
					}else{
						$ciudad = '';
					}
					if(isset($campoAdicional1Array[3])){
						$direccion = $campoAdicional1Array[3];
					}else{
						$direccion = '';
					}
                    $campoAdicional3Array = explode('|', $campoAdicional3);
					if(isset($campoAdicional3Array[0])){
						$subTotal = $campoAdicional3Array[0];
					}else{
						$subTotal = '';
					}
					if(isset($campoAdicional3Array[1])){
						$adicionales = $campoAdicional3Array[1];
					}else{
						$adicionales = '';
					}
					if(isset($campoAdicional3Array[2])){
						$descuentos = $campoAdicional3Array[2];
					}else{
						$descuentos = '';
					}
					if(isset($campoAdicional3Array[3])){
						$subtotal12 = $campoAdicional3Array[3];
					}else{
						$subtotal12 = '';
					}
					if(isset($campoAdicional3Array[4])){
						$subtotal0 = $campoAdicional3Array[4];
					}else{
						$subtotal0 = '';
					}
					if(isset($campoAdicional3Array[5])){
						$iva = $campoAdicional3Array[5];
					}else{
						$iva = '';
					}
					if(isset($campoAdicional3Array[6])){
						$total = $campoAdicional3Array[6];
					}else{
						$total = '';
					}
                    $campoAdicional3 = '';
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    if (isset($campoAdicional2Array[1])) {
                        $formaPago = $campoAdicional2Array[1];
                    }
                    if (isset($campoAdicional11)) {
                        $telefono = $campoAdicional11;
                    }
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                }
				
				//JCARRILLO 02-ABR-2018
                // if ($unidadNegocio == 'GENPU') {
				// 	
				// 	
                //     $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);					
				// 	$direccion = $campoAdicional1;
                //     $telefono = $campoAdicional12;
				// 	$subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                //     $descuentos = "" . $xml->infoFactura->totalDescuento;
                //     $subtotal0 = '0.00';				
				// 		
				// 	$subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                // 
                // 
				// 	$iva='0.00';
                // 
                //     foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                //         $iva+=(double)("".$imp->valor);
                //     }                   
                // 
                //     $total = "" . $xml->infoFactura->importeTotal;
				// 	
				// 	$codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                //     if ($codigoPorcentajeIva == '0') {
                //         $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                //         $subtotal12 = "0.00";
                //     }					
                // 
                //     $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_publicidad.html');
				// 	
                // }
		
				

                break;
            case '04':
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->generarDetalleOPTNC($xml->detalles->detalle);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    if (isset($campoAdicional11Array[1])) {
                        $descuentos = $campoAdicional11Array[1];
                    }

                    if (isset($campoAdicional11Array[2])) {
                        $subtotalDD = $campoAdicional11Array[2];
                    }
                    if (isset($campoAdicional11Array[5])) {
                        $iva = $campoAdicional11Array[5];
                    }
                    if (isset($campoAdicional11Array[6])) {
                        $total = $campoAdicional11Array[6];
                    }

                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito_optativo.html');
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiNC($xml->detalles->detalle);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito.html');
                }

                break;
            case '05':
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    if (isset($campoAdicional1Array[2])) {
                        $ciudad = $campoAdicional1Array[2];
                    }

                    $direccion = $campoAdicional10;
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);

                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                } else {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                }

                break;
            case '06':
                $dirEstablecimiento = "" . $xml->infoGuiaRemision->dirEstablecimiento;
                if (isset($xml->destinatarios->destinatario->razonSocialDestinatario)){
					$destinatario = "" . $xml->destinatarios->destinatario->razonSocialDestinatario;
				}else{
					$destinatario = "";
				}
				if(isset($xml->destinatarios->destinatario->identificacionDestinatario)){
					$ruc = "" . $xml->destinatarios->destinatario->identificacionDestinatario;
				}else{
					$ruc = "";
				}
				if(isset($xml->destinatarios->destinatario->dirDestinatario)){
					$destino = "" . $xml->destinatarios->destinatario->dirDestinatario;
				}else{
					$destino = "";
				}
                $razonSocialTransportista = '' . $xml->infoGuiaRemision->razonSocialTransportista;
                $rucTransportista = '' . $xml->infoGuiaRemision->rucTransportista;
                $placa = '' . $xml->infoGuiaRemision->placa;
                $dirEstablecimiento = '' . $xml->infoGuiaRemision->dirEstablecimiento;
                $fechaIniTransporte = '' . $xml->infoGuiaRemision->fechaIniTransporte;
                $fechaFinTransporte = "" . $xml->infoGuiaRemision->fechaFinTransporte;
				if(isset($xml->destinatarios->destinatario->numDocSustento)){
					$numDocSustento = "" . $xml->destinatarios->destinatario->numDocSustento;
				}else{
					$numDocSustento = "";
				}
                //if ($unidadNegocio == 'OPTAT') {
                $linea = $this->generarDetalleGUIA($xml->destinatarios->destinatario->detalles->detalle);
				//print_r($linea);
                $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/guia.html');
                //}
                break;

            case '07':
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                if (isset($campoAdicional1Array[1])) {
                    $direccion = $campoAdicional1Array[1];
                }
				
                $campoAdicional2Array = explode('|', $campoAdicional2);
                if (isset($campoAdicional2Array[2])) {
                    $telefono = $campoAdicional2Array[2];
                }

                if (isset($campoAdicional2Array[1])) {
                    $voucher = $campoAdicional2Array[1];
                }

                $campoAdicional7Array = explode('|', $campoAdicional7);

                if (isset($campoAdicional7Array[1])) {
                    $comprobante = $campoAdicional7Array[1];
                }
                if (isset($campoAdicional7Array[2])) {
                    $anio = $campoAdicional7Array[2];
                }

                $total = $campoAdicional7Array[0];


                $linea = $this->generarDetalleComRet($campoAdicional3, $campoAdicional4);
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $numDocSustento = $campoAdicional2Array[0];
                $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/retencion.html');
                break;
        }

        $datos = array(
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $fechaAutorizacion,
            'fechaEmision' => $fechaEmision,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => $descuentos,
            'subtotal12' => $subtotal12,
            'subtotal0' => $subtotal0,
            'iva' => $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => $voucher,
            'numDocSustento' => $numDocSustento,
            'numeroReferencia' => $numeroReferencia,
            'anunciante' => $anunciante,
            'rucAnunciante' => $rucAnunciante,
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'pagina' => $pagina,
            'color' => $color,
            'pago' => $pago,
            'fechaPago' => $fechaPago,
            'valorPago' => $valorPago,
            'emisorPago' => $emisorPago,
            'documentoPago' => $documentoPago,
            'observacionPago' => $observacionPago,
            'contrato' => $contrato,
            'otros' => $otros,
            'agencia' => $agencia,
            'titulo' => $titulo,
            'subtotal2' => $subtotal2,
            'porcentajeAdicionales' => $porcentajeAdicionales,
            'porcentajeDescuento' => $porcentajeDescuento,
            'porcentajeOtros' => $porcentajeOtros,
            'porcentajeAgencia' => $porcentajeAgencia,
            'porcentajeContrato' => $porcentajeContrato,
            'formaPago' => $formaPago,
            'destinatario' => $destinatario,
            'destino' => $destino,
            'razonSocialTransportista' => $razonSocialTransportista,
            'rucTransportista' => $rucTransportista,
            'placa' => $placa,
            'dirEstablecimiento' => $dirEstablecimiento,
            'fechaIniTransporte' => $fechaIniTransporte,
            'fechaFinTransporte' => $fechaFinTransporte
        );

        foreach ($datos as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        $Doc->html = $template;

        $Doc->save();
    }

    public function generarClaveAcceso($documento) {
        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);
        return $xml->infoTributaria->claveAcceso;
    }

    public function prueba_HeaderPdf($html, $numero, $archivo, $subruta = "") {
        $data['estab'] = "prueba stab";
        $data['ptoEmi'] = "prueba stab";
        $data['secuencial'] = "prueba stab";
        $data['numeroAutorizacion'] = "prueba stab";
        $data['fechaAutorizacion'] = "prueba stab";
        $data['ambiente'] = "prueba stab";
        $data['tipoEmision'] = "prueba stab";
        $data['dirMatriz'] = "prueba stab";
        $data['dirEstablecimiento'] = "prueba stab";
        $data['razonSocial'] = "prueba stab";
        $data['ruc'] = "prueba stab";
        $data['fechaEmision'] = "prueba stab";
        generar_pdf_test($html, $numero, $archivo, $subruta, $data);
    }

    public function re_generarPdf($idDocumento, $xml, $archivo, $subruta = "") {
			
        $Doc = DocumentoModel::find($idDocumento);
		
		if(!isset($xml->infoAdicional->campoAdicional)){
			$xml = simplexml_load_string($xml->comprobante);//DPS
		}
		
        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        $numeroAutorizacion = $Doc->numero_autorizacion;
        $fechaAutorizacion = $Doc->fecha_autorizacion;
        $fechaEmision = $Doc->fecha_emision;
        //$razonSocial = ($Doc->cliente->razon_social);  
        $razonSocial = 'DM';

        $ruc = '1716656952';
          //$ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $campoAdicional13 = '';
        $codSociedad = '';
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';
        $cm_tipo = "";
        $cm_numero = "";
        $cm_fecha = "";
        $cm_razon = "";
        $transportista = "";
        $rucTransportista = "";
        $fechaIniTransporte = "";
        $fechaFinTransporte = "";
        $identificacionDestinatario = "";
        $razonSocialDestinatario = "";
        $motivoTraslado = "";
        $codDocSustento = "";
        $numAutDocSustento = "";
        $numDocSustento = "";
        $fechaEmisionDocSustento = "";
        $placa = "";
        $dirPartida = "";
        $ruta = '';
        $tarifa = '';
        $destino = '';
		$mensajeJudicial='';
		$campoJudicial='';
		
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
				$campoJudicial="" . $xml->infoAdicional->campoAdicional[$i];
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
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional13') {
				$campoAdicional13 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
                $correo = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }
		
        if ($codSociedad != 'IMC' && $codSociedad != 'DIG' && $codSociedad != 'VAR') {
            $campoAdicional4 = '';
        }
		
		$Mensaje='SS';
		
		$patron = '/^J[1-9] JU/';
		
		foreach($Mensaje as $men){
			$mensajeJudicial=$men->mensaje;
		}
		
		if(preg_match ($patron,$campoJudicial)==0){
			$patron = '/^JUDICIALES/';		
			if(preg_match ($patron,$campoJudicial)==0){
				$patron = '/^COMERCIAL/';
				if(preg_match ($patron,$campoJudicial)==0){
					$patron = '/^NO COMERCIALES/';
					if(preg_match ($patron,$campoJudicial)==0){
						$mensajeJudicial='';						
					}
				}
				
			}
		
		}
		
        $formapago = array();

        switch ($codDoc) {
            case '01':
                $plantilla = 'ride.factura';
                $titulo_plantilla = 'F A C T U R A';

                if (isset($xml->infoFactura->pagos->pago)) {


                    foreach ($xml->infoFactura->pagos->pago as $pago) {
                        $nombre = FormaPagoModel::where('codigo', $pago->formaPago)->first();
                        $formapago1 = array();
                        $formapago1['descripcion'] = $nombre->descripcion;
                        $formapago1['total'] = $pago->total;
                        $formapago1['plazo'] = $pago->plazo;
                        $formapago1['unidadTiempo'] = $pago->unidadTiempo;
                        $formapago[] = $formapago1;
                    }
                }

                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    $ciudad = $campoAdicional1Array[2];
                    $localidad = $campoAdicional1Array[1];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    //IMPUESTOS//

                    /* $linea = array();
                      foreach ($detalles->detalle as $detalle) {
                      $subtotal0='0.00';
                      $subtotal12="".$xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                      $detalle->codigoPrincipal
                      } */

                   
                   // $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                      $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    $total = "" . $xml->infoFactura->importeTotal;
                    $campoAdicional3 = '';
                    $linea = $this->pdf_generarDetalleOPTFAC($xml->detalles);
                }

                if ($unidadNegocio == 'CLASI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    $ciudad = $campoAdicional2Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    
                    //$iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }


                    $total = "" . $xml->infoFactura->importeTotal;
                    //$campoAdicional3='';

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }

                if ($unidadNegocio == 'PUBLI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    
                    //$iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    $total = "" . $xml->infoFactura->importeTotal;

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    if ($codSociedad == 'IMC') {
                        $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);
                        $campoAdicional3 = $campoAdicional7;
                    } else
                        $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }
		//JCARRILLO 02-ABR-2018
                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI' && $unidadNegocio != 'GENPU' ) {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    
                    //$iva = $campoAdicional3Array[5];

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    $total = $campoAdicional3Array[6];
                    $campoAdicional3 = '';
                    $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);

                    if (count($xml->infoFactura->totalConImpuestos->totalImpuesto) == 1) {


                        $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                        if ($codigoPorcentajeIva == '0') {
                            $subtotal0 = $campoAdicional3Array[3];
                            $subtotal12 = $campoAdicional3Array[4];
                        }
                    }

                    if (isset($campoAdicional11)) {
                        $telefono = $campoAdicional11;
                    }
                    if ($codSociedad == 'IMC' || $codSociedad = 'SUS') {

                        $campoAdicional3 = $campoAdicional7;
                    }
                }

				//JCARRILLO 02-ABR-2018
                // if ($unidadNegocio == 'GENPU' ) {
				// 	
                //     $direccion = $campoAdicional1;
                //     $telefono = $campoAdicional12;
				// 	$subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                //     $descuentos = "" . $xml->infoFactura->totalDescuento;
                //     $subtotal0 = '0.00';
                //     $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                // 
				// 	$iva='0.00';
                // 
                //     foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                //         $iva+=(double)("".$imp->valor);
                //     }                   
                // 
                //     $total = "" . $xml->infoFactura->importeTotal;
				// 	
				// 	$codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                //     if ($codigoPorcentajeIva == '0') {
                //         $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                //         $subtotal12 = "0.00";
                //     }					
                // 
				// 	$linea = $this->pdf_generarDetalleERP($xml->detalles); 
				// 	
                //     if (isset($campoAdicional2)) {
                //         $telefono = $campoAdicional2;
                //     }
                // 
                // }				
				
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;

                /* $codigoPorcentajeIva="" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;

                  switch ($codigoPorcentajeIva) {
                  case '0':
                  $tarifa='0';
                  break;
                  case '2':
                  $tarifa='12';
                  break;
                  case '3':
                  $tarifa='14';
                  break;


                  } */


                if ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje == '2') {
                    $tarifa = '12';
                } else {
                    $tarifa = '14';
                }
				
				$patronConSAP='/^PUB[0-9]/';
				
				/*if(preg_match ($patronConSAP,$codInternoSAP)!=0){
					$mensajeArray=explode('|',$campoAdicional1);
					$campoAdicional3=$mensajeArray[1];
				}*/
				
				$subtotal12='0.00';
				$subtotal0='0.00';
				$tarifa='12';
				
				foreach($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp){
					if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3' ){
						$subtotal12="".$imp->baseImponible;
						if("".$imp->codigoPorcentaje=='3' ){
							$tarifa='14';

						}
					}
					
				}
				
				foreach($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp){
					if("".$imp->codigoPorcentaje=='0'){
						$subtotal0="".$imp->baseImponible;
					}
				}
				
				
                break;
				
            case '04':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                if ($xml->infoNotaCredito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaCredito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaCredito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaCredito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaCredito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_credito';
                $titulo_plantilla = 'NOTA DE CRÉDITO';
                $cm_numero = $xml->infoNotaCredito->numDocModificado;
                $cm_fecha = $xml->infoNotaCredito->fechaEmisionDocSustento;
                $cm_razon = $xml->infoNotaCredito->motivo;

                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->pdf_generarDetalleOPTNC($xml->detalles);
                    $campoAdicional11Array = explode('|', $campoAdicional11);

                    $subTotal = $campoAdicional11Array[0];
                    if (isset($campoAdicional11Array[1])) {
                        $descuentos = $campoAdicional11Array[1];
                    }
                    if (isset($campoAdicional11Array[2])) {
                        $subtotalDD = $campoAdicional11Array[2];
                    }
                    if (isset($campoAdicional11Array[5])) {
                        $iva = $campoAdicional11Array[5];
                    }
                    if (isset($campoAdicional11Array[6])) {
                        $total = $campoAdicional11Array[6];
                    }
					
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiNC($xml->detalles);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }

					$subtotal12='0.00';
					$subtotal0='0.00';
					$tarifa='12';
					$impuestos=$xml->infoNotaCredito->totalConImpuestos->totalImpuesto;
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='0'){
							$subtotal0=''.$imp->baseImponible;
						}
						
					}
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3'){
							$subtotal12=''.$imp->baseImponible;
							if("".$imp->codigoPorcentaje=='3'){
								$tarifa='14';
							}
						}
						
					}

                break;
            case '05':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;

                if ($xml->infoNotaDebito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaDebito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaDebito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaDebito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaDebito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_dedito';
                $titulo_plantilla = 'NOTA DE DÉBITO';
                $cm_numero = $xml->infoNotaDebito->numDocModificado;
                $cm_fecha = $xml->infoNotaDebito->fechaEmisionDocSustento;
                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional10;
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                } else {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }

					$subtotal12='0.00';
					$subtotal0='0.00';
					$tarifa='12';
					$impuestos=$xml->infoNotaDebito->totalConImpuestos->totalImpuesto;
					
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='0'){
							$subtotal0=''.$imp->baseImponible;
						}
						
					}
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3'){
							$subtotal12=''.$imp->baseImponible;
							if("".$imp->codigoPorcentaje=='3'){
								$tarifa='14';
							}
						}
						
					}

                break;
            case '06':
			
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoGuiaRemision->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                $plantilla = 'ride.guia';
                $titulo_plantilla = 'GUÍA DE REMISIÓN';
                $transportista = $xml->infoGuiaRemision->razonSocialTransportista;
                $rucTransportista = $xml->infoGuiaRemision->rucTransportista;
                $fechaIniTransporte = $xml->infoGuiaRemision->fechaIniTransporte;
                $fechaFinTransporte = $xml->infoGuiaRemision->fechaFinTransporte;
                $identificacionDestinatario = $xml->destinatarios->destinatario->identificacionDestinatario;
                $razonSocialDestinatario = $xml->destinatarios->destinatario->razonSocialDestinatario;
                $motivoTraslado = $xml->destinatarios->destinatario->motivoTraslado;
				if(isset($xml->destinatarios->destinatario->codDocSustento)){
					$codDocSustento = $xml->destinatarios->destinatario->codDocSustento;
				}else{
					$codDocSustento ="";
				}
                $numAutDocSustento = $xml->destinatarios->destinatario->numAutDocSustento;
                $numDocSustento = $xml->destinatarios->destinatario->numDocSustento;
                $fechaEmisionDocSustento = $xml->destinatarios->destinatario->fechaEmisionDocSustento;
                $placa = $xml->infoGuiaRemision->placa;
                $ruta = $xml->destinatarios->destinatario->ruta;
                $dirPartida = $xml->infoGuiaRemision->dirPartida;
                $tarifa = '';
                $destino = "" . $xml->destinatarios->destinatario->dirDestinatario;
                $direccion = $campoAdicional5;
				print_r ($unidadNegocio);
				$linea = $this->pdf_generarDetalleGUIA($xml->destinatarios->destinatario->detalles);
                /*if ($unidadNegocio == 'OPTAT') {
                    $linea = $this->pdf_generarDetalleGUIA($xml->destinatarios->destinatario->detalles);
                }*/
				print_r ($linea);

                break;
            case '07':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                $impuestos = $xml->impuestos;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                $direccion = $campoAdicional1Array[1];
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $telefono = $campoAdicional2Array[2];
                $voucher = $campoAdicional2Array[1];
                $campoAdicional7Array = explode('|', $campoAdicional7);
                $comprobante = $campoAdicional7Array[1];
                $anio = $campoAdicional7Array[2];
                $total = $campoAdicional7Array[0];
                $linea = $this->pdf_generarDetalleComRet($campoAdicional3, $campoAdicional4, $impuestos->impuesto, $xml->infoCompRetencion->periodoFiscal);
                $plantilla = 'ride.retencion';
                $titulo_plantilla = 'COMPROBANTE DE RETENCIÓN';

                /*
                  if ("" . $xml->infoCompRetencion->totalConImpuestos->totalImpuesto->codigoPorcentaje == '2') {
                  $tarifa = '12';
                  } else {
                  $tarifa = '14';
                  } */

                $tarifa = '';

                break;
            default :
                $claveAcceso = "";
                $dirEstablecimiento = "";
                $dirMatriz = "";
                $impuestos = "";
                $campoAdicional1Array = "";
                $ciudad = "";
                $direccion = "";
                $campoAdicional2Array = "";
                $telefono = "";
                $voucher = "";
                $campoAdicional7Array = "";
                $comprobante = "";
                $anio = "";
                $total = "";
                $linea = "";
                break;
        }

        if ($xml->infoTributaria->ambiente == 1) {
            $ambiente = "PRUEBAS";
        } elseif ($xml->infoTributaria->ambiente == 2) {
            $ambiente = "PRODUCCION";
        }
        if ($xml->infoTributaria->tipoEmision == 1) {
            $tipoEmision = "NORMAL";
        } elseif ($xml->infoTributaria->tipoEmision == 2) {
            $tipoEmision = "CONTINGENCIA";
        }

        $datos = array(
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => date("d/m/Y H:i:s", strtotime($fechaAutorizacion)),
            'fechaEmision' => date("d/m/Y", strtotime($fechaEmision)),
            'razonSocialEmpresa' => $xml->infoTributaria->razonSocial,
            'contribuyenteEspecial' => $xml->infoCompRetencion->contribuyenteEspecial,
            'obligadoContabilidad' => $xml->infoCompRetencion->obligadoContabilidad,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'campoAdicional13' => $campoAdicional13,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'dirMatriz' => $dirMatriz,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => "" . $descuentos,
            'subtotal12' => "" . $subtotal12,
            'subtotal0' => "" . $subtotal0,
            'iva' => "" . $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => "" . $voucher,
            'correo' => $correo,
            'clave_acceso' => $claveAcceso,
            'ambiente' => "" . $ambiente,
            'tipoEmision' => "" . $tipoEmision,
            'irbpnr' => '0.00',
            'propina' => "" . $xml->infoFactura->propina,
            'ice' => '0.00',
            'subtotal_exiva' => '0.00',
            'subtotal_noiva' => '0.00',
            'titulo_plantilla' => $titulo_plantilla,
            'cm_tipo' => "" . $cm_tipo,
            'cm_numero' => "" . $cm_numero,
            'cm_razon' => "" . $cm_razon,
            'cm_fecha' => "" . $cm_fecha,
            'codDoc' => "" . $codDoc,
            'tarifa' => "" . $tarifa,
            'transportista' => $transportista,
            'rucTransportista' => $rucTransportista,
            'fechaIniTransporte' => $fechaIniTransporte,
            'fechaFinTransporte' => $fechaFinTransporte,
            'identificacionDestinatario' => $identificacionDestinatario,
            'razonSocialDestinatario' => $razonSocialDestinatario,
            'motivoTraslado' => $motivoTraslado,
            'codDocSustento' => $codDocSustento,
            'numAutDocSustento' => $numAutDocSustento,
            'numDocSustento' => $numDocSustento,
            'fechaEmisionDocSustento' => $fechaEmisionDocSustento,
            'placa' => $placa,
            'dirPartida' => $dirPartida,
            'ruta' => $ruta,
            'destino' => $destino,
            'formapago' => $formapago,
			'mensajeJudicial'=>$mensajeJudicial
        );

        //return $datos;
		
        $view = \View::make($plantilla, $datos)->__toString();;
		//\Log::useDailyFiles(storage_path().'/logs/PDF.log');
		//\Log::error(['Documentos'=>"PDF", 'view'=>$view]);
			
        $html = (string) $view;
		
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
		
        echo $archivo . "---" . $subruta . "--" . $claveAcceso . "\n\n";
        //generar_pdf($html, $claveAcceso, $archivo, $subruta);
        generar_pdf_test($html, $claveAcceso, $archivo, $subruta, $datos);
    }

    public function generarPdf($idDocumento, $documento, $archivo, $subruta = "") {
			
        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);
		
		if(!isset($xml->infoAdicional->campoAdicional)){
			$xml = simplexml_load_string($xml->comprobante);//DPS
		}
		
        $Doc = DocumentoModel::find($idDocumento);
        


        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        $numeroAutorizacion = $Doc->numero_autorizacion;
        $fechaAutorizacion = $Doc->fecha_autorizacion;
        $fechaEmision = $Doc->fecha_emision;
		
       // JS 22MAR2019 $razonSocial = utf8_encode($Doc->cliente->razon_social);
		//$razonSocial = ($Doc->cliente->razon_social);
        $razonSocial = ('DM');
	
        $ruc = '1716656952';
         //$ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $campoAdicional13 = '';
        $codSociedad = $Doc->cod_sociedad;
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';
        $cm_tipo = "";
        $cm_numero = "";
        $cm_fecha = "";
        $cm_razon = "";
        $tarifa = "";
        $transportista = "";
        $rucTransportista = "";
        $fechaIniTransporte = "";
        $fechaFinTransporte = "";
        $identificacionDestinatario = "";
        $razonSocialDestinatario = "";
        $motivoTraslado = "";
        $codDocSustento = "";
        $numAutDocSustento = "";
        $numDocSustento = "";
        $fechaEmisionDocSustento = "";
        $placa = "";
        $dirPartida = "";
        $ruta = '';
        $tarifa = '';
        $destino = '';
		
		$mensajeJudicial='';
		$campoJudicial='';
		
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
				$campoJudicial = "" . $xml->infoAdicional->campoAdicional[$i];
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
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional13') {
                $campoAdicional13 = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CorreoCliente') {
                $correo = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        if ($codSociedad != 'IMC' && $codSociedad != 'DIG' && $codSociedad != 'VAR') {
            $campoAdicional4 = '';
        }
		


						   
  
							
								  
   
  
  
						   
  
							
								  
   
  
		
											 
							   
											  
							 
											   
								   
												
																		
	  
	 
	
	
  
   
  
        $formapago = array();
        switch ($codDoc) {
            case '01':
                $plantilla = 'ride.factura';
                $titulo_plantilla = 'F A C T U R A';

 


																	   
																							 
											  
																		  
															
															
																		  
												   
					 
				 


                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    $ciudad = $campoAdicional1Array[2];
                    $localidad = $campoAdicional1Array[1];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    //IMPUESTOS//

                    /* $linea = array();
                      foreach ($detalles->detalle as $detalle) {
                      $subtotal0='0.00';
                      $subtotal12="".$xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                      $detalle->codigoPrincipal
                      } */

                   
                   // $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                      $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    $total = "" . $xml->infoFactura->importeTotal;
                    $campoAdicional3 = '';
                    $linea = $this->pdf_generarDetalleOPTFAC($xml->detalles);
                }

                if ($unidadNegocio == 'CLASI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    $ciudad = $campoAdicional2Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    

                    //$iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }


                    $total = "" . $xml->infoFactura->importeTotal;
                    //$campoAdicional3='';

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }

                if ($unidadNegocio == 'PUBLI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    

                    //$iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    

                    $total = "" . $xml->infoFactura->importeTotal;

                    $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                    if ($codigoPorcentajeIva == '0') {
                        $subtotal0 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                        $subtotal12 = "0.00";
                    }

                    if ($codSociedad == 'IMC') {
                        $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);
                        $campoAdicional3 = $campoAdicional7;
                    } else
                        $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }

					//JCARRILLO 02-ABR-2018 
                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI' && $unidadNegocio != 'GENPU') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    
                    //$iva = $campoAdicional3Array[5];

                    $iva='0.00';

                    foreach ($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp) {
                        $iva+=(double)("".$imp->valor);
                    }

                    $total = $campoAdicional3Array[6];
                    $campoAdicional3 = '';
                    $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);



                    if (count($xml->infoFactura->totalConImpuestos->totalImpuesto) == 1) {


                        $codigoPorcentajeIva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje;
                        if ($codigoPorcentajeIva == '0') {
                            $subtotal0 = $campoAdicional3Array[3];
                            $subtotal12 = $campoAdicional3Array[4];
                        }
                    }


                    if (isset($campoAdicional11)) {
                        $telefono = $campoAdicional11;
                    }
                    if ($codSociedad == 'IMC' || $codSociedad = 'SUS') {

                        $campoAdicional3 = $campoAdicional7;
                    }
                }
								
				
						   
													
		
													 
													 
																							 
																			
										   
																																				 
				   
					
				   
																																									  
														  
										   
				   
																	 
		
																																			 
														 
																																					 
												
							 
				   
																							  
		
													 
														
						
				   
						 
	
				
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;

																																						 

												 
						   
							  
						
						   
							   
						
						   
							   
						


					  


                if ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->codigoPorcentaje == '2') {
                    $tarifa = '12';
                } else {
                    $tarifa = '14';
                }
				
				$patronConSAP='/^PUB[0-9]/';
				
				

												 
									   
	   
				
				$subtotal12='0.00';
				$subtotal0='0.00';
				$tarifa='12';
				
				foreach($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp){
					if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3' ){
						$subtotal12="".$imp->baseImponible;
						if("".$imp->codigoPorcentaje=='3' ){
							$tarifa='14';

						}
					}
					
				}
				
				foreach($xml->infoFactura->totalConImpuestos->totalImpuesto as $imp){
					if("".$imp->codigoPorcentaje=='0'){
						$subtotal0="".$imp->baseImponible;
					}
				}
				
				
                break;
				
            case '04':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                if ($xml->infoNotaCredito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaCredito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaCredito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaCredito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaCredito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_credito';
                $titulo_plantilla = 'NOTA DE CRÉDITO';
                $cm_numero = $xml->infoNotaCredito->numDocModificado;
                $cm_fecha = $xml->infoNotaCredito->fechaEmisionDocSustento;
                $cm_razon = $xml->infoNotaCredito->motivo;

                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->pdf_generarDetalleOPTNC($xml->detalles);
                    $campoAdicional11Array = explode('|', $campoAdicional11);

                    $subTotal = $campoAdicional11Array[0];
                    if (isset($campoAdicional11Array[1])) {
                        $descuentos = $campoAdicional11Array[1];
                    }
                    if (isset($campoAdicional11Array[2])) {
                        $subtotalDD = $campoAdicional11Array[2];
                    }
                    if (isset($campoAdicional11Array[5])) {
                        $iva = $campoAdicional11Array[5];
                    }
                    if (isset($campoAdicional11Array[6])) {
                        $total = $campoAdicional11Array[6];
                    }
					
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiNC($xml->detalles);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }

					$subtotal12='0.00';
					$subtotal0='0.00';
					$tarifa='12';
					$impuestos=$xml->infoNotaCredito->totalConImpuestos->totalImpuesto;
					
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='0'){
							$subtotal0=''.$imp->baseImponible;
						}
						
					}
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3'){
							$subtotal12=''.$imp->baseImponible;
							if("".$imp->codigoPorcentaje=='3'){
								$tarifa='14';
							}
						}
						
					}


                break;
            case '05':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;

                if ($xml->infoNotaDebito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaDebito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaDebito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaDebito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaDebito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_dedito';
                $titulo_plantilla = 'NOTA DE DÉBITO';
                $cm_numero = $xml->infoNotaDebito->numDocModificado;
                $cm_fecha = $xml->infoNotaDebito->fechaEmisionDocSustento;
                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional10;
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                } else {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }

					$subtotal12='0.00';
					$subtotal0='0.00';
					$tarifa='12';
					$impuestos=$xml->infoNotaDebito->totalConImpuestos->totalImpuesto;
					
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='0'){
							$subtotal0=''.$imp->baseImponible;
						}
						
					}
					
					foreach($impuestos as $imp){
						if("".$imp->codigoPorcentaje=='2' || "".$imp->codigoPorcentaje=='3'){
							$subtotal12=''.$imp->baseImponible;
							if("".$imp->codigoPorcentaje=='3'){
								$tarifa='14';
							}
						}
						
					}

                break;
            case '06':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoGuiaRemision->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                $plantilla = 'ride.guia';
                $titulo_plantilla = 'GUÍA DE REMISIÓN';
                $transportista = $xml->infoGuiaRemision->razonSocialTransportista;
                $rucTransportista = $xml->infoGuiaRemision->rucTransportista;
                $fechaIniTransporte = $xml->infoGuiaRemision->fechaIniTransporte;
                $fechaFinTransporte = $xml->infoGuiaRemision->fechaFinTransporte;
                $identificacionDestinatario = $xml->destinatarios->destinatario->identificacionDestinatario;
                $razonSocialDestinatario = $xml->destinatarios->destinatario->razonSocialDestinatario;
                $motivoTraslado = $xml->destinatarios->destinatario->motivoTraslado;
                $codDocSustento = $xml->destinatarios->destinatario->codDocSustento;
                $numAutDocSustento = $xml->destinatarios->destinatario->numAutDocSustento;
                $numDocSustento = $xml->destinatarios->destinatario->numDocSustento;
                $fechaEmisionDocSustento = $xml->destinatarios->destinatario->fechaEmisionDocSustento;
                $placa = $xml->infoGuiaRemision->placa;
                $ruta = $xml->destinatarios->destinatario->ruta;
                $dirPartida = $xml->infoGuiaRemision->dirPartida;
                $tarifa = '';
                $destino = "" . $xml->destinatarios->destinatario->dirDestinatario;
                $direccion = $campoAdicional5;
                //if ($unidadNegocio == 'OPTAT') {
                $linea = $this->pdf_generarDetalleGUIA($xml->destinatarios->destinatario->detalles);
                //}

                break;
            case '07':
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                $impuestos = $xml->impuestos;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                $direccion = $campoAdicional1Array[1];
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $telefono = $campoAdicional2Array[2];
                $voucher = $campoAdicional2Array[1];
                $campoAdicional7Array = explode('|', $campoAdicional7);
                $comprobante = $campoAdicional7Array[1];
                $anio = $campoAdicional7Array[2];
                $total = $campoAdicional7Array[0];
                $linea = $this->pdf_generarDetalleComRet($campoAdicional3, $campoAdicional4, $impuestos->impuesto, $xml->infoCompRetencion->periodoFiscal);
                $plantilla = 'ride.retencion';
                $titulo_plantilla = 'COMPROBANTE DE RETENCIÓN';

                /*
                  if ("" . $xml->infoCompRetencion->totalConImpuestos->totalImpuesto->codigoPorcentaje == '2') {
                  $tarifa = '12';
                  } else {
                  $tarifa = '14';
                  } */

                $tarifa = '';

                break;
            default :
                $claveAcceso = "";
                $dirEstablecimiento = "";
                $dirMatriz = "";
                $impuestos = "";
                $campoAdicional1Array = "";
                $ciudad = "";
                $direccion = "";
                $campoAdicional2Array = "";
                $telefono = "";
                $voucher = "";
                $campoAdicional7Array = "";
                $comprobante = "";
                $anio = "";
                $total = "";
                $linea = "";
                break;
        }

        if ($xml->infoTributaria->ambiente == 1) {
            $ambiente = "PRUEBAS";
        } elseif ($xml->infoTributaria->ambiente == 2) {
            $ambiente = "PRODUCCION";
        }
        if ($xml->infoTributaria->tipoEmision == 1) {
            $tipoEmision = "NORMAL";
        } elseif ($xml->infoTributaria->tipoEmision == 2) {
            $tipoEmision = "CONTINGENCIA";
        }

        $datos = array(
            'unidadNegocio' => $unidadNegocio,
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => date("d/m/Y H:i:s", strtotime($fechaAutorizacion)),
            'fechaEmision' => date("d/m/Y", strtotime($fechaEmision)),
            'razonSocialEmpresa' => $xml->infoTributaria->razonSocial,
            'contribuyenteEspecial' => $xml->infoCompRetencion->contribuyenteEspecial,
            'obligadoContabilidad' => $xml->infoCompRetencion->obligadoContabilidad,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'campoAdicional13' => $campoAdicional13,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'dirMatriz' => $dirMatriz,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => "" . $descuentos,
            'subtotal12' => "" . $subtotal12,
            'subtotal0' => "" . $subtotal0,
            'iva' => "" . $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => "" . $voucher,
            'correo' => $correo,
            'clave_acceso' => $claveAcceso,
            'ambiente' => "" . $ambiente,
            'tipoEmision' => "" . $tipoEmision,
            'irbpnr' => '0.00',
            'propina' => "" . $xml->infoFactura->propina,
            'ice' => '0.00',
            'subtotal_exiva' => '0.00',
            'subtotal_noiva' => '0.00',
            'titulo_plantilla' => $titulo_plantilla,
            'cm_tipo' => "" . $cm_tipo,
            'cm_numero' => "" . $cm_numero,
            'cm_razon' => "" . $cm_razon,
            'cm_fecha' => "" . $cm_fecha,
            'codDoc' => "" . $codDoc,
            'tarifa' => "" . $tarifa,
            'transportista' => $transportista,
            'rucTransportista' => $rucTransportista,
            'fechaIniTransporte' => $fechaIniTransporte,
            'fechaFinTransporte' => $fechaFinTransporte,
            'identificacionDestinatario' => $identificacionDestinatario,
            'razonSocialDestinatario' => $razonSocialDestinatario,
            'motivoTraslado' => $motivoTraslado,
            'codDocSustento' => $codDocSustento,
            'numAutDocSustento' => $numAutDocSustento,
            'numDocSustento' => $numDocSustento,
            'fechaEmisionDocSustento' => $fechaEmisionDocSustento,
            'placa' => $placa,
            'dirPartida' => $dirPartida,
            'ruta' => $ruta,
            'destino' => $destino,
            'formapago' => $formapago,
			'mensajeJudicial'=>$mensajeJudicial
        );



        //return $datos;
        $view = \View::make($plantilla, $datos);
        $html = (string) $view;
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
		
		generar_pdf_test($html, $claveAcceso, $archivo, $subruta, $datos);
		
        return "ok";
    }

/////////////////////DETALLES PDF//////////////////////////////

 private function pdf_generarDetalleERP($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {
                    if ($det['@attributes']['nombre'] == "detalle1")
                        $valor1 = $det['@attributes']['valor'];
                    if ($det['@attributes']['nombre'] == "detalle2")
                        $valor2 = $det['@attributes']['valor'];
                    if ($det['@attributes']['nombre'] == "detalle3")
                        $valor3 = $det['@attributes']['valor'];
                }
                $linea[] = array(
                    'codigo_principal' => "" . $detalle->codigoPrincipal,
                    'codigo_auxiliar' => "" . $detalle->codigoAuxiliar,
                    'cantidad' => "" . $detalle->cantidad,
                    'descripcion' => "" . $detalle->descripcion,
                    'detalle_ad1' => $valor1,
                    'detalle_ad2' => $valor2,
                    'detalle_ad3' => $valor3,
                    'precio_unitario' => "" . $detalle->precioUnitario,
                    'descuento' => "" . $detalle->descuento,
                    'precio_total' => "" . $detalle->precioTotalSinImpuesto
                );
            }
        }
        return $linea;
    }

    private function pdf_generarDetalleClasiND($motivos) {
        $linea = array();
        foreach ($motivos->motivo as $detalle) {
            $linea[] = array(
                'razon' => "" . $detalle->razon,
                'valor' => "" . $detalle->valor
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleClasiNC($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            $detAdicional = $array['detAdicional'];
            foreach ($detAdicional as $det) {

                $valor = $det['valor'];
            }
            $linea[] = array(
                'codigo_principal' => "" . $detalle->codigoInterno,
                'codigo_auxiliar' => "" . $detalle->codigoAdicional,
                'cantidad' => "" . $detalle->cantidad,
                'descripcion' => "" . $detalle->descripcion,
                'detalle_ad1' => $valor,
                'detalle_ad2' => ' ',
                'detalle_ad3' => ' ',
                'precio_unitario' => "" . $detalle->precioUnitario,
                'descuento' => "" . $detalle->descuento,
                'precio_total' => "" . $detalle->precioTotalSinImpuesto
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleOPTNC($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            $valor1 = '0.0';
            $valor2 = '0.0';
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {
                    if ($det['@attributes']['nombre'] == "detalle1")
                        $valor1 = $det['@attributes']['valor'];
                    if ($det['@attributes']['nombre'] == "detalle2")
                        $valor2 = $det['@attributes']['valor'];
                }
            }

            $linea[] = array(
                'codigo_principal' => "" . $detalle->codigoInterno,
                'codigo_auxiliar' => "" . $detalle->codigoAdicional,
                'cantidad' => "" . $detalle->cantidad,
                'descripcion' => "" . $detalle->descripcion,
                'detalle_ad1' => $valor1,
                'detalle_ad2' => $valor2,
                'detalle_ad3' => ' ',
                'precio_unitario' => "" . $detalle->precioUnitario,
                'descuento' => "" . $detalle->descuento,
                'precio_total' => "" . $detalle->precioTotalSinImpuesto
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleGUIA($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {
                    if ($det['nombre'] == "detalle1")
                        $valor1 = $det['valor'];
                }
            }

            $linea[] = array(
                'cantidad' => "" . $detalle->cantidad,
                'descripcion' => "" . $detalle->descripcion,
                'codigo_principal' => "" . $detalle->codigoInterno,
                'codigo_auxiliar' => "" . $detalle->codigoAdicional
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleDISFAC($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            $detAdicional = $array['detAdicional'];
            foreach ($detAdicional as $det) {

                $valor = $det['valor'];
            }
            $linea[] = array(
                'codigo_principal' => "" . $detalle->codigoPrincipal,
                'codigo_auxiliar' => "" . $detalle->codigoAuxiliar,
                'cantidad' => "" . $detalle->cantidad,
                'descripcion' => "" . $detalle->descripcion,
                'detalle_ad1' => $valor,
                'detalle_ad2' => ' ',
                'detalle_ad3' => ' ',
                'precio_unitario' => "" . $detalle->precioUnitario,
                'descuento' => "" . $detalle->descuento,
                'precio_total' => "" . $detalle->precioTotalSinImpuesto
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleOPTFAC($detalles) {

        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];

                foreach ($detAdicional as $det) {
                    if (isset($det['@attributes'])) {
                        if ($det['@attributes']['nombre'] == "detalle1")
                            $valor1 = $det['@attributes']['valor'];
                        if ($det['@attributes']['nombre'] == "detalle2")
                            $valor2 = $det['@attributes']['valor'];
                    }
                    else {
                        $valor_array = explode('|', $det['@attributes']['valor']);
                        $valor1 = $valor_array[0];
                        $valor2 = $valor_array[1];
                    }
                }
            } else {
                $valor1 = "";
                $valor2 = "";
            }
            $linea[] = array(
                'codigo_principal' => "" . $detalle->codigoPrincipal,
                'codigo_auxiliar' => "" . $detalle->codigoAuxiliar,
                'cantidad' => "" . $detalle->cantidad,
                'descripcion' => "" . $detalle->descripcion,
                'detalle_ad1' => $valor1,
                'detalle_ad2' => $valor2,
                'detalle_ad3' => ' ',
                'precio_unitario' => "" . $detalle->precioUnitario,
                'descuento' => "" . $detalle->descuento,
                'precio_total' => "" . $detalle->precioTotalSinImpuesto
            );
        }
        return $linea;
    }

    private function pdf_generarDetalleCLASIFAC($detalles) {
        $linea = array();
        foreach ($detalles->detalle as $detalle) {
            $array = json_decode(json_encode($detalle->detallesAdicionales), true);
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {
                    if ($det['@attributes']['nombre'] == "detalle1")
                        $valor1 = $det['@attributes']['valor'];
                    if ($det['@attributes']['nombre'] == "detalle2")
                        $valor2 = $det['@attributes']['valor'];
                    if ($det['@attributes']['nombre'] == "detalle3")
                        $valor3 = $det['@attributes']['valor'];
                }
                $linea[] = array(
                    'codigo_principal' => "" . $detalle->codigoPrincipal,
                    'codigo_auxiliar' => "" . $detalle->codigoAuxiliar,
                    'cantidad' => "" . $detalle->cantidad,
                    'descripcion' => "" . $detalle->descripcion,
                    'detalle_ad1' => $valor1,
                    'detalle_ad2' => $valor2,
                    'detalle_ad3' => $valor3,
                    'precio_unitario' => "" . $detalle->precioUnitario,
                    'descuento' => "" . $detalle->descuento,
                    'precio_total' => "" . $detalle->precioTotalSinImpuesto
                );
            }
        }
        return $linea;
    }

    private function pdf_generarDetalleComRetOLD($campoAdicional3, $campoAdicional4, $impuestos, $periodo) {

        $linea = '';

        $datos = explode('|', $campoAdicional4);
        if (count($datos) > 0) {
            $array = json_decode(json_encode($impuestos->impuesto[0]), true);
            if ($array['codDocSustento'] == "01")
                $comprobante = "FACTURA";
            elseif ($array['codDocSustento'] == "04")
                $comprobante = "NOTA DE CRÉDITO";
            elseif ($array['codDocSustento'] == "05")
                $comprobante = "NOTA DE DÉBITO";
            elseif ($array['codDocSustento'] == "06")
                $comprobante = "GUÍA DE REMISIÓN";

            elseif ($array['codDocSustento'] == "07")
                $comprobante = "COMPROBANTE DE RETENCIÓN";

            $linea1 = array(
                'comprobante' => $comprobante,
                'numero' => $array['numDocSustento'],
                'fecha_emision' => $array['fechaEmisionDocSustento'],
                'ejercicio_fiscal' => $periodo,
                'base' => $array['baseImponible'],
                'impuesto' => $datos[0],
                'porcentaje_retencion' => $array['porcentajeRetener'],
                'retencion' => $array['valorRetenido']
            );
        }

        $datos = explode('|', $campoAdicional3);
        if (count($datos) > 0) {
            $array = json_decode(json_encode($impuestos->impuesto[1]), true);
            if ($array['codDocSustento'] == "01")
                $comprobante = "FACTURA";
            elseif ($array['codDocSustento'] == "04")
                $comprobante = "NOTA DE CRÉDITO";
            elseif ($array['codDocSustento'] == "05")
                $comprobante = "NOTA DE DÉBITO";
            elseif ($array['codDocSustento'] == "06")
                $comprobante = "GUÍA DE REMISIÓN";

            $linea2 = array(
                'comprobante' => $comprobante,
                'numero' => $array['numDocSustento'],
                'fecha_emision' => $array['fechaEmisionDocSustento'],
                'ejercicio_fiscal' => $periodo,
                'base' => $array['baseImponible'],
                'impuesto' => $datos[0],
                'porcentaje_retencion' => $array['porcentajeRetener'],
                'retencion' => $array['valorRetenido']
            );
        }

        return array($linea1, $linea2);
    }

    private function pdf_generarDetalleComRet($campoAdicional3, $campoAdicional4, $impuestos, $periodo) {
        $linea1 = array();

        foreach ($impuestos as $impuesto) {
            $comprobante = '';
            if ($impuesto->codigo == '1') {
                switch ($impuesto->codDocSustento) {
                    case '01':
                        $comprobante = "FACTURA";
                        break;
                    case '04';
                        $comprobante = "NOTA DE CRÉDITO";
                        break;
                    case '05':
                        $comprobante = "NOTA DE DÉBITO";
                        break;
                    case '06':
                        $comprobante = "GUÍA DE REMISIÓN";
                        break;
                    case '07':
                        $comprobante = "COMPROBANTE DE RETENCIÓN";
                        break;
                }

                $linea1[] = array(
                    'comprobante' => $comprobante,
                    'numero' => "" . $impuesto->numDocSustento,
                    'fecha_emision' => "" . $impuesto->fechaEmisionDocSustento,
                    'ejercicio_fiscal' => $periodo,
                    'base' => "" . $impuesto->baseImponible,
                    'impuesto' => 'RENTA',
                    'porcentaje_retencion' => "" . $impuesto->porcentajeRetener,
                    'retencion' => "" . $impuesto->valorRetenido
                );
            }

            if ($impuesto->codigo == '2') {
                switch ($impuesto->codDocSustento) {
                    case '01':
                        $comprobante = "FACTURA";
                        break;
                    case '04';
                        $comprobante = "NOTA DE CRÉDITO";
                        break;
                    case '05':
                        $comprobante = "NOTA DE DÉBITO";
                        break;
                    case '06':
                        $comprobante = "GUÍA DE REMISIÓN";
                        break;
                    case '07':
                        $comprobante = "COMPROBANTE DE RETENCIÓN";
                        break;
                }

                $linea1[] = array(
                    'comprobante' => $comprobante,
                    'numero' => "" . $impuesto->numDocSustento,
                    'fecha_emision' => "" . $impuesto->fechaEmisionDocSustento,
                    'ejercicio_fiscal' => $periodo,
                    'base' => "" . $impuesto->baseImponible,
                    'impuesto' => 'IVA',
                    'porcentaje_retencion' => "" . $impuesto->porcentajeRetener,
                    'retencion' => "" . $impuesto->valorRetenido
                );
            }
        }
        return $linea1;
    }

    //////////////////////////////////////////////

    private function generarDetalleClasiNC($detalle) {
        $linea = '';

        for ($i = 0; $i < count($detalle); $i++) {

            $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);

            $detAdicional = $array['detAdicional'];
            $cantidad = $detalle[$i]->cantidad;
            $descripcion = $detalle[$i]->descripcion;

            foreach ($detAdicional as $det) {

                if ($det['nombre'] == 'detalle1') {
                    $valor = $det['valor'];
                }
            }
            $valoresArray = explode('|', $valor);

            $linea.= '<tr class="txt6">';
            $linea.='<td width="173" height="28" align="center" class="txt6b">&nbsp;' . $cantidad . '</td>';
            $linea.='<td width="368" align="left" class="txt6b" style="padding-right:15px">' . $descripcion . '<br><br></td>';
            $linea.='<td width="130" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valoresArray[0] . '</td>';
            $linea.='<td width="111" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valoresArray[1] . '</td>';
            $linea.='</tr>';
        }
        return $linea;
    }

    private function generarDetalleOPTNC($detalle) {
        $linea = '';
		
        for ($i = 0; $i < count($detalle); $i++) {
            $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);

            $cantidad = $detalle[$i]->cantidad;
            $descripcion = $detalle[$i]->descripcion;
            $codInterno = $detalle[$i]->codigoInterno;
            $precioUnitario = $detalle[$i]->precioUnitario;
            $precioTotal = $detalle[$i]->precioTotalSinImpuesto;

            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {

                    //var_dump($det);

                    if ($det['@attributes']['nombre'] == 'detalle1') {
                        $valor = $det['@attributes']['valor'];
                    }
                }
                $valoresArray = explode('|', $valor);
            }

            $linea.='<tr class="txt6">';
            $linea.='<td align="center" valign="middle">' . $codInterno . '</td>';
            $linea.='<td width="82" height="28px">&nbsp;</td>';
            $linea.='<td width="89" align="right" style="padding-right:15px">' . $cantidad . '</td>';
            $linea.='<td width="91" align="left"></td>';
            $linea.='<td width="224" align="center" style="padding-right:15px; padding-left:15px">' . $descripcion . '</td>';
            $linea.='<td width="104" align="right" style="padding-right:15px">' . $precioUnitario . '</td>';
            $linea.='<td width="83" align="right" style="padding-right:15px">' . $precioTotal . '</td>';
            $linea.='</tr>';
        }

        return $linea;
    }

    private function generarDetalleGUIA($detalle) {
        $linea = '';
        for ($i = 0; $i < count($detalle); $i++) {
            $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);
            $cantidad = $detalle[$i]->cantidad;
            $descripcion = $detalle[$i]->descripcion;
            $unidad = '';
            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];
                foreach ($detAdicional as $det) {
                    if ($det['nombre'] == 'detalle1') {
                        $unidad = $det['valor'];
                    }
                }
            }
            $linea.='<tr class="txt6">';
            $linea.='<td align="center" valign="middle">' . $unidad . '</td>';
            $linea.='<td align="left" style="padding-right:15px">' . $descripcion . '</td>';
            $linea.='<td align="center" style="padding-right:15px; padding-left:15px">' . $cantidad . '</td>';
            $linea.='</tr>';
        }
        return $linea;
    }

    private function generarDetalleClasiND($motivos) {
        $linea = '';

        foreach ($motivos as $mot) {
            # code...
            $valoresArray = explode('|', $mot->motivo->razon);

            $valor1 = '';
            $valor2 = '';
            $valor3 = '';

            if (isset($valoresArray[1])) {
                $valor1 = $valoresArray[1];
            }

            if (isset($valoresArray[2])) {
                $valor2 = $valoresArray[2];
            }

            if (isset($valoresArray[3])) {
                $valor3 = $valoresArray[3];
            }

            $linea.='<tr class="txt6">';
            $linea.='<td width="173" height="28" align="center" class="txt6b">&nbsp;' . $valor1 . '</td>';
            $linea.='<td width="368" align="left" class="txt6b" style="padding-right:15px">' . $valoresArray[0] . '<br> <br></td>';
            $linea.='<td width="130" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor2 . '</td>';
            $linea.='<td width="107" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor3 . '</td>';
            $linea.='</tr>';
        }
        return $linea;
    }

    private function generarDetalleDISFAC($detalle) {
        $linea = '';

        for ($i = 0; $i < count($detalle); $i++) {

            $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);

            $detAdicional = $array['detAdicional'];
            $cantidad = $detalle[$i]->cantidad;
            $descripcion = $detalle[$i]->descripcion;
            if (isset($detalle[$i]->codigoInterno))
                $codInterno = $detalle[$i]->codigoInterno;
            else
                $codInterno = $detalle[$i]->codigoPrincipal;
            $precioUnitario = $detalle[$i]->precioUnitario;
            $precioTotal = $detalle[$i]->precioTotalSinImpuesto;


            foreach ($detAdicional as $det) {
				if (isset($det['@attributes'])){
					if ($det['@attributes']['nombre'] == 'detalle1') {
						$valor = $det['@attributes']['valor'];
						//echo $valor;
					}
				}else{
					if ($det['nombre'] == 'detalle1') {
						$valor = $det['valor'];
						//echo $valor;
					}
				}
            }
            $valoresArray = explode('|', $valor);
			if(isset($valoresArray[1])){
				$valoresArray1 = $valoresArray[1];
			}else{
				$valoresArray1 ='';
			}

            $linea.='<tr class="txt6">';
            $linea.='<td width="173" height="28" align="center" class="txt6b">&nbsp;' . $cantidad . '</td>';
            $linea.='<td width="368" align="left" class="txt6b" style="padding-right:15px">' . $descripcion . '<br><br></td>';
            $linea.='<td width="130" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valoresArray[0] . '</td>';
            $linea.='<td width="107" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valoresArray1 . '</td>';
            $linea.='</tr>';
        }
        return $linea;
    }

    private function generarDetalleOPTFAC($detalle) {
        $linea = '';

        for ($i = 0; $i < count($detalle); $i++) {

            $array = json_decode(json_encode($detalle[$i]), true);

            //$detAdicional = $array['detAdicional'];
            $cantidad = $detalle[$i]->cantidad;
            $descripcion = $detalle[$i]->descripcion;
            $codPrincipal = $detalle[$i]->codigoPrincipal;
            $precioUnitario = $detalle[$i]->precioUnitario;
            $descuento = $detalle[$i]->descuento;
            $precioTotal = $detalle[$i]->precioTotalSinImpuesto;

            $linea.='<tr class="txt6">';
            $linea.='<td align="left" valign="middle">' . $codPrincipal . '</td>';
            $linea.='<td width="98" height="28px" align="center"></td>';
            $linea.='<td width="83">&nbsp;</td>';
            $linea.='<td width="84" align="right" style="padding-right:15px">' . $cantidad . '</td>';
            $linea.='<td width="183" align="left" style="padding-left:15px; padding-right:15px">' . $descripcion . '</td>';
            $linea.='<td width="82" align="right" style="padding-right:15px">' . $precioUnitario . '</td>';
            $linea.='<td width="90" align="right" style="padding-right:15px">' . $descuento . '</td>';
            $linea.='<td width="66" align="right" style="padding-right:15px">' . $precioTotal . '</td>';
            $linea.='</tr>';
        }
        return $linea;
    }

    private function generarDetalleCLASIFAC($detalle) {
        $linea = '';

        for ($i = 0; $i < count($detalle); $i++) {

            if (isset($detalle[$i]->detallesAdicionales)) {

                $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);
                $descripcion = '';
                $fecha = '';
                $valor = '';

                if (isset($array['detAdicional'])) {
                    $detAdicional = $array['detAdicional'];
                    foreach ($detAdicional as $det) {

                        if ($det['@attributes']['nombre'] == 'detalle1') {
                            $descripcion = $det['@attributes']['valor'];
                        }

                        if ($det['@attributes']['nombre'] == 'detalle2') {
                            $fecha = $det['@attributes']['valor'];
                        }
                        if ($det['@attributes']['nombre'] == 'detalle3') {
                            $valor = $det['@attributes']['valor'];
                        }
                    }
                    $valoresArray = explode('|', $valor);

                    $linea.='<tr class="txt6">';
                    $linea.='<td width="126" height="28px" align="center">' . $descripcion . '</td>';
                    $linea.='<td width="108" align="center">' . $fecha . '</td>';
                    $linea.='<td width="89" align="center">' . $valoresArray[2] . '</td>';
                    $linea.='<td width="121" align="right" style="padding-right:15px">' . $valoresArray[3] . '</td>';
                    $linea.='<td width="119" align="center">' . $valoresArray[0] . '</td>';
                    $linea.='<td width="147" align="right" style="padding-right:15px">' . $valoresArray[1] . '</td>';
                    $linea.='<td width="70" align="right" style="padding-right:15px">' . $valoresArray[4] . '</td>';
                    $linea.='</tr>';
                }
            }
        }
        return $linea;
    }

    private function generarDetallePUBLIFAC($detalle) {
        $linea = '';

        for ($i = 0; $i < count($detalle); $i++) {
            $descripcion = '';
            $fecha = '';
            $valor = '';

            $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);

            if (isset($array['detAdicional'])) {
                $detAdicional = $array['detAdicional'];

                foreach ($detAdicional as $det) {

                    if (isset($det['@attributes'])) {
                        if ($det['@attributes']['nombre'] == 'detalle1') {
                            $descripcion = $det['@attributes']['valor'];
                        }

                        if ($det['@attributes']['nombre'] == 'detalle2') {
                            $fecha = $det['@attributes']['valor'];
                        }
                        if ($det['@attributes']['nombre'] == 'detalle3') {
                            $valor = $det['@attributes']['valor'];
                        }
                    }
                }
                $valor0 = '';
                $valor1 = '';
                $valor2 = '';
                $valor3 = '';
                $valor4 = '';

                $valoresArray = array();
                $valoresArray = explode('|', $valor);

                if (isset($valoresArray[0])) {
                    $valor0 = $valoresArray[0];
                }

                if (isset($valoresArray[1])) {
                    $valor1 = $valoresArray[1];
                }
                if (isset($valoresArray[2])) {
                    $valor2 = $valoresArray[2];
                }
                if (isset($valoresArray[3])) {
                    $valor3 = $valoresArray[3];
                }
                if (isset($valoresArray[4])) {
                    $valor4 = $valoresArray[4];
                }

                $linea.='<tr class="txt6">';
                $linea.='<td align="center" valign="middle" class="txt6b">' . $fecha . '</td>';
                $linea.='<td width="122" height="28px" align="center" class="txt6b">&nbsp;' . $valor0 . '</td>';
                $linea.='<td width="124" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor1 . '</td>';
                $linea.='<td width="181" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor2 . '</td>';
                $linea.='<td width="128" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor3 . '</td>';
                $linea.='<td width="111" align="right" class="txt6b" style="padding-right:15px">&nbsp;' . $valor4 . '</td>';
                $linea.='</tr>';

                //echo $linea . "\n\n";
            }
        }
        return $linea;
    }

    private function generarDetalleComRet($campoAdicional3, $campoAdicional4) {

        $linea = '';

        if ($campoAdicional3 != '') {
            $datos = explode('|', $campoAdicional3);
        } else {
            $datos = array();
        }

        if (count($datos) > 0) {

            $linea.='<tr class="txt6">';
            $linea.='<td align="center" valign="middle" style="border-right:2px solid #FFF">' . $datos[0] . '</td>';
            $linea.='<td height="28" align="center" style="border-right:2px solid #FFF">' . $datos[1] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[2] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[3] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[4] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[5] . '</td>';
            $linea.='<td align="right" style="padding-right:15px">' . $datos[6] . '</td>';
            $linea.='</tr>';
        }

        if ($campoAdicional4 != '') {
            $datos = explode('|', $campoAdicional4);
        } else {
            $datos = array();
        }

        if (count($datos) != 0) {

            $linea.='<tr class="txt6">';
            $linea.='<td align="center" valign="middle" style="border-right:2px solid #FFF">' . $datos[0] . '</td>';
            $linea.='<td height="28" align="center" style="border-right:2px solid #FFF">' . $datos[1] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[2] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[3] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[4] . '</td>';
            $linea.='<td align="center" style="border-right:2px solid #FFF">' . $datos[5] . '</td>';
            $linea.='<td align="right" style="padding-right:15px">' . $datos[6] . '</td>';
            $linea.='</tr>';
        }

        return $linea;
    }

    public function registrarEnvioMail($idDocumento, $codigoInterno) {
        //$Documento = DocumentoModel::find($idDocumento);
		
        $Documento = DocumentoModel::where('id',$idDocumento)
								   ->where('codigo_interno',$codigoInterno)
								   ->first();
								   
        $Documento->enviado_mail = $Documento->enviado_mail + 1;
        $Documento->fecha_envio_mail = date("Y-m-d H:i:s");
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 16",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function ingresarMigracion($documento, $nombreArchivo, $destino) {
        $xml = new \SimpleXmlElement(file_get_contents($documento));

        echo "El destino es " . $destino;

        $codDoc = "" . $xml->infoTributaria->codDoc;
        $identificacionComprador = "";
        $fechaEmision = date("Y-m-d");
        $importeTotal = 0.0;
        $unidadNegocio = '';
        $codInterno = '';
        $codSociedad = '';
        $emision = '1';


        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $emision = $key->valor;
        }

        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $unidadNegocio = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        switch ($codDoc) {
            case '01':
                $identificacionComprador = "" . $xml->infoFactura->identificacionComprador;
                $fechaEmision = "" . $xml->infoFactura->fechaEmision;
                $importeTotal = "" . $xml->infoFactura->importeTotal;
                break;

            case '04':
                $identificacionComprador = "" . $xml->infoNotaCredito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaCredito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaCredito->valorModificacion;
                break;

            case '05':
                $identificacionComprador = "" . $xml->infoNotaDebito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaDebito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaDebito->valorTotal;
                break;

            case '07':
                $identificacionComprador = "" . $xml->infoCompRetencion->identificacionSujetoRetenido;
                $fechaEmision = "" . $xml->infoCompRetencion->fechaEmision;
                $importeTotal = 0.00;
                break;
        }

        $Cliente = ClienteModel::where('ruc', $identificacionComprador)->get();
        $idCliente = 0;
        foreach ($Cliente as $cli) {
            $idCliente = $cli->id;
        }
		
		$DB=new MigracionClass();
		
		$rs = $DB->db->Execute("exec prc_de_obtener_datos_autorizacion '" . $codInterno . "'");
            $numeroAutorizacion = '';
            $fechaAutorizacion = '0000-00-00 00:00:00';
            $contingencia = '0';
            $claveAcceso = '';
                                               
                                               
            foreach ($rs as $row) {
                $numeroAutorizacion = $row[1];
                $claveAcceso = $row[6];
                $fechaAutorizacion = date("Y-m-d", strtotime($row[2])) . " " . $row[3];
                if ($row[5] != ' ') {
                    $contingencia = $row[5];
                }
            }

        $Documento = new DocumentoModel();
        $Documento->cliente_id = $idCliente;
        $Documento->nombre_archivo = $nombreArchivo;
        $Documento->cod_doc = $codDoc;
        $Documento->clave_acceso = "" . $xml->infoTributaria->claveAcceso;
        $Documento->estab = "" . $xml->infoTributaria->estab;
        $Documento->ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $Documento->secuencial = "" . $xml->infoTributaria->secuencial;
        $Documento->fecha_emision = date("Y-m-d", strtotime(str_replace('/', '-', $fechaEmision)));
        //$Documento->codigo_principal="".$xml->detalles->detalle->codigoPrincipal;
        $Documento->unidad_negocio = $unidadNegocio;
        $Documento->codigo_interno = $codInterno;
        $Documento->cod_sociedad = $codSociedad;
        $Documento->valor_documento = $importeTotal;
        $Documento->estado = 'AUTORIZADA';
        $Documento->enviado_sri = 1;
        $Documento->path = $destino;
		$Documento->numero_legal="" . $xml->infoTributaria->estab."" . $xml->infoTributaria->ptoEmi."" . $xml->infoTributaria->secuencial;
		$Documento->numero_autorizacion=$numeroAutorizacion;
		$Documento->fecha_autorizacion=$fechaAutorizacion;
        //$Documento->fecha_firma = date("Y-m-d H:i:s");
        //$Documento->estado = "FIRMADO";
        if ($emision == '1') {
            $Documento->contingencia = '0';
        } else {
            $Documento->contingencia = '1';
        }
        $Documento->migrado = 1;

		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 2",'Documento'=>$Documento]);
		
        $Documento->save();
    }

    public function generarHtmlPruebas($idDocumento, $documento) {

        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);
		
		if(!isset($xml->infoAdicional->campoAdicional)){
			$xml = simplexml_load_string($xml->comprobante);//DPS
		}
		
        $Doc = DocumentoModel::find($idDocumento);
        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        $numeroAutorizacion = $Doc->numero_autorizacion;
        $fechaAutorizacion = date("d-m-Y H:i:s", strtotime($Doc->fecha_autorizacion));
        $fechaEmision = $Doc->fecha_emision;
        $razonSocial = $Doc->cliente->razon_social;
        $ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $codSociedad = '';
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';

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
        }

        switch ($codDoc) {
            case '01':

                //echo $unidadNegocio;

                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    if (isset($campoAdicional1Array[2])) {
                        $ciudad = $campoAdicional1Array[2];
                    }

                    if (isset($campoAdicional1Array[1])) {
                        $localidad = $campoAdicional1Array[1];
                    }

                    $linea = $this->generarDetalleOPTFAC($xml->detalles->detalle);
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;

                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '';

                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    $campoAdicional3 = '';

                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_optativo.html');
                }

                if ($unidadNegocio == 'CLASI') {
                    $campoAdicional4Array = explode('|', $campoAdicional4);

                    if (isset($campoAdicional4Array[0])) {
                        $medio = $campoAdicional4Array[0];
                    }
                    if (isset($campoAdicional4Array[1])) {
                        $codigoSeccion = $campoAdicional4Array[1];
                    }
                    if (isset($campoAdicional4Array[2])) {
                        $seccion = $campoAdicional4Array[2];
                    }
                    if (isset($campoAdicional4Array[3])) {
                        $marca = $campoAdicional4Array[3];
                    }
                    if (isset($campoAdicional4Array[4])) {
                        $modelo = $campoAdicional4Array[4];
                    }

                    $linea = $this->generarDetalleCLASIFAC($xml->detalles->detalle);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $subtotalAD = $campoAdicional11Array[2];
                    $descuentos = $xml->infoFactura->totalDescuento;
                    $subtotalDD = $xml->infoFactura->totalSinImpuestos;
                    $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = $xml->infoFactura->importeTotal;
                    // $campoAdicional3='';
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_clasi.html');
                }

                if ($unidadNegocio == 'PUBLI') {
                    if ($codSociedad == 'IMC') {
                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        $ciudad = $campoAdicional1Array[2];
                        $direccion = $campoAdicional1Array[3];
                        $campoAdicional3Array = explode('|', $campoAdicional3);
                        $subTotal = $campoAdicional3Array[0];
                        $adicionales = $campoAdicional3Array[1];
                        $descuentos = $campoAdicional3Array[2];
                        $subtotal12 = $campoAdicional3Array[3];
                        $subtotal0 = $campoAdicional3Array[4];
                        $iva = $campoAdicional3Array[5];
                        $total = $campoAdicional3Array[6];
                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        $campoAdicional3 = '';
                        if (isset($campoAdicional2Array[1])) {
                            $formaPago = $campoAdicional2Array[1];
                        }
                        $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                    } else {
                        $campoAdicional7Array = explode('|', $campoAdicional7);
                        $numeroReferencia = $campoAdicional7Array[0];

                        $campoAdicional8Array = explode('|', $campoAdicional8);
                        $cliente = $campoAdicional8Array[0];

                        if (isset($campoAdicional8Array[1])) {
                            $ruc = $campoAdicional8Array[1];
                            $rucAnunciante = $campoAdicional8Array[1];
                        }

                        $anunciante = $campoAdicional8Array[0];

                        $direccion = $campoAdicional10;

                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        $ciudad = $campoAdicional1Array[0];
                        if (isset($campoAdicional1Array[1])) {
                            $titulo = $campoAdicional1Array[1];
                        }
                        if (isset($campoAdicional1Array[3])) {
                            $fechaEmision = $campoAdicional1Array[3];
                        }
                        if (isset($campoAdicional1Array[2])) {
                            $medio = $campoAdicional1Array[2];
                        }

                        $campoAdicional4Array = explode('|', $campoAdicional4);
                        $tipo = $campoAdicional4Array[0];
                        if (isset($campoAdicional4Array[1])) {
                            $subtipo = $campoAdicional4Array[1];
                        }
                        if (isset($campoAdicional4Array[2])) {
                            $seccion = $campoAdicional4Array[2];
                        }
                        if (isset($campoAdicional4Array[3])) {
                            $pagina = $campoAdicional4Array[3];
                        }
                        if (isset($campoAdicional4Array[4])) {
                            $color = $campoAdicional4Array[4];
                        }

                        $campoAdicional11Array = explode('|', $campoAdicional11);
                        $subTotal = $campoAdicional11Array[0];

                        if (isset($campoAdicional11Array[1])) {
                            $porcentajeAdicionales = $campoAdicional11Array[1];
                        }
                        if (isset($campoAdicional11Array[2])) {
                            $adicionales = $campoAdicional11Array[2];
                        }
                        if (isset($campoAdicional11Array[3])) {
                            $subtotalAD = $campoAdicional11Array[3];
                        }

                        if (isset($campoAdicional11Array[4])) {
                            $porcentajeContrato = $campoAdicional11Array[4];
                        }

                        if (isset($campoAdicional11Array[5])) {
                            $contrato = $campoAdicional11Array[5];
                        }

                        if (isset($campoAdicional11Array[6])) {
                            $porcentajeOtros = $campoAdicional11Array[6];
                        }

                        if (isset($campoAdicional11Array[7])) {
                            $otros = $campoAdicional11Array[7];
                        }

                        if (isset($campoAdicional11Array[8])) {
                            $subtotal2 = $campoAdicional11Array[8];
                        }
                        if (isset($campoAdicional11Array[9])) {
                            $porcentajeAgencia = $campoAdicional11Array[9];
                        }

                        if (isset($campoAdicional11Array[10])) {
                            $agencia = $campoAdicional11Array[10];
                        }

                        if (isset($campoAdicional11Array[11])) {
                            $subtotalDD = $campoAdicional11Array[11];
                        }

                        $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                        $total = $xml->infoFactura->importeTotal;

                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        $pagosArray = explode(' ', $campoAdicional2Array[0]);
                        if (isset($pagosArray[0])) {
                            $pago = $pagosArray[0];
                        }
                        if (isset($pagosArray[1])) {
                            $fechaPago = $pagosArray[1];
                        }
                        if (isset($pagosArray[2])) {
                            $valorPago = $pagosArray[2];
                        }
                        if (isset($pagosArray[3])) {
                            $emisorPago = $pagosArray[3];
                        }
                        if (isset($pagosArray[4])) {
                            $documentoPago = $pagosArray[4];
                        }

                        if (isset($campoAdicional2Array[1])) {
                            $observacionPago = $campoAdicional2Array[1];
                        }


                        $linea = $this->generarDetallePUBLIFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_publicidad.html');
                    }
                }

                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $campoAdicional3 = '';
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    if (isset($campoAdicional2Array[1])) {
                        $formaPago = $campoAdicional2Array[1];
                    }
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                }

                break;
            case '04':
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->generarDetalleOPTNC($xml->detalles->detalle);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $descuentos = $campoAdicional11Array[1];
                    $subtotalDD = $campoAdicional11Array[2];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito_optativo.html');
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiNC($xml->detalles->detalle);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito.html');
                }


                break;
            case '05':
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional10;
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                } else {


                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                }




                break;
            case '07':
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                $direccion = $campoAdicional1Array[1];
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $telefono = $campoAdicional2Array[2];
                $voucher = $campoAdicional2Array[1];
                $campoAdicional7Array = explode('|', $campoAdicional7);
                $comprobante = $campoAdicional7Array[1];
                $anio = $campoAdicional7Array[2];
                $total = $campoAdicional7Array[0];
                $linea = $this->generarDetalleComRet($campoAdicional3, $campoAdicional4);
                $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/retencion.html');





                break;
        }




        $datos = array(
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $fechaAutorizacion,
            'fechaEmision' => $fechaEmision,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => $descuentos,
            'subtotal12' => $subtotal12,
            'subtotal0' => $subtotal0,
            'iva' => $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => $voucher,
            'formaPago' => $formaPago
        );

        foreach ($datos as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }



        return $template;
    }

    public function reporcesarHtmlAutorizado($idDocumento, $documento) {

        // $string = file_get_contents($documento);
        $xml = simplexml_load_string($documento);

        //$Doc = DocumentoModel::find($idDocumento);
        $Doc = DocumentoModel::find($idDocumento);
	
        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        $numeroAutorizacion = $Doc->numero_autorizacion;
        $fechaAutorizacion = date("d/m/Y H:i:s", strtotime($Doc->fecha_autorizacion));
        $fechaEmision = date("d/m/Y", strtotime($Doc->fecha_emision));
        $razonSocial = $Doc->cliente->razon_social;
        $ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $codSociedad = '';
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';
        $numDocSustento = '';
        $numeroReferencia = '';
        $anunciante = '';
        $rucAnunciante = '';
        $tipo = '';
        $subtipo = '';
        $pagina = '';
        $color = '';
        $pago = '';
        $fechaPago = '';
        $valorPago = '';
        $emisorPago = '';
        $documentoPago = '';
        $observacionPago = '';
        $contrato = '';
        $otros = '';
        $agencia = '';
        $titulo = '';
        $subtotal2 = '';
        $porcentajeAdicionales = '0.00';
        $porcentajeDescuento = '0.00';
        $porcentajeOtros = '0.00';
        $porcentajeAgencia = '0.00';
        $porcentajeContrato = '0.00';
        $formaPago = '';
        $destinatario = "";
        $destino = "";
        $razonSocialTransportista = '';
        $rucTransportista = '';
        $placa = '';
        $dirEstablecimiento = '';
        $fechaIniTransporte = '';
        $fechaFinTransporte = "";



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
        }

        switch ($codDoc) {
            case '01':
                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    if (isset($campoAdicional1Array[2])) {
                        $ciudad = $campoAdicional1Array[2];
                    }

                    if (isset($campoAdicional1Array[1])) {
                        $localidad = $campoAdicional1Array[1];
                    }


                    $linea = $this->generarDetalleOPTFAC($xml->detalles->detalle);
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;




                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '';


                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;


                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_optativo.html');
                }

                if ($unidadNegocio == 'CLASI') {
                    $campoAdicional4Array = explode('|', $campoAdicional4);

                    if (isset($campoAdicional4Array[0])) {
                        $medio = $campoAdicional4Array[0];
                    }
                    if (isset($campoAdicional4Array[1])) {
                        $codigoSeccion = $campoAdicional4Array[1];
                    }
                    if (isset($campoAdicional4Array[2])) {
                        $seccion = $campoAdicional4Array[2];
                    }
                    if (isset($campoAdicional4Array[3])) {
                        $marca = $campoAdicional4Array[3];
                    }
                    if (isset($campoAdicional4Array[4])) {
                        $modelo = $campoAdicional4Array[4];
                    }

                    $linea = $this->generarDetalleCLASIFAC($xml->detalles->detalle);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $subtotalAD = $campoAdicional11Array[2];
                    $descuentos = $xml->infoFactura->totalDescuento;
                    $subtotalDD = $xml->infoFactura->totalSinImpuestos;
                    $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = $xml->infoFactura->importeTotal;
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_clasi.html');
                }

                if ($unidadNegocio == 'PUBLI') {
                    if ($codSociedad == 'IMC') {
                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        if (isset($campoAdicional1Array[2])) {
                            $ciudad = $campoAdicional1Array[2];
                        }

                        if (isset($campoAdicional1Array[3])) {
                            $direccion = $campoAdicional1Array[3];
                        }


                        $campoAdicional3Array = explode('|', $campoAdicional3);
                        $subTotal = $campoAdicional3Array[0];

                        if (isset($campoAdicional3Array[1])) {
                            $adicionales = $campoAdicional3Array[1];
                        }

                        if (isset($campoAdicional3Array[2])) {
                            $descuentos = $campoAdicional3Array[2];
                        }

                        if (isset($campoAdicional3Array[3])) {
                            $subtotal12 = $campoAdicional3Array[3];
                        }

                        if (isset($campoAdicional3Array[4])) {
                            $subtotal0 = $campoAdicional3Array[4];
                        }

                        if (isset($campoAdicional3Array[5])) {
                            $iva = $campoAdicional3Array[5];
                        }

                        if (isset($campoAdicional3Array[6])) {
                            $total = $campoAdicional3Array[6];
                        }

                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        if (isset($campoAdicional2Array[1])) {
                            $formaPago = $campoAdicional2Array[1];
                        }
                        $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                    } else {
                        $campoAdicional7Array = explode('|', $campoAdicional7);
                        $numeroReferencia = $campoAdicional7Array[0];

                        $campoAdicional8Array = explode('|', $campoAdicional8);
                        $cliente = $campoAdicional8Array[0];

                        if (isset($campoAdicional8Array[1])) {
                            // $ruc = $campoAdicional8Array[1];
                            $rucAnunciante = $campoAdicional8Array[1];
                        }

                        $anunciante = $campoAdicional8Array[0];

                        $direccion = $campoAdicional10;

                        $campoAdicional1Array = explode('|', $campoAdicional1);
                        $ciudad = $campoAdicional1Array[0];
                        if (isset($campoAdicional1Array[1])) {
                            $titulo = $campoAdicional1Array[1];
                        }
                        if (isset($campoAdicional1Array[3])) {
                            $fechaEmision = $campoAdicional1Array[3];
                        }
                        if (isset($campoAdicional1Array[2])) {
                            $medio = $campoAdicional1Array[2];
                        }

                        $campoAdicional4Array = explode('|', $campoAdicional4);
                        $tipo = $campoAdicional4Array[0];
                        if (isset($campoAdicional4Array[1])) {
                            $subtipo = $campoAdicional4Array[1];
                        }
                        if (isset($campoAdicional4Array[2])) {
                            $seccion = $campoAdicional4Array[2];
                        }
                        if (isset($campoAdicional4Array[3])) {
                            $pagina = $campoAdicional4Array[3];
                        }
                        if (isset($campoAdicional4Array[4])) {
                            $color = $campoAdicional4Array[4];
                        }

                        $campoAdicional11Array = explode('|', $campoAdicional11);
                        $subTotal = $campoAdicional11Array[0];

                        if (isset($campoAdicional11Array[1])) {
                            $porcentajeAdicionales = $campoAdicional11Array[1];
                        }
                        if (isset($campoAdicional11Array[2])) {
                            $adicionales = $campoAdicional11Array[2];
                        }
                        if (isset($campoAdicional11Array[3])) {
                            $subtotalAD = $campoAdicional11Array[3];
                        }

                        if (isset($campoAdicional11Array[4])) {
                            $porcentajeContrato = $campoAdicional11Array[4];
                        }

                        if (isset($campoAdicional11Array[5])) {
                            $contrato = $campoAdicional11Array[5];
                        }

                        if (isset($campoAdicional11Array[6])) {
                            $porcentajeOtros = $campoAdicional11Array[6];
                        }

                        if (isset($campoAdicional11Array[7])) {
                            $otros = $campoAdicional11Array[7];
                        }

                        if (isset($campoAdicional11Array[8])) {
                            $subtotal2 = $campoAdicional11Array[8];
                        }
                        if (isset($campoAdicional11Array[9])) {
                            $porcentajeAgencia = $campoAdicional11Array[9];
                        }

                        if (isset($campoAdicional11Array[10])) {
                            $agencia = $campoAdicional11Array[10];
                        }

                        if (isset($campoAdicional11Array[11])) {
                            $subtotalDD = $campoAdicional11Array[11];
                        }

                        $iva = $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                        $total = $xml->infoFactura->importeTotal;

                        $campoAdicional2Array = explode('|', $campoAdicional2);
                        $pagosArray = explode(' ', $campoAdicional2Array[0]);
                        if (isset($pagosArray[0])) {
                            $pago = $pagosArray[0];
                        }
                        if (isset($pagosArray[1])) {
                            $fechaPago = $pagosArray[1];
                        }
                        if (isset($pagosArray[2])) {
                            $valorPago = $pagosArray[2];
                        }
                        if (isset($pagosArray[3])) {
                            $emisorPago = $pagosArray[3];
                        }
                        if (isset($pagosArray[4])) {
                            $documentoPago = $pagosArray[4];
                        }

                        if (isset($campoAdicional2Array[1])) {
                            $observacionPago = $campoAdicional2Array[1];
                        }


                        $linea = $this->generarDetallePUBLIFAC($xml->detalles->detalle);
                        $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_publicidad.html');
                    }
                }

                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    if (isset($campoAdicional1Array[2])){
                    $ciudad = $campoAdicional1Array[2];
					}
					if (isset($campoAdicional1Array[3])){
                    $direccion = $campoAdicional1Array[3];
					}
                    $linea = $this->generarDetalleDISFAC($xml->detalles->detalle);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
					if(isset($campoAdicional3Array[1])){
                    $adicionales = $campoAdicional3Array[1];
					}
					if(isset($campoAdicional3Array[2])){
                    $descuentos = $campoAdicional3Array[2];
					}
					if(isset($campoAdicional3Array[3])){
                    $subtotal12 = $campoAdicional3Array[3];
					}
					if(isset($campoAdicional3Array[4])){
                    $subtotal0 = $campoAdicional3Array[4];
					}
					if(isset($campoAdicional3Array[5])){
                    $iva = $campoAdicional3Array[5];
					}
					if(isset($campoAdicional3Array[6])){
                    $total = $campoAdicional3Array[6];
					}
					
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    if (isset($campoAdicional2Array[1])) {
                        $formaPago = $campoAdicional2Array[1];
                    }
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/factura_distri_DIGT.html');
                }

                break;
            case '04':
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->generarDetalleOPTNC($xml->detalles->detalle);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $descuentos = $campoAdicional11Array[1];
                    $subtotalDD = $campoAdicional11Array[2];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito_optativo.html');
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiNC($xml->detalles->detalle);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notacredito.html');
                }


                break;
            case '05':
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;

                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional10;
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                } else {


                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/notadebito.html');
                }




                break;

            case '06':
                $dirEstablecimiento = "" . $xml->infoGuiaRemision->dirEstablecimiento;
                $destinatario = "" . $xml->destinatarios->destinatario->razonSocialDestinatario;
                $ruc = "" . $xml->destinatarios->destinatario->identificacionDestinatario;
                $destino = "" . $xml->destinatarios->destinatario->dirDestinatario;
                $razonSocialTransportista = '' . $xml->infoGuiaRemision->razonSocialTransportista;
                $rucTransportista = '' . $xml->infoGuiaRemision->rucTransportista;
                $placa = '' . $xml->infoGuiaRemision->placa;
                $dirEstablecimiento = '' . $xml->infoGuiaRemision->dirEstablecimiento;
                $fechaIniTransporte = '' . $xml->infoGuiaRemision->fechaIniTransporte;
                $fechaFinTransporte = "" . $xml->infoGuiaRemision->fechaFinTransporte;
                $numDocSustento = "" . $xml->destinatarios->destinatario->numDocSustento;
                if ($unidadNegocio == 'OPTAT') {
                    $linea = $this->generarDetalleGUIA($xml->destinatarios->destinatario->detalles->detalle);
                    $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/guia.html');
                }
                break;

            case '07':
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                if (isset($campoAdicional1Array[1])) {
                    $direccion = $campoAdicional1Array[1];
                }


                $campoAdicional2Array = explode('|', $campoAdicional2);
                if (isset($campoAdicional2Array[2])) {
                    $telefono = $campoAdicional2Array[2];
                }

                if (isset($campoAdicional2Array[1])) {
                    $voucher = $campoAdicional2Array[1];
                }

                $campoAdicional7Array = explode('|', $campoAdicional7);

                if (isset($campoAdicional7Array[1])) {
                    $comprobante = $campoAdicional7Array[1];
                }
                if (isset($campoAdicional7Array[2])) {
                    $anio = $campoAdicional7Array[2];
                }

                $total = $campoAdicional7Array[0];


                $linea = $this->generarDetalleComRet($campoAdicional3, $campoAdicional4);
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $numDocSustento = $campoAdicional2Array[0];
                $template = file_get_contents(base_path() . '/resources/views/admin/documento/templates/retencion.html');

                break;
        }

        $datos = array(
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => $fechaAutorizacion,
            'fechaEmision' => $fechaEmision,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => $descuentos,
            'subtotal12' => $subtotal12,
            'subtotal0' => $subtotal0,
            'iva' => $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => $voucher,
            'numDocSustento' => $numDocSustento,
            'numeroReferencia' => $numeroReferencia,
            'anunciante' => $anunciante,
            'rucAnunciante' => $rucAnunciante,
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'pagina' => $pagina,
            'color' => $color,
            'pago' => $pago,
            'fechaPago' => $fechaPago,
            'valorPago' => $valorPago,
            'emisorPago' => $emisorPago,
            'documentoPago' => $documentoPago,
            'observacionPago' => $observacionPago,
            'contrato' => $contrato,
            'otros' => $otros,
            'agencia' => $agencia,
            'titulo' => $titulo,
            'subtotal2' => $subtotal2,
            'porcentajeAdicionales' => $porcentajeAdicionales,
            'porcentajeDescuento' => $porcentajeDescuento,
            'porcentajeOtros' => $porcentajeOtros,
            'porcentajeAgencia' => $porcentajeAgencia,
            'porcentajeContrato' => $porcentajeContrato,
            'formaPago' => $formaPago,
            'destinatario' => $destinatario,
            'destino' => $destino,
            'razonSocialTransportista' => $razonSocialTransportista,
            'rucTransportista' => $rucTransportista,
            'placa' => $placa,
            'dirEstablecimiento' => $dirEstablecimiento,
            'fechaIniTransporte' => $fechaIniTransporte,
            'fechaFinTransporte' => $fechaFinTransporte
        );

        foreach ($datos as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }



        $Doc->html = $template;

        $Doc->save();
    }

    public function registrarEstadoInterno($idDocumento, $estado, $codInterno) {

        //$Doc = DocumentoModel::find($idDocumento);
        $Doc = DocumentoModel::where('id',$idDocumento)
							 ->where('codigo_interno', $codInterno)
							 ->first();
							 
        $Doc->estado_interno = $estado;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 3",'Documento'=>$Doc]);
        $Doc->save();
    }

    public function getDatosRespuestaRegenerar($doc, $numeroAutorizacion = '', $fechaAutorizacion = '', $horaAutorizacion = '', $rechazo = '', $contingencia = '0', $origenError = '', $mensaje = '') {



        //$string = file_get_contents($doc);
        //$xml = simplexml_load_string($string);
        $xml = simplexml_load_string($doc);

        $codSociedad = '';
        $codInterno = '';
        $estab = '';
        $ptoEmi = '';
        $tipoEmision = '';
        $claveAcceso = '';
        $ambiente = '';
        $tipoDoc = '';

        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $tipoEmision = $key->valor;
        }

        $sql = "select valor from configuracion where dato='ambiente'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $ambiente = $key->valor;
        }



        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {

            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        //$tipoEmision="".$xml->infoTributaria->tipoEmision;
        $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
        //$ambiente="".$xml->infoTributaria->ambiente;
        $tipoDoc = "" . $xml->infoTributaria->codDoc;

        $mensaje = preg_replace("[\n|\r|\n\r]", "", $mensaje);

        $retorno = $codSociedad . ";" . $codInterno . ";" . $estab . ";" . $ptoEmi . ";" . $tipoEmision . ";" . $claveAcceso . ";" . $numeroAutorizacion . ";" . $ambiente . ";" . $fechaAutorizacion . ";" . $horaAutorizacion . ";" . $rechazo . ";" . $contingencia . ";" . $origenError . ";" . $mensaje . ";" . $tipoDoc;



        return $retorno;
    }

    /*
      private function generarDetallesClasiFactura($detalle) {
      $linea = '';

      for ($i = 0; $i < count($detalle); $i++) {


      $array = json_decode(json_encode($detalle[$i]->detallesAdicionales), true);

      $detAdicional = $array['detAdicional'];

      foreach ($detAdicional as $det) {
      if ($det['@attributes']['nombre'] == 'detalle1') {
      $codigo = $det['@attributes']['valor'];
      }
      if ($det['@attributes']['nombre'] == 'detalle2') {
      $fecha = $det['@attributes']['valor'];
      }
      if ($det['@attributes']['nombre'] == 'detalle3') {
      $valoresArray = explode('|', $det['@attributes']['valor']);
      }
      }

      $linea.='<tr class="txt6">';
      $linea.='<td width="126" height="28px" align="center">' . $codigo . '</td>';
      $linea.='<td width="108" align="center">' . $fecha . '</td>';
      $linea.='<td width="89" align="center">' . $valoresArray[2] . '</td>';
      $linea.='<td width="121" align="right" style="padding-right:15px">' . $valoresArray[3] . '</td>';
      $linea.='<td width="119" align="center">' . $valoresArray[0] . '</td>';
      $linea.='<td width="147" align="right" style="padding-right:15px">' . $valoresArray[1] . '</td>';
      $linea.='<td width="70" align="right" style="padding-right:15px">' . $valoresArray[4] . '</td>';
      $linea.='</tr>';
      }
      return $linea;
      } */

    public function generarPdfSinFirma($idDocumento, $documento, $archivo, $subruta = "", $claveAcceso, $numeroAutorizacion, $fechaAutorizacion) {

        $string = file_get_contents($documento);
        $xml = simplexml_load_string($string);
        $Doc = DocumentoModel::find($idDocumento);

        $codDoc = $Doc->cod_doc;
        $unidadNegocio = $Doc->unidad_negocio;
        $numeroAutorizacion = $numeroAutorizacion;
        $claveAcceso = $claveAcceso;
        $fechaAutorizacion = $fechaAutorizacion;
        $fechaEmision = $Doc->fecha_emision;
        $razonSocial = utf8_encode($Doc->cliente->razon_social);
        $ruc = $Doc->cliente->ruc;
        $estab = $Doc->estab;
        $ptoEmi = $Doc->ptoEmi;
        $secuencial = $Doc->secuencial;
        $lineas = '';
        $campoAdicional1 = '';
        $campoAdicional2 = '';
        $campoAdicional3 = '';
        $campoAdicional4 = '';
        $campoAdicional5 = '';
        $campoAdicional6 = '';
        $campoAdicional7 = '';
        $campoAdicional8 = '';
        $campoAdicional9 = '';
        $campoAdicional10 = '';
        $campoAdicional11 = '';
        $campoAdicional12 = '';
        $codSociedad = '';
        $CodInternoSAP = '';
        $dirEstablecimiento = '';
        $ciudad = '';
        $direccion = '';
        $linea = '';
        $subTotal = '';
        $adicionales = '';
        $descuentos = '';
        $subtotal12 = '';
        $subtotal0 = '';
        $iva = '';
        $total = '';
        $template = '';
        $subtotalDD = '';
        $formaPago = '';
        $provincia = '';
        $localidad = '';
        $medio = '';
        $codigoSeccion = '';
        $marca = '';
        $seccion = '';
        $modelo = '';
        $subtotalAD = '';
        $telefono = '';
        $anio = '';
        $comprobante = '';
        $voucher = '';
        $cm_tipo = "";
        $cm_numero = "";
        $cm_fecha = "";
        $cm_razon = "";


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

        switch ($codDoc) {
            case '01':
                $plantilla = 'ride.factura';
                $titulo_plantilla = 'F A C T U R A';
                if ($unidadNegocio == 'OPTAT') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $provincia = $campoAdicional1Array[0];
                    if (isset($campoAdicional1Array[2])) {
                        $ciudad = $campoAdicional1Array[2];
                    }
                    if (isset($campoAdicional1Array[1])) {
                        $localidad = $campoAdicional1Array[1];
                    }


                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    //IMPUESTOS//

                    /* $linea = array();
                      foreach ($detalles->detalle as $detalle) {
                      $subtotal0='0.00';
                      $subtotal12="".$xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                      $detalle->codigoPrincipal
                      } */

                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    $linea = $this->pdf_generarDetalleOPTFAC($xml->detalles);
                }

                if ($unidadNegocio == 'CLASI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional2Array = explode('|', $campoAdicional2);
                    $ciudad = $campoAdicional2Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }

                if ($unidadNegocio == 'PUBLI') {
                    $direccion = $campoAdicional10;
                    $telefono = $campoAdicional6;
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[0];
                    $subTotal = "" . $xml->infoFactura->totalSinImpuestos;
                    $descuentos = "" . $xml->infoFactura->totalDescuento;
                    $subtotal0 = '0.00';
                    $subtotal12 = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible;
                    $iva = "" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor;
                    $total = "" . $xml->infoFactura->importeTotal;
                    if ($codSociedad == 'IMC')
                        $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);
                    else
                        $linea = $this->pdf_generarDetalleCLASIFAC($xml->detalles);
                }

                if ($unidadNegocio != 'CLASI' && $unidadNegocio != 'OPTAT' && $unidadNegocio != 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                    $linea = $this->pdf_generarDetalleDISFAC($xml->detalles);
                }
                //$claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoFactura->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                break;
            case '04':
                //$claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                if ($xml->infoNotaCredito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaCredito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaCredito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaCredito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaCredito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_credito';
                $titulo_plantilla = 'NOTA DE CRÉDITO';
                $cm_numero = $xml->infoNotaCredito->numDocModificado;
                $cm_fecha = $xml->infoNotaCredito->fechaEmisionDocSustento;
                $cm_razon = $xml->infoNotaCredito->motivo;

                $dirEstablecimiento = "" . $xml->infoNotaCredito->dirEstablecimiento;
                if ($unidadNegocio == 'OPTAT') {

                    $linea = $this->pdf_generarDetalleOPTNC($xml->detalles);
                    $campoAdicional11Array = explode('|', $campoAdicional11);

                    $subTotal = $campoAdicional11Array[0];
                    if (isset($campoAdicional11Array[1])) {
                        $descuentos = $campoAdicional11Array[1];
                    }
                    if (isset($campoAdicional11Array[2])) {
                        $subtotalDD = $campoAdicional11Array[2];
                    }
                    if (isset($campoAdicional11Array[5])) {
                        $iva = $campoAdicional11Array[5];
                    }
                    if (isset($campoAdicional11Array[6])) {
                        $total = $campoAdicional11Array[6];
                    }
                } else {
                    //if ($unidadNegocio == 'CLASI' || $unidadNegocio == 'PUBLI') {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiNC($xml->detalles);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }


                break;
            case '05':
                //$claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoNotaDebito->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;

                if ($xml->infoNotaDebito->codDocModificado == "01")
                    $cm_tipo = "FACTURA";
                if ($xml->infoNotaDebito->codDocModificado == "04")
                    $cm_tipo = "NOTA DE CREDITO";
                if ($xml->infoNotaDebito->codDocModificado == "05")
                    $cm_tipo = "NOTA DE DÉBITO";
                if ($xml->infoNotaDebito->codDocModificado == "06")
                    $cm_tipo = "GUÍA DE REMISIÓN";
                if ($xml->infoNotaDebito->codDocModificado == "0´7")
                    $cm_tipo = "COMPROBANTE DE RETENCIÓN";
                $plantilla = 'ride.nota_dedito';
                $titulo_plantilla = 'NOTA DE DÉBITO';
                $cm_numero = $xml->infoNotaDebito->numDocModificado;
                $cm_fecha = $xml->infoNotaDebito->fechaEmisionDocSustento;
                if ($unidadNegocio == 'OPTAT') {

                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional10;
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional11Array = explode('|', $campoAdicional11);
                    $subTotal = $campoAdicional11Array[0];
                    $adicionales = $campoAdicional11Array[1];
                    $descuentos = $campoAdicional11Array[2];
                    $subtotal12 = $campoAdicional11Array[3];
                    $subtotal0 = $campoAdicional11Array[4];
                    $iva = $campoAdicional11Array[5];
                    $total = $campoAdicional11Array[6];
                } else {
                    $campoAdicional1Array = explode('|', $campoAdicional1);
                    $ciudad = $campoAdicional1Array[2];
                    $direccion = $campoAdicional1Array[3];
                    $linea = $this->pdf_generarDetalleClasiND($xml->motivos);
                    $campoAdicional3Array = explode('|', $campoAdicional3);
                    $subTotal = $campoAdicional3Array[0];
                    $adicionales = $campoAdicional3Array[1];
                    $descuentos = $campoAdicional3Array[2];
                    $subtotal12 = $campoAdicional3Array[3];
                    $subtotal0 = $campoAdicional3Array[4];
                    $iva = $campoAdicional3Array[5];
                    $total = $campoAdicional3Array[6];
                }
                break;
            case '07':
                //$claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                $dirEstablecimiento = "" . $xml->infoCompRetencion->dirEstablecimiento;
                $dirMatriz = "" . $xml->infoTributaria->dirMatriz;
                $impuestos = $xml->impuestos;
                $campoAdicional1Array = explode('|', $campoAdicional1);
                $ciudad = $campoAdicional1Array[0];
                $direccion = $campoAdicional1Array[1];
                $campoAdicional2Array = explode('|', $campoAdicional2);
                $telefono = $campoAdicional2Array[2];
                $voucher = $campoAdicional2Array[1];
                $campoAdicional7Array = explode('|', $campoAdicional7);
                $comprobante = $campoAdicional7Array[1];
                $anio = $campoAdicional7Array[2];
                $total = $campoAdicional7Array[0];
                $linea = $this->pdf_generarDetalleComRet($campoAdicional3, $campoAdicional4, $impuestos->impuesto, $xml->infoCompRetencion->periodoFiscal);
                $plantilla = 'ride.retencion';
                $titulo_plantilla = 'COMPROBANTE DE RETENCIÓN';
                break;
            default :
                //  $claveAcceso = "";
                $dirEstablecimiento = "";
                $dirMatriz = "";
                $impuestos = "";
                $campoAdicional1Array = "";
                $ciudad = "";
                $direccion = "";
                $campoAdicional2Array = "";
                $telefono = "";
                $voucher = "";
                $campoAdicional7Array = "";
                $comprobante = "";
                $anio = "";
                $total = "";
                $linea = "";
                break;
        }

        if ($xml->infoTributaria->ambiente == 1) {
            $ambiente = "PRUEBAS";
        } elseif ($xml->infoTributaria->ambiente == 2) {
            $ambiente = "PRODUCCION";
        }
        if ($xml->infoTributaria->tipoEmision == 1) {
            $tipoEmision = "NORMAL";
        } elseif ($xml->infoTributaria->tipoEmision == 2) {
            $tipoEmision = "CONTINGENCIA";
        }

        $ambiente = 'PRODUCCION';
        $tipoEmision = 'NORMAL';

		$formaPago=array();
		$formaPago[]=array(			
				'descripcion'=>'',
				'total'=>'',
				'plazo'=>'',
				'unidadTiempo'=>''
		);




        $datos = array(
            'numeroAutorizacion' => $numeroAutorizacion,
            'fechaAutorizacion' => date("d/m/Y H:i:s", strtotime($fechaAutorizacion)),
            'fechaEmision' => date("d/m/Y", strtotime($fechaEmision)),
            'razonSocialEmpresa' => ''.$xml->infoTributaria->razonSocial,
            'contribuyenteEspecial' => ''.$xml->infoTributaria->contribuyenteEspecial,
            'obligadoContabilidad' => ''.$xml->infoTributaria->obligadoContabilidad,
            'razonSocial' => $razonSocial,
            'ruc' => $ruc,
            'estab' => $estab,
            'ptoEmi' => $ptoEmi,
            'secuencial' => $secuencial,
            'campoAdicional1' => $campoAdicional1,
            'campoAdicional2' => $campoAdicional2,
            'campoAdicional3' => $campoAdicional3,
            'campoAdicional4' => $campoAdicional4,
            'campoAdicional5' => $campoAdicional5,
            'campoAdicional6' => $campoAdicional6,
            'campoAdicional7' => $campoAdicional7,
            'campoAdicional8' => $campoAdicional8,
            'campoAdicional9' => $campoAdicional9,
            'campoAdicional10' => $campoAdicional10,
            'campoAdicional11' => $campoAdicional11,
            'campoAdicional12' => $campoAdicional12,
            'codSociedad' => $codSociedad,
            'codInternoSAP' => $codInternoSAP,
            'dirEstablecimiento' => $dirEstablecimiento,
            'dirMatriz' => $dirMatriz,
            'ciudad' => $ciudad,
            'direccion' => $direccion,
            'linea' => $linea,
            'subTotal' => $subTotal,
            'adicionales' => $adicionales,
            'descuentos' => "" . $descuentos,
            'subtotal12' => "" . $subtotal12,
            'subtotal0' => "" . $subtotal0,
            'iva' => "" . $iva,
            'total' => str_replace('-', '', $total),
            'subtotalDD' => $subtotalDD,
            'provincia' => $provincia,
            'localidad' => $localidad,
            'medio' => $medio,
            'codigoSeccion' => $codigoSeccion,
            'marca' => $marca,
            'seccion' => $seccion,
            'modelo' => $modelo,
            'subtotalAD' => $subtotalAD,
            'telefono' => $telefono,
            'anio' => $anio,
            'comprobante' => $comprobante,
            'voucher' => "" . $voucher,
            'correo' => $correo,
            'clave_acceso' => $claveAcceso,
            'ambiente' => "" . $ambiente,
            'tipoEmision' => "" . $tipoEmision,
            'irbpnr' => '0.00',
            'propina' => "" . $xml->infoFactura->propina,
            'ice' => '0.00',
            'subtotal_exiva' => '0.00',
            'subtotal_noiva' => '0.00',
            'titulo_plantilla' => $titulo_plantilla,
            'cm_tipo' => "" . $cm_tipo,
            'cm_numero' => "" . $cm_numero,
            'cm_razon' => "" . $cm_razon,
            'cm_fecha' => "" . $cm_fecha,
            'codDoc' => "" . $codDoc,
			'formapago'=>$formaPago,
			'tarifa'=>'12'
        );

		
		
        //return $datos;
        $view = \View::make($plantilla, $datos);
        $html = (string) $view;
        //$claveAcceso = "" . $xml->infoTributaria->claveAcceso;
        generar_pdf($html, $claveAcceso, $archivo, $subruta, $datos);
        return "ok";
    }

    public function ingresarMigracionSinFirma($documento, $nombreArchivo, $destino) {
        $xml = new \SimpleXmlElement(file_get_contents($documento));

        echo "El destino es " . $destino;




        $codDoc = "" . $xml->infoTributaria->codDoc;
        $identificacionComprador = "";
        $fechaEmision = date("Y-m-d");
        $importeTotal = 0.0;
        $unidadNegocio = '';
        $codInterno = '';
        $codSociedad = '';
        $emision = '1';


        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $emision = $key->valor;
        }





        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $unidadNegocio = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }





        switch ($codDoc) {
            case '01':
                $identificacionComprador = "" . $xml->infoFactura->identificacionComprador;
                $fechaEmision = "" . $xml->infoFactura->fechaEmision;
                $importeTotal = "" . $xml->infoFactura->importeTotal;
                break;

            case '04':
                $identificacionComprador = "" . $xml->infoNotaCredito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaCredito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaCredito->valorModificacion;
                break;

            case '05':
                $identificacionComprador = "" . $xml->infoNotaDebito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaDebito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaDebito->valorTotal;
                break;

            case '07':
                $identificacionComprador = "" . $xml->infoCompRetencion->identificacionSujetoRetenido;
                $fechaEmision = "" . $xml->infoCompRetencion->fechaEmision;
                $importeTotal = 0.00;
                break;
        }





        $Cliente = ClienteModel::where('ruc', $identificacionComprador)->get();
        $idCliente = 0;
        foreach ($Cliente as $cli) {
            $idCliente = $cli->id;
        }


        $Documento = DocumentoModel::where('nombre_archivo',$nombreArchivo)->first();
        if(count($Documento) == 0)
        $Documento = new DocumentoModel();
        $Documento->cliente_id = $idCliente;
        $Documento->nombre_archivo = $nombreArchivo;
        $Documento->cod_doc = $codDoc;
        $Documento->clave_acceso = "";
        $Documento->estab = "" . $xml->infoTributaria->estab;
        $Documento->ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $Documento->secuencial = "" . $xml->infoTributaria->secuencial;
        $Documento->fecha_emision = date("Y-m-d", strtotime(str_replace('/', '-', $fechaEmision)));
        //$Documento->codigo_principal="".$xml->detalles->detalle->codigoPrincipal;
        $Documento->unidad_negocio = $unidadNegocio;
        $Documento->codigo_interno = $codInterno;
        $Documento->cod_sociedad = $codSociedad;
        $Documento->valor_documento = $importeTotal;
        $Documento->estado = 'AUTORIZADA';
        $Documento->enviado_sri = 1;
        $Documento->path = $destino;
        //$Documento->fecha_firma = date("Y-m-d H:i:s");
        //$Documento->estado = "FIRMADO";
        if ($emision == '1') {
            $Documento->contingencia = '0';
        } else {
            $Documento->contingencia = '1';
        }
        $Documento->migrado = 1;

		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 4",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function setEstadoFirmado($idDocumento) {
        /*
          $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
          $idDocumento = 0;
          foreach ($Documento as $doc) {
          $idDocumento = $doc->id;
          } */

        $Documento = DocumentoModel::find($idDocumento);

        $Documento->enviado_sri = 0;

        $Documento->estado = 'FIRMADO';
        $Documento->estado_interno = 'Firmado';
        //$Documento->mensaje_estado = $mensaje;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 5",'Documento'=>$Documento]);
        $Documento->save();
    }

    public function verPorCodigoInterno($documento) {

        $codigoInterno = '';

        $xml = simplexml_load_file($documento);
        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codigoInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
        }

        return $codigoInterno;
    }

    public function registrarEstadoAutorizacion($idDocumento, $estado, $mensaje, $codInterno) {
        /*
          $Documento = DocumentoModel::where('id', '<>', 0)->orderBy('id', 'desc')->take(1)->get();
          $idDocumento = 0;
          foreach ($Documento as $doc) {
          $idDocumento = $doc->id;
          } */

        //$Documento = DocumentoModel::find($idDocumento);
        $Documento = DocumentoModel::where('id',$idDocumento)
								   ->where('codigo_interno', $codInterno)
								   ->first();

        
        $Documento->estado = $estado;
        $Documento->mensaje_estado = $mensaje;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 6",'Documento'=>$Documento]);
        $Documento->save();
    }
	
	public function ingresarDocumentoDevuelta($documento, $nombreArchivo,$mensaje) {
		
		$this->verificarNoIngresado($documento);
		
        $xml = new \SimpleXmlElement(file_get_contents($documento));

        $codDoc = "" . $xml->infoTributaria->codDoc;
        $identificacionComprador = "";
        $fechaEmision = date("Y-m-d");
        $importeTotal = 0.0;
        $unidadNegocio = '';
        $codInterno = '';
        $codSociedad = '';
        $emision = '1';
		$valorCR='';
		$path='public/Documentos/Devueltas/'.date("Y/m/d").'/'.$nombreArchivo;

        $estab = "" . $xml->infoTributaria->estab;
        $ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $secuencial = "" . $xml->infoTributaria->secuencial;

        $subtotalSinIVA = 0.00;
        $valorIva = 0.00;

        $sql = "select valor from configuracion where dato='emision'";
        $result = \DB::select($sql);
        foreach ($result as $key) {
            $emision = $key->valor;
        }





        for ($i = 0; $i < count($xml->infoAdicional->campoAdicional); $i++) {
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodSociedad') {
                $codSociedad = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CodInternoSAP') {
                $codInterno = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional9') {
                $unidadNegocio = "" . $xml->infoAdicional->campoAdicional[$i];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional7') {
                $campoAdicional7Array = explode('|', "" . $xml->infoAdicional->campoAdicional[$i]);
                $valorCR = $campoAdicional7Array[0];
            }
            if ($xml->infoAdicional->campoAdicional[$i]->attributes() == 'CampoAdicional2') {
                $campoAdicional2Array = explode('|', "" . $xml->infoAdicional->campoAdicional[$i]);
            }
        }


        switch ($codDoc) {
            case '01':
                $identificacionComprador = "" . $xml->infoFactura->identificacionComprador;
                $fechaEmision = "" . $xml->infoFactura->fechaEmision;
                $importeTotal = "" . $xml->infoFactura->importeTotal;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoFactura->totalConImpuestos->totalImpuesto->valor);




                break;

            case '04':
                $identificacionComprador = "" . $xml->infoNotaCredito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaCredito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaCredito->valorModificacion;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoNotaCredito->totalConImpuestos->totalImpuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoNotaCredito->totalConImpuestos->totalImpuesto->valor);
                break;

            case '05':
                $identificacionComprador = "" . $xml->infoNotaDebito->identificacionComprador;
                $fechaEmision = "" . $xml->infoNotaDebito->fechaEmision;
                $importeTotal = "" . $xml->infoNotaDebito->valorTotal;
                $claveAcceso = $xml->infoTributaria->claveAcceso;

                $subtotalSinIVA = (double) ("" . $xml->infoNotaDebito->impuestos->impuesto->baseImponible);
                $valorIva = (double) ("" . $xml->infoNotaDebito->impuestos->impuesto->valor);


                break;

            case '06':
                $identificacionComprador = "" . $xml->destinatarios->destinatario->identificacionDestinatario;
                $fechaEmision = date('Y-m-d');
                $importeTotal = 0;
                $claveAcceso = "" . $xml->infoTributaria->claveAcceso;
                break;

            case '07':
                $identificacionComprador = "" . $xml->infoCompRetencion->identificacionSujetoRetenido;
                $fechaEmision = "" . $xml->infoCompRetencion->fechaEmision;
                $importeTotal = $valorCR;
                $claveAcceso = $xml->infoTributaria->claveAcceso;
                break;
        }





        //$Cliente = ClienteModel::where('ruc', $identificacionComprador)->get();
        $idCliente = 1;
        //foreach ($Cliente as $cli) {
          //  $idCliente = $cli->id;
        //}


        $DocAux = DocumentoModel::where('estab', $estab)->where('ptoEmi', $ptoEmi)->where('secuencial', $secuencial)->get();

        if (count($DocAux) > 0) {
            foreach ($DocAux as $key) {
                $idDoc = $key->id;
            }
            $Documento = DocumentoModel::find($idDoc);
        } else {

            $Documento = new DocumentoModel();
        }

        $Documento->cliente_id = $idCliente;
        $Documento->nombre_archivo = $nombreArchivo;
        $Documento->cod_doc = $codDoc;
        $Documento->clave_acceso = "" . $claveAcceso;
        $Documento->estab = "" . $xml->infoTributaria->estab;
        $Documento->ptoEmi = "" . $xml->infoTributaria->ptoEmi;
        $Documento->secuencial = "" . $xml->infoTributaria->secuencial;
        $Documento->fecha_emision = date("Y-m-d", strtotime(str_replace('/', '-', $fechaEmision)));
        //$Documento->codigo_principal="".$xml->detalles->detalle->codigoPrincipal;
        $Documento->unidad_negocio = $unidadNegocio;
        $Documento->codigo_interno = $codInterno;
        $Documento->cod_sociedad = $codSociedad;
        $Documento->valor_documento = $importeTotal;
        //$Documento->fecha_firma = date("Y-m-d H:i:s");
        $Documento->estado = "DEVUELTA";
        $Documento->mensaje_estado = $mensaje;
        $Documento->enviado_sri = '0';
        $Documento->subtotal = $subtotalSinIVA;
        $Documento->valor_iva = $valorIva;
		$Documento->hilo = 0;
		$Documento->estado_interno='PROCESADO';

        $Documento->numero_legal=$estab.$ptoEmi.$secuencial;
		$Documento->error='GEC';
		$Documento->path=$path;
		
        if ($emision == '1') {
            $Documento->contingencia = '0';
        } else {
            $Documento->contingencia = '1';
        }
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 7",'Documento'=>$Documento]);

        $Documento->save();
        if ($codDoc == '07') {
            $Documento1 = new Doc_x_docsusModel();
            $Documento1->doc_id = $Documento->id;
            $Documento1->doc_sustento = $campoAdicional2Array[0];
            $Documento1->save();
        }
        if ($codDoc == '06') {
            $Documento1 = new Doc_x_docsusModel();
            $Documento1->doc_id = $Documento->id;
            $Documento1->doc_sustento = $xml->destinatarios->destinatario->numDocSustento;
            $Documento1->save();
            $Documento1 = new Doc_x_guiaRemision();
            $Documento1->doc_id = $Documento->id;
            $Documento1->dirPartida = $xml->infoGuiaRemision->dirPartida;
            $Documento1->razonSocialTransportista = $xml->infoGuiaRemision->razonSocialTransportista;
            $Documento1->tipoIdentificacionTransportista = $xml->infoGuiaRemision->tipoIdentificacionTransportista;
            $Documento1->rucTransportista = $xml->infoGuiaRemision->rucTransportista;
            $Documento1->fechaIniTransporte = $xml->infoGuiaRemision->fechaIniTransporte;
            $Documento1->fechaFinTransporte = $xml->infoGuiaRemision->fechaFinTransporte;
            $Documento1->placa = $xml->infoGuiaRemision->placa;
            $Documento1->save();
        }
    }
	
	public function registrarTipoError($idDocumento,$tipoError){
		$doc=DocumentoModel::find($idDocumento);
		$doc->error=$tipoError;
		//\Log::useDailyFiles(storage_path().'/logs/DocumentoClass.log');
		//\Log::error(['DocumentoClass'=>"Log verificar al insertar 16",'Documento'=>$doc]);
		$doc->save();
	}

}
