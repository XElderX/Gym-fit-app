<?php
namespace App\Http\Controllers\Api\V1; use App\Http\Controllers\Controller; use Illuminate\Http\Request;
class SettingsController extends Controller { public function update(Request $r){$d=$r->validate(['name'=>'sometimes|required|string|max:120','timezone'=>'sometimes|required|timezone']);$r->user()->update($d);return ['data'=>$r->user()->fresh()];} }
