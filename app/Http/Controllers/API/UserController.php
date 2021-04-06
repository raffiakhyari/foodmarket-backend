<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
//use Dotenv\Validator;
use Exception;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $Request){
        try {
            //validasi input
            $Request->validate([
                'email' => 'email|required',
                'password'=> 'required'
            ]);

            //mengecek credential(auth)
            $credentials = request(['email','password']);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'UnAuthorized'
                   
                ],  'AuthenticationFailed', 500);
            }

            // jika hash tidak sesuai maka beri error
            $user = User::where('email', $Request->email)-> first();
            if(!Hash::check($Request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            // Jika sukses maka loginkan
            $tokenResult = $user->createToken('authtoken')-> plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');

        } catch(Exception $error) {
            return ResponseFormatter::error([
                'message'=> 'something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function register (Request $request){
        try{
            //request validasi
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string','email', 'max:255','unique:users'],
                'password' => $this->passwordRules()
            ]);

            //pembuatan user sehingga tersimpan
                user ::create([
                    'name' =>$request->name,
                    'email' =>$request->email,
                    'address' =>$request->address,
                    'houseNumber' =>$request->houseNumber,
                    'phoneNumber' =>$request->phoneNumber,
                    'city' =>$request->city,
                    'password' =>Hash::make($request->password),
                ]);
                    // mengambil data yang tersimpan
                $user = User::where('email', $request->email)->first();

                $tokenResult = $user->createToken('authToken')->plaintTextToken;
                
                return ResponseFormatter::success([
                    'access_token' =>$tokenResult,
                    'token_type' =>'Bearer',
                    'user'=> $user
                ]);

        } catch (Exception $error){
            return ResponseFormatter::error([
                'message' =>'something went wrong',
                'error' => $error,

            ], 'Authentication Failed', 500);
        }
    }

    public function logout(Request $request){
        $token = $request->user()->curretAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoked');
    }

    public function fetch(Request $request){
        return ResponseFormatter::success(
            $request->user(),'Data profile user berhasil diambil');
    }

    public function updateProfile(Request $request){
        $data = $request-> all();
        $user = Auth::user();
        $user -> update($data);

        return ResponseFormatter::success($user, 'Profile Updated');
    }

    public function updatePhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'file' => 'require|image|max:2048'
        ]);
        
        if($validator->fails()){
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
            'Update photo fails', 481
            );
        }
        
        if ($request->file('file')){
            $file = $request->file->store('assets/user','public');

            //simpan foto ke dalam database(urlnya)

            $user =Auth::user();
            $user-> profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File successfully uploaded');
        }

    }
}

