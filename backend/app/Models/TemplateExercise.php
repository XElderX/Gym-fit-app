<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class TemplateExercise extends Model { protected $fillable=['workout_template_id','exercise_id','position']; public function exercise(){return $this->belongsTo(Exercise::class);} public function sets(){return $this->hasMany(TemplateSet::class)->orderBy('position');} }
