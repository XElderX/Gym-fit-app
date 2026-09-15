<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class TemplateSet extends Model { protected $fillable=['template_exercise_id','position','set_type','planned_weight_kg','planned_reps']; protected function casts():array{return ['planned_weight_kg'=>'decimal:2'];} }
