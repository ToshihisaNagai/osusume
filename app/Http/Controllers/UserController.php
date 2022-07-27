<?php

namespace App\Http\Controllers;

use App\User;
use App\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function mypage()
    {
        $user = Auth::user();

        return view('users.mypage', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $user = Auth::user();

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $user = Auth::user();

        $user->name = $request->input('name') ? $request->input('name') : $user->name;
        $user->email = $request->input('email') ? $request->input('email') : $user->email;
        $user->postal_code = $request->input('postal_code') ? $request->input('postal_code') : $user->postal_code;
        $user->address = $request->input('address') ? $request->input('address') : $user->address;
        $user->phone = $request->input('phone') ? $request->input('phone') : $user->phone;
        $user->update();

        return redirect()->route('mypage');
    }

    public function edit_address()
    {
        $user = Auth::user();

        return view('users.edit_address', compact('user'));
    }

    public function edit_password()
    {
        return view('users.edit_password');
    }

    public function update_password(Request $request)
    {
        $user = Auth::user();

        if ($request->input('password') == $request->input('password_confirmation')) {
            $user->password = bcrypt($request->input('password'));
            $user->update();
        } else {
            return redirect()->route('mypage.edit_password');
        }

        return redirect()->route('mypage');
    }

    public function favorite()
    {
        $user = Auth::user();

        $favorites = $user->favorites()->get();

        return view('users.favorite', compact('favorites'));
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        
        if ($user->deleted_flag) {
            $user->deleted_flag = false;
        } else {
            $user->deleted_flag = true;
        }

        $user->update();

        Auth::logout();

        return redirect('/');
    }
 
    public function register_card(Request $request)
    {
        $user = Auth::user();
 
        $pay_jp_secret = env('PAYJP_SECRET_KEY');
        \Payjp\Payjp::setApiKey($pay_jp_secret);
 
        $card = [];
        $count = 0;
 
        if ($user->token != "") {
            $result = \Payjp\Customer::retrieve($user->token)->cards->all(array("limit"=>1))->data[0];
            $count = \Payjp\Customer::retrieve($user->token)->cards->all()->count;
 
            $card = [
                'brand' => $result["brand"],
                'exp_month' => $result["exp_month"],
                'exp_year' => $result["exp_year"],
                'last4' => $result["last4"] 
            ];
        }
 
        return view('users.register_card', compact('card', 'count'));
    }
 
    public function token(Request $request)
    {
        $pay_jp_secret = env('PAYJP_SECRET_KEY');
        \Payjp\Payjp::setApiKey($pay_jp_secret);
 
        $user = Auth::user();
        $customer = $user->token;
 
        if ($customer != "") {
            $cu = \Payjp\Customer::retrieve($customer);
            $delete_card = $cu->cards->retrieve($cu->cards->data[0]["id"]);
            $delete_card->delete();
            $cu->cards->create(array(
                "card" => request('payjp-token')
            ));
        } else {
            $cu = \Payjp\Customer::create(array(
                "card" => request('payjp-token')
            ));
            $user->token = $cu->id;
            $user->update();
        }
 
        return redirect()->route('mypage');
    }
}
