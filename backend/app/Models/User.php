<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Foundation\Auth\User as Authenticatable; use Illuminate\Notifications\Notifiable; use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable { use HasApiTokens,HasFactory,Notifiable; protected $fillable=['name','email','password','timezone']; protected $hidden=['password','remember_token']; protected function casts(): array{return ['email_verified_at'=>'datetime','password'=>'hashed'];} public function workouts(){return $this->hasMany(Workout::class);} public function templates(){return $this->hasMany(WorkoutTemplate::class);} }
