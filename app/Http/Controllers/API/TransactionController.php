<?php

namespace App\Http\Controllers\API;

use Exception;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

//use Illuminate\Support\Facades\Config;



class TransactionController extends Controller
{
     //satu fungsi dan menghandel semua request
    public function all (Request $request){
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if($id){
            $transaksi = Transaction::with(['food','user'])->find($id);
            if($transaksi){
                return ResponseFormatter::success(
                    $transaksi,
                    'Data transaksi berhasil diambil'
                );
            } else{
                return ResponseFormatter::error(
                    null,
                    'Data transaksi berhasil diambil', 484
                );
            }
        }

        $transaksi = Transaction::with(['food','user'])->where('user_id', Auth::user()->id);

        if($food_id){
            $transaksi->where('food_id', $food_id);
        }
        if($status){
            $transaksi->where('status', $status);
        }


        return ResponseFormatter::success(
            $transaksi->pageinate($limit),
            'Data list transaksi berhasil diambil'
        );


    }

    public function update(Request $request, $id){
        $transaksi = Transaction::findOrFail($id);

        $transaksi-> update($request->all());

        return ResponseFormatter::success($transaksi, 'Transaksi berhasil diperbaharui');
    }

    public function checkout (Request $request){
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);

        $transaksi =Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        //konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized =config('services.midtrans.isSanitized');
        Config::$is3ds =config('services.midtrans.is3ds');

        //panggil transaksi yang tadi dibuat
        $transaksi = Transaction::with(['food','user'])->find($transaksi->id);
        
        //membuat transaksi midtrans
        $midtrans =[
            'transaction_details'=> [
                'order_id' => $transaksi->id,
                'gross_amount' => (int) $transaksi->total,
            ],
            'customer_details'=> [
                'first_name' =>$transaksi->user->name,
                'email' => $transaksi->user->email,
            ],
            'enabled_payments' =>[
                'gopay', 'bank_transfer'],
                'vtweb' => []
            ];

            try{
                //Ambil halaman payments midtrans
                $paymentUrl= Snap::createTransaction($midtrans)->redirect_url;
                
                $transaksi->payment_url = $paymentUrl;
                $transaksi->save();

                //Mengembalikan data ke API

                return ResponseFormatter::success($transaksi, 'Transaksi berhasil');
            } 
            catch (Exception $e) {
                return ResponseFormatter::error($e->getMessage(), 'Transaksi gagal');
            }
        }
    }
