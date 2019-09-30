<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{

    public function __construct()
    {
        // $this->middleware('auth:api');
    }

   /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
   public function authenticate(Request $request)
   {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->input('email'))->first();
        if($user) {
            if(Hash::check($request->input('password'), $user->password)) {
                if(!$user->api_key) {
                    $apikey = base64_encode(str_random(40));
                    // User::where('email', $request->input('email'))->update(['api_key' => "$apikey"]);
                    $user->update(['api_key' => "$apikey"]);
                }

                return response()->json(['status' => 'success','api_key' => $user->api_key]);
            }
        }
        
        return response()->json(['status' => 'fail'],401);
   }

} 
