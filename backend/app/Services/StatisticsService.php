<?php
namespace App\Services;
use App\Models\{Workout,WorkoutSet};
use Carbon\CarbonImmutable;

class StatisticsService {
    public function range(string $preset, ?string $from, ?string $to, string $timezone): array {
        $today=CarbonImmutable::now($timezone)->startOfDay();
        return match($preset){
            'week'=>[$today->startOfWeek(CarbonImmutable::MONDAY),$today],
            'month'=>[$today->startOfMonth(),$today],
            'last10'=>[$today->subDays(9),$today],
            'last30'=>[$today->subDays(29),$today],
            'custom'=>[CarbonImmutable::parse($from,$timezone)->startOfDay(),CarbonImmutable::parse($to,$timezone)->startOfDay()],
            default=>[$today->subDays(29),$today]
        };
    }
    public function summary(int $userId, CarbonImmutable $from, CarbonImmutable $to): array {
        $base=WorkoutSet::query()->join('workout_exercises as we','we.id','=','workout_sets.workout_exercise_id')->join('workouts as w','w.id','=','we.workout_id')->join('exercises as e','e.id','=','we.exercise_id')->where('w.user_id',$userId)->whereBetween('w.workout_date',[$from->toDateString(),$to->toDateString()]);
        $workouts=Workout::where('user_id',$userId)->whereBetween('workout_date',[$from->toDateString(),$to->toDateString()])->selectRaw('COUNT(*) workouts, COUNT(DISTINCT workout_date) training_days')->first();
        $working=(clone $base)->where('workout_sets.completed',true)->where('workout_sets.set_type','working');
        $muscles=(clone $working)->join('muscle_groups as mg','mg.id','=','e.primary_muscle_group_id')->selectRaw('mg.id, mg.name, COUNT(*) as sets')->groupBy('mg.id','mg.name')->orderByDesc('sets')->get();
        $secondary=(clone $working)->join('exercise_secondary_muscle_group as esm','esm.exercise_id','=','e.id')->join('muscle_groups as smg','smg.id','=','esm.muscle_group_id')->selectRaw('smg.id, smg.name, COUNT(*) as sets')->groupBy('smg.id','smg.name')->orderByDesc('sets')->get();
        $exercises=(clone $working)->selectRaw('e.id,e.name,COUNT(*) sets,SUM(COALESCE(workout_sets.reps,0)) reps,SUM(CASE WHEN e.tracking_type = "weighted_reps" AND workout_sets.weight_kg IS NOT NULL THEN workout_sets.weight_kg * workout_sets.reps WHEN e.tracking_type = "weighted_bodyweight_reps" AND workout_sets.bodyweight_extra_kg IS NOT NULL THEN workout_sets.bodyweight_extra_kg * workout_sets.reps ELSE 0 END) volume_kg')->groupBy('e.id','e.name')->orderByDesc('sets')->get();
        $feelings=(clone $base)->whereNotNull('we.feeling_rating')->selectRaw('w.workout_date,AVG(we.feeling_rating) feeling')->groupBy('w.workout_date')->orderBy('w.workout_date')->get();
        $moods=(clone $base)->whereNotNull('w.mood')->selectRaw('w.id,w.workout_date,w.mood')->distinct()->orderBy('w.workout_date')->get();
        return ['from'=>$from->toDateString(),'to'=>$to->toDateString(),'workouts'=>(int)($workouts->workouts??0),'training_days'=>(int)($workouts->training_days??0),'primary_muscle_sets'=>$muscles,'secondary_muscle_sets'=>$secondary,'exercises'=>$exercises,'feelings'=>$feelings,'moods'=>$moods,'counting_method'=>'Completed working sets only. Each set counts once toward the exercise primary muscle group. Secondary involvement is shown separately and is not added to primary totals. Bodyweight-only exercises do not invent kilogram volume.'];
    }
    public function exerciseProgress(int $userId,int $exerciseId,CarbonImmutable $from,CarbonImmutable $to): array {
        $rows=WorkoutSet::query()->join('workout_exercises as we','we.id','=','workout_sets.workout_exercise_id')->join('workouts as w','w.id','=','we.workout_id')->join('exercises as e','e.id','=','we.exercise_id')->where('w.user_id',$userId)->where('we.exercise_id',$exerciseId)->whereBetween('w.workout_date',[$from->toDateString(),$to->toDateString()])->where('workout_sets.completed',true)->where('workout_sets.set_type','working')->orderBy('w.workout_date')->orderBy('workout_sets.position')->get(['w.workout_date','workout_sets.weight_kg','workout_sets.bodyweight_extra_kg','workout_sets.reps','workout_sets.rpe','e.tracking_type']);
        $heaviest=$rows->whereNotNull('weight_kg')->max(fn($r)=>(float)$r->weight_kg);
        $records=[]; foreach($rows->whereNotNull('weight_kg')->groupBy('weight_kg') as $weight=>$sets){$records[]=['weight_kg'=>(float)$weight,'max_reps'=>(int)$sets->max('reps')];}
        return ['points'=>$rows->map(fn($r)=>['date'=>(string)$r->workout_date,'weight_kg'=>$r->weight_kg!==null?(float)$r->weight_kg:null,'extra_kg'=>$r->bodyweight_extra_kg!==null?(float)$r->bodyweight_extra_kg:null,'reps'=>(int)$r->reps,'volume_kg'=>$r->tracking_type==='weighted_reps'&&$r->weight_kg!==null?(float)$r->weight_kg*(int)$r->reps:($r->tracking_type==='weighted_bodyweight_reps'&&$r->bodyweight_extra_kg!==null?(float)$r->bodyweight_extra_kg*(int)$r->reps:null),'rpe'=>$r->rpe!==null?(float)$r->rpe:null]),'records'=>['heaviest_weight_kg'=>$heaviest,'most_reps_by_weight'=>$records]];
    }
}
