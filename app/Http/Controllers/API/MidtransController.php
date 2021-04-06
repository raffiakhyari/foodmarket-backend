<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Notifications\Notification;

class MidtransController extends Controller
{
    public function callback (Request $request){
       
        //set Konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized =config('services.midtrans.isSanitized');
        Config::$is3ds =config('services.midtrans.is3ds');

        //Buat instance midtrans notification
        $notification = new Notification();

        //Assign ke variable untuk memudahkan coding
        $status = $notification->transaksi_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;


        //Cari transaksi berdasarkan ID
        $transaksi = Transaction::findOrFail($order_id);

        //Handle notifikasi status midtrans
        if($status == 'capture'){

            if($type == 'credit_card'){

                if($fraud == 'challenge'){

                    $transaksi->status= 'PENDING';

                }else{

                    $transaksi->status= 'SUCCESS';
                }

            }
            
        }else if($status == 'settlement'){

            $transaksi->status = 'SUCCESS';

        }else if($status == 'pending'){

            $transaksi->status= 'PENDING';
            
        }else if($status == 'deny'){

            $transaksi->status= 'CANCELLED';
            
        }else if($status == 'expire'){

            $transaksi->status= 'CANCELLED';

        }else if($status == 'cancel'){

            $transaksi->status= 'CANCELLED';

        }

        //Simpan transaksi
        $transaksi->save();
    }

    public function success(){
        return view('midtrans.success');
    }

    public function unfinish(){
        return view('midtrans.unfinish');
    }

    public function error(){
        return view('midtrans.error');
    }
}
