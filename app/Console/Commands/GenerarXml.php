<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Customer;
use App\CustomerGroup;
use App\Warehouse;
use App\Biller;
use App\Brand;
use App\Category;
use App\Product;
use App\Unit;
use App\Tax;
use App\Sale;
use App\Delivery;
use App\PosSetting;
use App\Product_Sale;
use App\Product_Warehouse;
use App\Payment;
use App\Account;
use App\Coupon;
use App\GiftCard;
use App\PaymentWithCheque;
use App\PaymentWithGiftCard;
use App\PaymentWithCreditCard;
use App\PaymentWithPaypal;
use App\User;
use App\Variant;
use App\ProductVariant;
use DB;
use App\GeneralSetting;
use Stripe\Stripe;
use NumberToWords\NumberToWords;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\UserNotification;
use Illuminate\Support\Facades\Mail;
use Srmklive\PayPal\Services\ExpressCheckout;
use Srmklive\PayPal\Services\AdaptivePayments;
use GeniusTS\HijriDate\Date;
use Illuminate\Support\Facades\Validator;

class GenerarXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Generar_Xml';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar_Xml';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /////facturacion DM

        
        $action=''; 

        $order =  Sale::select('sales.*')
        ->with('biller', 'customer', 'warehouse', 'user')
        ->join('customers', 'sales.customer_id', '=', 'customers.id')
        ->where('sales.facturacion_status','=', 0)
        ->get();

       //$json_print= json_encode($order);
       //$this->info($json_print);
        //die();

        if ($order) {

        foreach ($order as $key => $value) {

            

            $sale_id=$value->id;
            $warehouse_id=$value->warehouse_id;
          
            # code...
            $data                    = array();
            $data['name']            = $value->customer->name;
            $data['address']         = $value->customer->address.$value->customer->city;
            $data['phone']           = $value->customer->phone_number;
            $data['email']           = $value->customer->email;
            $data['comment']         = 'comment';
            $data['payment_method']  = 'payment_method';
            $data['shipping_method'] = 'shipping_method';
            $data['created_at']      = '2023-01-26 09:35:24';
            $data['currency']        = 'USD';
           // $data['exchange_rate']   = ''$order['exchange_rate']'';
            $data['subtotal']        = $value->total_price;
            $data['tax']             = $value->order_tax;
            $data['shipping']        = $value->shipping_cost;
            $data['discount']        = $value->total_discount;
            $data['total']           = $value->grand_total;
            $data['received']        = $value->paid_amount;
            $data['balance']         = $value->paid_amount;
            $data['other_fee']       = 0;
            $data['comment']         = 'comment';
            $data['country']         = 'country';
            $data['id']              = $value->id;
          //  $data['seq']             = $order->seq;
            $data['seq']             = rand(100000000,999999999);
            $data['details'] = [];


//XML  FIRST PART   
                    
$dom = new \DOMDocument('1.0', 'utf-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
//create the main tags, without values
$factura = $dom->createElement('factura');
$info_tributaria = $dom->createElement('infoTributaria');
$infoFactura = $dom->createElement('infoFactura');

////informacion triburaria


$razonSocial = $dom->createElement('razonSocial','MDAZA CARRILLO CINDY LORENA');
$nombreComercial = $dom->createElement('nombreComercial','DAZA CARRILLO CINDY LORENA');
$ruc = $dom->createElement('ruc','1757703580001');
$codDoc = $dom->createElement('codDoc','01' );
$estab = $dom->createElement('estab','001');
$ptoEmi = $dom->createElement('ptoEmi','001');
$secuencial = $dom->createElement('secuencial',$data['seq']);
$dirMatriz = $dom->createElement('dirMatriz','EL TABLON');

//fin infoTributaria

   
                    


$attr_fact2 = $dom->createAttribute('id');
$attr_fact2->value = 'comprobante';
$factura->appendChild($attr_fact2);
$attr_fact = $dom->createAttribute('version');
$attr_fact->value = '1.1.0';
$factura->appendChild($attr_fact);



//create the XML structure
$factura->appendChild($info_tributaria);
$info_tributaria->appendChild($razonSocial);
$info_tributaria->appendChild($nombreComercial);
$info_tributaria->appendChild($ruc);
$info_tributaria->appendChild($codDoc);
$info_tributaria->appendChild($estab);
$info_tributaria->appendChild($ptoEmi);
$info_tributaria->appendChild($secuencial);
$info_tributaria->appendChild($dirMatriz);
 ///xml
         
         
         

$factura->appendChild($infoFactura);
//$fechaEmision = $dom->createElement('fechaEmision', $data['created_at']);
$fechaEmision = $dom->createElement('fechaEmision', date("d/m/Y"));
$dirEstablecimiento = $dom->createElement('dirEstablecimiento',$data['address']);
//$contribuyenteEspecial = $dom->createElement('contribuyenteEspecial','5368');
$obligadoContabilidad = $dom->createElement('obligadoContabilidad','SI');
$tipoIdentificacionComprador = $dom->createElement('tipoIdentificacionComprador','05');
$razonSocialComprador = $dom->createElement('razonSocialComprador',$data['name']);
$identificacionComprador = $dom->createElement('identificacionComprador','1716656952');
$totalSinImpuestos = $dom->createElement('totalSinImpuestos',$data['subtotal']);
$totalDescuento = $dom->createElement('totalDescuento','0.00');

//
$infoFactura->appendChild($fechaEmision);
$infoFactura->appendChild($dirEstablecimiento);
//$infoFactura->appendChild($contribuyenteEspecial);
$infoFactura->appendChild($obligadoContabilidad);
$infoFactura->appendChild($tipoIdentificacionComprador);
$infoFactura->appendChild($razonSocialComprador);
$infoFactura->appendChild($identificacionComprador);
$infoFactura->appendChild($totalSinImpuestos);
$infoFactura->appendChild($totalDescuento);


//

$totalConImpuestos = $dom->createElement('totalConImpuestos');
$infoFactura->appendChild($totalConImpuestos);

$totalImpuesto = $dom->createElement('totalImpuesto');
$totalConImpuestos->appendChild($totalImpuesto);





$codigo = $dom->createElement('codigo',2);
$codigoPorcentaje = $dom->createElement('codigoPorcentaje',2);
$baseImponible = $dom->createElement('baseImponible',$data['subtotal']);
$tarifa = $dom->createElement('tarifa','12.00');
$valor = $dom->createElement('valor',$data['tax']);

$totalImpuesto->appendChild($codigo);
$totalImpuesto->appendChild($codigoPorcentaje);
$totalImpuesto->appendChild($baseImponible);
$totalImpuesto->appendChild($tarifa);
$totalImpuesto->appendChild($valor);

//

$propina = $dom->createElement('propina',0 );
$infoFactura->appendChild($propina);

//

$importeTotal = $dom->createElement('importeTotal',$data['total']);
$infoFactura->appendChild($importeTotal);

//

$moneda = $dom->createElement('moneda',$data['currency']);
$infoFactura->appendChild($moneda);


//
$pagos = $dom->createElement('pagos');
$infoFactura->appendChild($pagos);

$pago = $dom->createElement('pago');
$pagos->appendChild($pago);



//
$detalles = $dom->createElement('detalles');
$factura->appendChild($detalles);


                    //Adicionales
                    
           
             
                  //@I_LINEA_ADICIONAL = 1 
                  $v_nombre1 = 'CodSociedad';
                  $v_valor1 =  'aa';

                  //@I_LINEA_ADICIONAL = 2  
                  $v_nombre2 = 'CodInternoSAP';
                  $v_valor2 =  'bb' ; 

                    //@I_LINEA_ADICIONAL = 3  
                    $v_nombre3 = 'CorreoCliente';
                    $v_valor3 =  'cc';


                    
                    //XML ADIIONALES///
                    
$infoAdicional = $dom->createElement('infoAdicional');
$factura->appendChild($infoAdicional);
                                    
                                   
//                   
$campoadicional = $dom->createElement('campoAdicional',$v_valor1);
$infoAdicional->appendChild($campoadicional);
$attr_adicional = $dom->createAttribute('nombre');
$attr_adicional->value = $v_nombre1;
$campoadicional->appendChild($attr_adicional);
//

//                   
$campoadicional = $dom->createElement('campoAdicional',$v_valor2);
$infoAdicional->appendChild($campoadicional);
$attr_adicional = $dom->createAttribute('nombre');
$attr_adicional->value = $v_nombre2;
$campoadicional->appendChild($attr_adicional);
//

//                   
$campoadicional = $dom->createElement('campoAdicional',$v_valor3);
$infoAdicional->appendChild($campoadicional);
$attr_adicional = $dom->createAttribute('nombre');
$attr_adicional->value = $v_nombre3;
$campoadicional->appendChild($attr_adicional);
//
///detalle


$lims_product_sale_data = DB::table('sales')
->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
//->join('products', 'product_sales.id', '=', 'products.id')
->where([
        ['product_sales.sale_id', $sale_id],
        ['sales.warehouse_id', $warehouse_id]
])->get();




foreach ($lims_product_sale_data as $key => $detail) {


    $lims_product_data = Product::where('id', $detail->product_id)->first();


    $data['details'][] = [
        'no' => $key + 1, 
        'sku' => $lims_product_data->code, 
        'name' => $lims_product_data->name, 
        'qty' => $detail->qty, 
        'price' => $lims_product_data->price, 
        'total_price' => $detail->total,
    ];


$detalle = $dom->createElement('detalle');
$detalles->appendChild($detalle);
    
    //FACTURACIÓN ELECTRÓNICA LINE--------------------------------------------------------------------------------------------------------

    
    $codigoPrincipal = $dom->createElement('codigoPrincipal','PUBLI');
    $detalle->appendChild($codigoPrincipal);
    
    $codigoAuxiliar = $dom->createElement('codigoAuxiliar','PUBLI');
    $detalle->appendChild($codigoAuxiliar);
    
    $descripcion = $dom->createElement('descripcion',$lims_product_data->name);
    $detalle->appendChild($descripcion);
    
    $cantidad = $dom->createElement('cantidad',$detail->qty);
    $detalle->appendChild($cantidad);
    
    $precioUnitario = $dom->createElement('precioUnitario',$lims_product_data->price);
    $detalle->appendChild($precioUnitario);
     
    $descuento = $dom->createElement('descuento',0);
    $detalle->appendChild($descuento);
     
    $precioTotalSinImpuesto = $dom->createElement('precioTotalSinImpuesto',$lims_product_data->price);
    $detalle->appendChild($precioTotalSinImpuesto);
    //impuestos
    
    $detallesAdicionales = $dom->createElement('detallesAdicionales');
    $detalle->appendChild($detallesAdicionales);
    
     //@I_LINEA_ADICIONAL = 1 
    $v_nombre = 'detalle1' ;
    $v_valor = 'fact';
    $det_adicional = $dom->createElement('detAdicional');
    $detallesAdicionales->appendChild($det_adicional);
    $att_det_adicional = $dom->createAttribute('nombre');
    $att_det_adicional->value = $v_nombre;
    $det_adicional->appendChild($att_det_adicional);
    $att_det_adicional = $dom->createAttribute('valor');
    $att_det_adicional->value = $v_valor;
    $det_adicional->appendChild($att_det_adicional);
    
    
    $impuestos = $dom->createElement('impuestos');
    $detalle->appendChild($impuestos);
    $impuesto = $dom->createElement('impuesto');
    $impuestos->appendChild($impuesto);
     
     
    $codigo = $dom->createElement('codigo',2);
    $impuesto->appendChild($codigo);  
    
    $codigoPorcentaje = $dom->createElement('codigoPorcentaje',2);
    $impuesto->appendChild($codigoPorcentaje);  
    
    $tarifa = $dom->createElement('tarifa',12);
    $impuesto->appendChild($tarifa);  
    
    $baseImponible = $dom->createElement('baseImponible',$lims_product_data->price);
    $impuesto->appendChild($baseImponible);
    
    $valor = $dom->createElement('valor',$lims_product_data->price);
    $impuesto->appendChild($valor); 
     

        }

        }
    }

       

                            //$v_codigoPago          =  \Config::get("constantes.formas_pago." . $formaPago->v_ec2_forma_pago);  
                            $v_codigoPago          =  '01';
                            $n_TotalPago           =  $lims_product_data->price;
                            $i_plazo               =  0;    
                            $v_unidadTiempo        =  'dias';  
                        
                        

$formaPago = $dom->createElement('formaPago', $v_codigoPago );
$total = $dom->createElement('total',$n_TotalPago);
$plazo = $dom->createElement('plazo',$i_plazo );
$unidadTiempo = $dom->createElement('unidadTiempo',$v_unidadTiempo);

$pago->appendChild($formaPago);
$pago->appendChild($total);
$pago->appendChild($plazo);
$pago->appendChild($unidadTiempo);


$dom->appendChild($factura);
//print_r ($dom->saveXML());
$xml_string =$dom->saveXML();

//Y se guarda en el nombre del archivo 'achivo.xml', y el obejto nstanciado
$hoy = date("dmYHis");


\File::put(public_path().'/Documentos/Originales/'.$hoy.'.xml', $xml_string); 

 $this->info(public_path().'/Documentos/Originales/'.$hoy.'.xml', $xml_string);



\Log::info(['TIPO' => "INFO GENERADA", 'XML numero legal' => $hoy.'.xml']);




    }
}
