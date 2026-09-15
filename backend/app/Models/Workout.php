<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Workout extends Model { protected $fillable=['user_id','client_uuid','workout_date','status','started_at','ended_at','duration_seconds','notes','mood']; protected function casts():array{return ['workout_date'=>'date:Y-m-d','started_at'=>'datetime','ended_at'=>'datetime'];} public function exercises(){return $this->hasMany(WorkoutExercise::class)->orderBy('position');} }
