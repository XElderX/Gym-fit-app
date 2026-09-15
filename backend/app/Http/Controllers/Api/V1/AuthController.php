<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function register(Request $r){$d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:190|unique:users,email','password'=>'required|string|min:8|confirmed','timezone'=>'nullable|timezone']);$u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>$d['password'],'timezone'=>$d['timezone']??'Europe/Vilnius']);Auth::login($u);$r->session()->regenerate();return response()->json(['data'=>$u],201);}
 public function login(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($d)) return response()->json(['message'=>'Invalid credentials.'],422);$r->session()->regenerate();return ['data'=>$r->user()];}
 public function me(Request $r){return ['data'=>$r->user()];}
 public function logout(Request $r){Auth::guard('web')->logout();$r->session()->invalidate();$r->session()->regenerateToken();return response()->noContent();}
}
