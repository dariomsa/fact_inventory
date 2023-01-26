<?php

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');

class Tcpdf_testClass extends TCPDF {

    private $pdf;

    public function Footer() {
        //$image_file = "img/bg_bottom_releve.jpg";
        //$this->Image($image_file, 11, 241, 189, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        $this->SetFont('helvetica', 'N', 6);
        $this->Cell(0, 5, "Facturación Electrónica :: GRUPO EL COMERCIO", 0, false, 'L', 0, '', 0, false, 'T', 'M');


        $cur_y = $this->y;
        $this->SetTextColorArray($this->footer_text_color);
        //set style for cell border
        $line_width = (0.85 / $this->k);
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
        //print document barcode
        /*$barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->Ln($line_width);
            $barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
            $style = array(
                'position' => $this->rtl?'R':'L',
                'align' => $this->rtl?'R':'L',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false,
                'text' => false
            );
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
        }*/
        $w_page = isset($this->l['w_page']) ? $this->l['w_page'].' ' : '';
        if (empty($this->pagegroups)) {
            $pagenumtxt = $w_page.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
        } else {
            $pagenumtxt = $w_page.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        if ($this->getRTL()) {
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 5, $pagenumtxt, 'T', 0, 'L');
        } else {
            $this->SetX($this->original_lMargin);
            $this->Cell(0, 5, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
        }


    }
}

class Tcpdf_ProdClass extends TCPDF {
    private $pdf;
    public function Footer() {
        //$image_file = "img/bg_bottom_releve.jpg";
        //$this->Image($image_file, 11, 241, 189, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        $this->SetFont('helvetica', 'N', 6);
        $this->Cell(0, 5, "Facturación Electrónica :: GRUPO EL COMERCIO", 0, false, 'L', 0, '', 0, false, 'T', 'M');


        $cur_y = $this->y;
        $this->SetTextColorArray($this->footer_text_color);
        //set style for cell border
        $line_width = (0.85 / $this->k);
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));

        $w_page = isset($this->l['w_page']) ? $this->l['w_page'].' ' : '';
        if (empty($this->pagegroups)) {
            $pagenumtxt = $w_page.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
        } else {
            $pagenumtxt = $w_page.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        if ($this->getRTL()) {
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 5, $pagenumtxt, 'T', 0, 'L');
        } else {
            $this->SetX($this->original_lMargin);
            $this->Cell(0, 5, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
        }


    }
    public function setHtmlHeader($htmlHeader,$numero) {
        $this->htmlHeader = $htmlHeader;
        $this->numeroHeader = $numero;
    }
    public function Header(){
    $style = array(
        'position' => '',
        'align' => 'C',
        'stretch' => false,
        'fitwidth' => true,
        'cellfitalign' => '',
        'border' => false,
        'hpadding' => 'auto',
        'vpadding' => 'auto',
        'fgcolor' => array(0,0,0),
        'bgcolor' => false, //array(255,255,255),
        'text' => true,
        'font' => 'helvetica',
        'fontsize' => 8,
        'stretchtext' => 4
    );
     $this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = 10, $this->htmlHeader, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true);
     $this->write1DBarcode($this->numeroHeader, 'C128', 110, 63, '80', 15, 0.4, $style, 'N');
    }



}


function generar_pdf_test($html, $numero, $archivo, $subruta = "",$data){
    $pdf = new Tcpdf_ProdClass(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $_html = '<style>
      *{
        font-family:Arial, sans-serif;
        font-size:8px;
      }
      .table-curve{
        border: 1px solid #000000;
        border-radius: 10px;
      }
      .table-valores td{
        padding:2px;
      }
     
    </style>
         <table width="630" border="0" cellpadding="10" cellspacing="0">
      <tbody>
        <tr>
          <td align="center" width="310px"><img src="https://facturacion.grupoelcomercio.com/images/logo_elcomerciobn.png" width="200"></td>
          <td rowspan="2" valign="top" width="10">&nbsp;</td>
          <td rowspan="2" valign="top" width="310px" class="table-curve" style ="line-height: 9px;">
            <span style="font-size:12px">R.U.C.: 1790008851001</span>
            <p style="font-size:14px">'.$data['titulo_plantilla'].'</p>
            <p>No. '.$data['estab'].'-'.$data['ptoEmi'].'-'.$data['secuencial'].'</p>
            <p>NÚMERO DE AUTORIZACIÓN:</p>
            <p>'.$data['numeroAutorizacion'].'</p>
            <p>FECHA Y HORA DE AUTORIZACIÓN: '.$data['fechaAutorizacion'].'</p>
            <p>AMBIENTE: '.$data['ambiente'].'</p>
            <p>EMISIÓN: '.$data['tipoEmision'].'</p>
            <p>CLAVE DE ACCESO: </p>
            <p></p><p></p><p></p><p></p>
          </td>
        </tr>
        <tr>
          <td valign="top" style="padding:15px;" width="310" class="table-curve">
            <span style="font-size:14px">GRUPO EL COMERCIO C.A.</span>
            <p>Dir Matriz: '.$data['dirMatriz'].'</p>
            <p>Dir Sucursal: '.$data['dirEstablecimiento'].'</p>
            <p>Contribuyente Especial Nro 5368<br>OBLIGADO A LLEVAR CONTABLIDAD: SI</p>
          </td>
        </tr>
      </tbody>
    </table>
    <table border="0">
        <tr style="line-height: 60%;" > 
           <td></td>
        </tr>
    </table>';
  if($data['codDoc'] == '06')
  { 
      $_html .= '<table width="630" border="0" cellpadding="5" cellspacing="0" style="border:1px solid #000;">
          <tbody>
            <tr>
              <td colspan="2">Identificación (Transportista): </td>
              <td colspan="3">'.$data['rucTransportista'].'</td>
            </tr>
            <tr>
              <td colspan="2">Razón Social / Nombres y Apellidos: </td>
              <td colspan="3">'.$data['transportista'].'</td>
            </tr>
            <tr>
              <td colspan="1">Placa: </td>
              <td colspan="4">'.$data['placa'].'</td>
            </tr>
            <tr>
              <td colspan="1">Punto de partida: </td>
              <td colspan="4">'.$data['dirPartida'].'</td>
            </tr>
            <tr>
              <td colspan="1">Fecha inicio Transporte: </td>
              <td colspan="2">'.$data['fechaIniTransporte'].'</td>
              <td colspan="1">Fecha fin Transporte: </td>
              <td colspan="1">'.$data['fechaFinTransporte'].'</td>
            </tr>
            <tr>
              <td width="160">&nbsp;</td>
              <td width="65">&nbsp;</td>
              <td width="90">&nbsp;</td>
              <td width="155">&nbsp;</td>
              <td width="160">&nbsp;</td>
            </tr>
          </tbody>
        </table>';
        $pdf->SetMargins(PDF_MARGIN_LEFT, 110, PDF_MARGIN_RIGHT);
  }
  else{
        $_html .= '<table width="630" border="0" cellpadding="5" cellspacing="0" style="border:1px solid #000;">
          <tbody>
            <tr>
              <td colspan="2">Razón Social / Nombres y Apellidos: </td>
              <td colspan="2">'.$data['razonSocial'].'</td>
              <td>Identificación:</td>
              <td>'.$data['ruc'].'</td>
            </tr>';
        if($data['codDoc'] == '07' || $data['codDoc'] == '01')
           { 
                $_html .= '<tr>
                  <td>Fecha Emisión: </td>
                  <td>'.$data['fechaEmision'].'</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>Guía Remisión: </td>
                  <td>&nbsp;</td>
                </tr>
              </tbody>
            </table>
            <table border="0">
                <tr style="line-height: 60%;" > 
                   <td></td>
                </tr>
            </table>';
            $pdf->SetMargins(PDF_MARGIN_LEFT, 100, PDF_MARGIN_RIGHT);
        }
        else{
            $_html .= '<tr>
          <td>Fecha Emisión: </td>
          <td>'.$data['fechaEmision'].'</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td colspan="4" style = "border-top: 1px solid #000000; width:65%;"></td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td colspan="2">Comprobante que se modifica</td>
          <td colspan="2">'.$data['cm_tipo'].'</td>
          <td colspan="2">'.$data['cm_numero'].'</td>
        </tr>
        <tr>
          <td colspan="2">Fecha Emisión (Comprobante a modificar)</td>
          <td colspan="2">'.$data['cm_fecha'].'</td>
          <td colspan="2">&nbsp;</td>
        </tr>
        </tbody>
        </table>';
        $pdf->SetMargins(PDF_MARGIN_LEFT, 110, PDF_MARGIN_RIGHT);
        }
  }
    $pdf->setHtmlHeader($_html,$data['clave_acceso']);
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Grupo El Comercio');
    $pdf->SetTitle('Facturación Electrónica :: Grupo El Comercio');
    $pdf->SetSubject('Facturación Electrónica');

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //$pdf->SetPrintHeader(true);
    $pdf->AddPage();

    $pdf->writeHTML($html, true, false, true, false, '');
    $style = array(
        'position' => '',
        'align' => 'C',
        'stretch' => false,
        'fitwidth' => true,
        'cellfitalign' => '',
        'border' => false,
        'hpadding' => 'auto',
        'vpadding' => 'auto',
        'fgcolor' => array(0,0,0),
        'bgcolor' => false, //array(255,255,255),
        'text' => true,
        'font' => 'helvetica',
        'fontsize' => 8,
        'stretchtext' => 4
    );
    //$pdf->write1DBarcode($numero, 'C128', 110, 80, '80', 15, 0.4, $style, 'N');

    if (!file_exists(\Config::get('rutas.local').$subruta)) {
        mkdir(\Config::get('rutas.local').$subruta, 0775, true);
    }
    $pdf->Output(\Config::get('rutas.local').$subruta."/".$archivo, 'F');
	chown(\Config::get('rutas.local').$subruta."/".$archivo,'apache');
	chmod(\Config::get('rutas.local').$subruta."/".$archivo,0775);
	
}


function generar_pdf($html, $numero, $archivo, $subruta = "",$data){


// create new PDF document
$pdf = new Tcpdf_ProdClass(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


$_html = '<style>
      *{
        font-family:Arial, sans-serif;
        font-size:8px;
      }
      .table-curve{
        border: 1px solid #000000;
        border-radius: 10px;
      }
      .table-valores td{
        padding:2px;
      }
     
    </style>
         <table width="630" border="0" cellpadding="10" cellspacing="0">
      <tbody>
        <tr>
          <td align="center" width="310px"><img src="https://facturacion.grupoelcomercio.com/images/logo_elcomerciobn.png" width="200"></td>
          <td rowspan="2" valign="top" width="10">&nbsp;</td>
          <td rowspan="2" valign="top" width="310px" class="table-curve" style ="line-height: 9px;">
            <span style="font-size:12px">R.U.C.: 1790008851001</span>
            <p style="font-size:14px">'.$data['titulo_plantilla'].'</p>
            <p>No. '.$data['estab'].'-'.$data['ptoEmi'].'-'.$data['secuencial'].'</p>
            <p>NÚMERO DE AUTORIZACIÓN:</p>
            <p>'.$data['numeroAutorizacion'].'</p>
            <p>FECHA Y HORA DE AUTORIZACIÓN: '.$data['fechaAutorizacion'].'</p>
            <p>AMBIENTE: '.$data['ambiente'].'</p>
            <p>EMISIÓN: '.$data['tipoEmision'].'</p>
            <p>CLAVE DE ACCESO: </p>
            <p></p><p></p><p></p><p></p>
          </td>
        </tr>
        <tr>
          <td valign="top" style="padding:15px;" width="310" class="table-curve">
            <span style="font-size:14px">GRUPO EL COMERCIO C.A.</span>
            <p>Dir Matriz: '.$data['dirMatriz'].'</p>
            <p>Dir Sucursal: '.$data['dirEstablecimiento'].'</p>
            <p>Contribuyente Especial Nro 5368<br>OBLIGADO A LLEVAR CONTABLIDAD: SI</p>
          </td>
        </tr>
      </tbody>
    </table>
    <table border="0">
        <tr style="line-height: 60%;" > 
           <td></td>
        </tr>
    </table>';
  if($data['codDoc'] == '06')
  { 
      $_html .= '<table width="630" border="0" cellpadding="5" cellspacing="0" style="border:1px solid #000;">
          <tbody>
            <tr>
              <td colspan="2">Identificación (Transportista): </td>
              <td colspan="3">'.$data['rucTransportista'].'</td>
            </tr>
            <tr>
              <td colspan="2">Razón Social / Nombres y Apellidos: </td>
              <td colspan="3">'.$data['transportista'].'</td>
            </tr>
            <tr>
              <td colspan="1">Placa: </td>
              <td colspan="4">'.$data['placa'].'</td>
            </tr>
            <tr>
              <td colspan="1">Punto de partida: </td>
              <td colspan="4">'.$data['dirPartida'].'</td>
            </tr>
            <tr>
              <td colspan="1">Fecha inicio Transporte: </td>
              <td colspan="2">'.$data['fechaIniTransporte'].'</td>
              <td colspan="1">Fecha fin Transporte: </td>
              <td colspan="1">'.$data['fechaFinTransporte'].'</td>
            </tr>
            <tr>
              <td width="160">&nbsp;</td>
              <td width="65">&nbsp;</td>
              <td width="90">&nbsp;</td>
              <td width="155">&nbsp;</td>
              <td width="160">&nbsp;</td>
            </tr>
          </tbody>
        </table>';
        $pdf->SetMargins(PDF_MARGIN_LEFT, 110, PDF_MARGIN_RIGHT);
  }
  else{
    $_html .= '<table width="630" border="0" cellpadding="5" cellspacing="0" style="border:1px solid #000;">
      <tbody>
        <tr>
          <td colspan="2">Razón Social / Nombres y Apellidos: </td>
          <td colspan="2">'.$data['razonSocial'].'</td>
          <td>Identificación:</td>
          <td>'.$data['ruc'].'</td>
        </tr>';
    if($data['codDoc'] == '07' || $data['codDoc'] == '01')
       { 
            $_html .= '<tr>
              <td>Fecha Emisión: </td>
              <td>'.$data['fechaEmision'].'</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>Guía Remisión: </td>
              <td>&nbsp;</td>
            </tr>
          </tbody>
        </table>
        <table border="0">
            <tr style="line-height: 60%;" > 
               <td></td>
            </tr>
        </table>';
        $pdf->SetMargins(PDF_MARGIN_LEFT, 100, PDF_MARGIN_RIGHT);
    }
    else{
        $_html .= '<tr>
      <td>Fecha Emisión: </td>
      <td>'.$data['fechaEmision'].'</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td colspan="4" style = "border-top: 1px solid #000000; width:65%;"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2">Comprobante que se modifica</td>
      <td colspan="2">'.$data['cm_tipo'].'</td>
      <td colspan="2">'.$data['cm_numero'].'</td>
    </tr>
    <tr>
      <td colspan="2">Fecha Emisión (Comprobante a modificar)</td>
      <td colspan="2">'.$data['cm_fecha'].'</td>
      <td colspan="2">&nbsp;</td>
    </tr>
    </tbody>
    </table>';
    $pdf->SetMargins(PDF_MARGIN_LEFT, 110, PDF_MARGIN_RIGHT);
    }
}
    $pdf->setHtmlHeader($_html,$data['clave_acceso']);
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Grupo El Comercio');
$pdf->SetTitle('Facturación Electrónica :: Grupo El Comercio');
$pdf->SetSubject('Facturación Electrónica');

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//$pdf->SetPrintHeader(false);

if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}


	$pdf->AddPage();



$pdf->writeHTML($html, true, false, true, false, '');
/*$style = array(
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255),
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 8,
    'stretchtext' => 4
);
$pdf->write1DBarcode($numero, 'C128', 110, 80, '80', 15, 0.4, $style, 'N');
*/
if (!file_exists(\Config::get('rutas.local').$subruta)) {
    mkdir(\Config::get('rutas.local').$subruta, 0775, true);
}
$pdf->Output(\Config::get('rutas.local').$subruta."/".$archivo, 'F');
chown(\Config::get('rutas.local').$subruta."/".$archivo,'apache');
chmod(\Config::get('rutas.local').$subruta."/".$archivo,0775);
}