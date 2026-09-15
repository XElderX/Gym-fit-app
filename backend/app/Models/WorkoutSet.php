<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class WorkoutSet extends Model { protected $fillable=['workout_exercise_id','client_uuid','position','set_type','weight_kg','bodyweight_extra_kg','reps','completed','rpe']; protected function casts():array{return ['weight_kg'=>'decimal:2','bodyweight_extra_kg'=>'decimal:2','rpe'=>'decimal:1','completed'=>'boolean'];} }
