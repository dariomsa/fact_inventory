<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Clases;

include_once 'XAdes/XAdES-BES.php';

use App\Http\Models\ConfiguracionModel;


class FirmadorClass {

    private $pincode;
    private $serialNumber;
    private $ambiente;
    private $emision;
    private $subcert;
    private $cacert;
    private $p12;
    private $certificadoPath;
    private $workPath;
    private $oppenSSL;
    private $digestAlgorithm;
    private $digestAlgorithmURI;
    private $signatureAlgorithURI;
    private $docType;
    private $contingencia;

    public function __construct() {
        $this->GENERAL = new GeneralClass();

        $configuracion = ConfiguracionModel::where('dato', 'pincode')->get();
        foreach ($configuracion as $conf) {
            $this->pincode = $conf->valor;
        }
        $configuracion = ConfiguracionModel::where('dato', 'serial_number')->get();
        foreach ($configuracion as $conf) {
            $this->serialNumber = '20158851';
            //$this->serialNumber = $conf->valor;
        }

        $configuracion = ConfiguracionModel::where('dato', 'ambiente')->get();
        foreach ($configuracion as $conf) {
            $this->ambiente = $conf->valor;
        }
        $configuracion = ConfiguracionModel::where('dato', 'emision')->get();
        foreach ($configuracion as $conf) {
            $this->emision = $conf->valor;
        }

        $configuracion = ConfiguracionModel::where('dato', 'contingencia')->get();
        foreach ($configuracion as $conf) {
            $this->contingencia = $conf->valor;
        }

        $this->subcert = \Config::get('certificados.subcert');
        $this->cacert = \Config::get('certificados.cacert');
        $this->p12 = \Config::get('certificados.p12');
        $this->certificadoPath = \Config::get('rutas.documentos') . "certificados/";
        $this->workPath = \Config::get('rutas.documentos') . "certificados/temp-dir/";
        $this->oppenSSL = " openssl ";
        $this->digestAlgorithm = "sha1";

        $this->digestAlgorithmURI = "http://www.w3.org/2000/09/xmldsig#sha1";
        $this->signatureAlgorithURI = "http://www.w3.org/2000/09/xmldsig#rsa-sha1";
        $this->docType = 'F';
    }

    public function getEmision(){
        return $this->emision;
    }

    public function firmar($documento) {
        $returnValue = array();

        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('default_socket_timeout', 600);

        $comprobante = file_get_contents($documento);

        $xmlComprobante = new \DOMDocument("1.0", "utf-8");
        $xmlComprobante->loadXML($comprobante);

        if ($this->emision == '2') {

            $claveAcceso = $this->generarClaveAccesoContingencia($documento);
        } else {

            $claveAcceso = $this->generarClaveAcceso($documento);
        }
    


        try {
           $signedContent = create_xades_bes($xmlComprobante, $this->p12, $this->pincode, $this->oppenSSL, $this->certificadoPath, $this->digestAlgorithm, $this->digestAlgorithmURI, $this->signatureAlgorithURI, $this->docType . '-' . $this->serialNumber, $this->subcert, $this->cacert, $this->workPath, $this->ambiente, $this->emision, $claveAcceso);

            $fragment = $xmlComprobante->createDocumentFragment();

            $fragment->appendXML($signedContent->C14N(false, false));

            $xmlComprobante->documentElement->appendChild($fragment);
            //file_write($path_content_signed . $data_input['name'], $xmlComprobante->saveXML(NULL, LIBXML_NOEMPTYTAG));
            $returnValue[0] = true;
            $returnValue[1] = $xmlComprobante;
        } catch (Exception $e) {
            $returnValue[0] = false;
            $returnValue[1] = utf8_encode("HUBO UN ERROR Y NO SE HA PODIDO GENERAR EL ARCHIVO XML FIRMADO: " . $e->getMessage());
            //print_r($returnValue);

            echo "entro al catch";

//    var_dump($returnValue);
        }

        return $returnValue;
    }

    public function generarClaveAccesoErp($datos) {
        $clave = "";
        $fechaEmision;

        $codDoc = "" . $datos['codDoc'];
        $ruc = "" . $datos['ruc'];
        $secuencial = "" . $datos['secuencial'];
        $serie = "" . $datos['estab'] . "" . $datos['ptoEmi'];
        $fechaEmision = str_replace('/', '', $datos['fechaEmision']);

        switch ($codDoc) {

            case '01':
                $this->docType = 'F';
                break;

            case '04':
                $this->docType = 'NC';
                break;
            case '05':
                $this->docType = 'ND';
                break;
            case '06':
                $fechaEmision = date('dmY');
                $this->docType = 'GR';
                break;
            case '07':
                $this->docType = 'CR';
                break;
        }

        $clave.=$fechaEmision;

        $clave.=$codDoc;

        $clave.=$ruc;

        $clave.=$this->ambiente;

        $clave.=$serie;

        $clave.=$secuencial;

        $clave.=$this->serialNumber;

        $clave.=$this->emision;
        $digitoVerificador = $this->obtenerModulo11($clave);
        
        $clave.=$digitoVerificador;

        return $clave;
    }

    private function generarClaveAcceso($doc) {
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
                $this->docType = 'F';
                break;

            case '04':
                $fechaEmision = str_replace('/', '', $xml->infoNotaCredito->fechaEmision);
                $this->docType = 'NC';
                break;
            case '05':
                $fechaEmision = str_replace('/', '', $xml->infoNotaDebito->fechaEmision);
                $this->docType = 'ND';
                break;
            case '06':
                $fechaEmision = date('dmY');
                $this->docType = 'GR';
                break;
            case '07':
                $fechaEmision = str_replace('/', '', $xml->infoCompRetencion->fechaEmision);
                $this->docType = 'CR';
                break;
        }


        $clave.=$fechaEmision;

        $clave.=$codDoc;

        $clave.=$ruc;

        $clave.=$this->ambiente;

        $clave.=$serie;

        $clave.=$secuencial;

		if ($xml->infoAdicional->campoAdicional[0] == 'OPF'){//DPS - 20191127 - FERIA
			$clave.=substr($secuencial, 1, 9);//DPS - 20191127 - FERIA
		}else{//DPS - 20191127 - FERIA
			$clave.=$this->serialNumber;
		}//DPS - 20191127 - FERIA

        $clave.=$this->emision;
        $digitoVerificador = $this->obtenerModulo11($clave);
       // echo $clave . "----" . $digitoVerificador;
        
        //die();
        $clave.=$digitoVerificador;




        return $clave;
    }

    private function generarClaveAccesoContingencia($doc) {
        $xml = new \SimpleXMLElement($doc, null, true);
        $clave = "";
        $fechaEmision;

        $codDoc = "" . $xml->infoTributaria->codDoc;
        $ruc = "" . $xml->infoTributaria->ruc;
        $secuencial = "" . $xml->infoTributaria->secuencial;
        $serie = "" . $xml->infoTributaria->estab . "" . $xml->infoTributaria->ptoEmi;
        $contingencia = ContingenciaModel::where('usado', '0')->orderBy('id', 'asc')->take(1)->get();
        $codigoNumerico = '00000000000000000000000';
        $idContingencia = 0;
        foreach ($contingencia as $cont) {
            $codigoNumerico = substr($cont->numero, 14);
            $idContingencia = $cont->id;
        }

        $CONTINGENCIA = ContingenciaModel::find($idContingencia);
        $CONTINGENCIA->usado = 1;
        $CONTINGENCIA->save();



        switch ($codDoc) {

            case '01':
                $fechaEmision = str_replace('/', '', $xml->infoFactura->fechaEmision);
                $this->docType = 'F';
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
        }


        $clave.=$fechaEmision;

        $clave.=$codDoc;

        $clave.=$ruc;

        $clave.=$this->ambiente;
        

        $clave.=$codigoNumerico;

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
            $sumatorio = $sumatorio + (intval($x_claveAcceso[$i]) * $x);
            $x++;
        }
        $digito = $this->my_bcmod($sumatorio, 11);
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

    private function my_bcmod($x, $y) {
        // how many numbers to take at once? carefull not to exceed (int) 
        $take = 5;
        $mod = '';

        do {
            $a = (int) $mod . substr($x, 0, $take);
            $x = substr($x, $take);
            $mod = $a % $y;
        } while (strlen($x));

        return (int) $mod;
    }

}
