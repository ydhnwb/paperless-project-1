<?php

namespace App\Http\Controllers\v1\reports;

use App\Exports\InvoiceExport;
use App\Exports\ReportsExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Order;
use App\OrderDetail;
use App\Store;
//use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ReportController extends Controller
{
    public function invoice(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();
        $details = new InvoiceResource($order);

        $order_details = OrderDetail::where('order_id', $order->id)->get();
        $array_total_price = (array)null;
        foreach ($order_details as $item) {
            $array_total_price[] = $item->price * $item->quantity;
        }
        $total_price = array_sum($array_total_price);
        $total_price_with_discount = $total_price-$details->discount;

//        return view('exports.invoice', compact(['details', 'total_price', 'total_price_with_discount']));

        $pdf = PDF::loadView('exports.invoice', compact(['details', 'total_price', 'total_price_with_discount']));
        $filename = $order->code . ".pdf";
        $filepath = "invoice/" . $filename;
        Storage::disk('s3')->put($filepath, $pdf->output());
        $url = Storage::disk('s3')->url($filepath,$filename);
        return response()->json([
            'status' => true,
            'message' => "OK",
            'data' => [
                'url' => $url
            ]
        ]);

    }

    public function report(Request $request)
    {
        $store_id = $request->store_id;
        $store = Store::where('id', $store_id)->first();
        $filename = "$store->name-" . date_format(now(), 'ymd-His') . ".xlsx";
        $filepath = "download/" . $filename;
        Excel::store(new ReportsExport($store_id), $filepath,'s3', \Maatwebsite\Excel\Excel::XLSX);
        $excel = Storage::disk('s3')->url($filepath, $filename);
        return response()->json([
            'status' => true,
            'message' => "Ok",
            'data' => [
                'url' => $excel
            ]
        ]);
    }
}
