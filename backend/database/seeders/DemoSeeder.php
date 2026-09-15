<?php
namespace Database\Seeders;
use App\Models\User; use Illuminate\Database\Seeder;
class DemoSeeder extends Seeder { public function run(): void { User::firstOrCreate(['email'=>'demo@example.test'],['name'=>'Demo User','password'=>'demo-password','timezone'=>'Europe/Vilnius']); } }
