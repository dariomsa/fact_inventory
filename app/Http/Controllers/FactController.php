<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProductPurchase;
use App\Product_Sale;
use App\ProductQuotation;
use App\Sale;
use App\Purchase;
use App\Quotation;
use App\Transfer;
use App\Returns;
use App\ProductReturn;
use App\ReturnPurchase;
use App\ProductTransfer;
use App\PurchaseProductReturn;
use App\Payment;
use App\Warehouse;
use App\Product_Warehouse;
use App\Expense;
use App\Payroll;
use App\User;
use App\Customer;
use App\Supplier;
use App\Variant;
use App\ProductVariant;
use DB;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        echo 'entro';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function indexReport()
    {
      
        $start_date = '1988-04-18';
        $end_date = '2023-01-24';
        $warehouse_id = 0;

       

        $lims_product_all = Product::select('id', 'name', 'qty', 'is_variant')->where('is_active', true)->get();
        foreach ($lims_product_all as $product) {
            $lims_product_sale_data = null;
            $variant_id_all = [];
            if($warehouse_id == 0){
                if($product->is_variant)
                    $variant_id_all = Product_Sale::distinct('variant_id')->where('product_id', $product->id)->whereDate('created_at', '>=' , $start_date)->whereDate('created_at', '<=' , $end_date)->pluck('variant_id');
                else
                    $lims_product_sale_data = Product_Sale::where('product_id', $product->id)->whereDate('created_at', '>=' , $start_date)->whereDate('created_at', '<=' , $end_date)->first();
            }
            else {
                if($product->is_variant)
                    $variant_id_all = DB::table('sales')
                        ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                        ->distinct('variant_id')
                        ->where([
                            ['product_sales.product_id', $product->id],
                            ['sales.warehouse_id', $warehouse_id]
                        ])->whereDate('sales.created_at','>=', $start_date)
                          ->whereDate('sales.created_at','<=', $end_date)
                          ->pluck('variant_id');
                else
                    $lims_product_sale_data = DB::table('sales')
                            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                                    ['product_sales.product_id', $product->id],
                                    ['sales.warehouse_id', $warehouse_id]
                            ])->whereDate('sales.created_at','>=', $start_date)
                              ->whereDate('sales.created_at','<=', $end_date)
                              ->first();
            }
            if($lims_product_sale_data) {
                $product_name[] = $product->name;
                $product_id[] = $product->id;
                $variant_id[] = null;
                if($warehouse_id == 0)
                    $product_qty[] = $product->qty;
                else {
                    $product_qty[] = Product_Warehouse::where([
                                    ['product_id', $product->id],
                                    ['warehouse_id', $warehouse_id]
                                ])->sum('qty');
                }
            }
            elseif(count($variant_id_all)) {
                foreach ($variant_id_all as $key => $variantId) {
                    $variant_data = Variant::find($variantId);
                    $product_name[] = $product->name.' ['.$variant_data->name.']';
                    $product_id[] = $product->id;
                    $variant_id[] = $variant_data->id;
                    if($warehouse_id == 0)
                        $product_qty[] = ProductVariant::FindExactProduct($product->id, $variant_data->id)->first()->qty;
                    else
                        $product_qty[] = Product_Warehouse::where([
                                        ['product_id', $product->id],
                                        ['variant_id', $variant_data->id],
                                        ['warehouse_id', $warehouse_id]
                                    ])->first()->qty;
                    
                }
            }
        }
        
        

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('report.sale_report',compact('product_id', 'variant_id', 'product_name', 'product_qty', 'start_date', 'end_date', 'lims_warehouse_list','warehouse_id'));
    }

}
