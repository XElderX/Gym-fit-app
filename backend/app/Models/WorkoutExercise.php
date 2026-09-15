<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class WorkoutExercise extends Model { protected $fillable=['workout_id','exercise_id','position','feeling_rating','feeling_notes']; public function workout(){return $this->belongsTo(Workout::class);} public function exercise(){return $this->belongsTo(Exercise::class);} public function sets(){return $this->hasMany(WorkoutSet::class)->orderBy('position');} }
