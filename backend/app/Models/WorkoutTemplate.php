<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class WorkoutTemplate extends Model { protected $fillable=['user_id','name','notes']; public function exercises(){return $this->hasMany(TemplateExercise::class)->orderBy('position');} }
