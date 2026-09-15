<?php
namespace App\Services;
use App\Models\{Exercise,Workout,WorkoutExercise,WorkoutSet};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkoutSyncService {
    public function sync(int $userId, array $data, ?Workout $workout = null): Workout {
        return DB::transaction(function () use ($userId,$data,$workout) {
            if ($workout && $workout->user_id !== $userId) abort(404);
            $workout ??= Workout::firstOrNew(['user_id'=>$userId,'client_uuid'=>$data['client_uuid']]);
            $workout->fill(collect($data)->only(['workout_date','status','started_at','ended_at','duration_seconds','notes','mood'])->all());
            $workout->user_id=$userId; $workout->client_uuid=$data['client_uuid']; $workout->save();
            $keptExercises=[];
            foreach ($data['exercises'] ?? [] as $i=>$exData) {
                $exercise=Exercise::visibleTo($userId)->find($exData['exercise_id']);
                if (!$exercise) throw ValidationException::withMessages(['exercises'=>['Exercise is not available to this user.']]);
                $we = !empty($exData['id']) ? $workout->exercises()->whereKey($exData['id'])->first() : null;
                $we ??= new WorkoutExercise(['workout_id'=>$workout->id]);
                $we->fill(['exercise_id'=>$exercise->id,'position'=>$exData['position'] ?? $i,'feeling_rating'=>$exData['feeling_rating'] ?? null,'feeling_notes'=>$exData['feeling_notes'] ?? null]);
                $we->workout_id=$workout->id; $we->save(); $keptExercises[]=$we->id;
                $keptSets=[];
                foreach ($exData['sets'] ?? [] as $j=>$setData) {
                    $set=$we->sets()->firstOrNew(['client_uuid'=>$setData['client_uuid']]);
                    $set->fill(['position'=>$setData['position'] ?? $j,'set_type'=>$setData['set_type'] ?? 'working','weight_kg'=>$setData['weight_kg'] ?? null,'bodyweight_extra_kg'=>$setData['bodyweight_extra_kg'] ?? null,'reps'=>$setData['reps'] ?? null,'completed'=>$setData['completed'] ?? false,'rpe'=>$setData['rpe'] ?? null]);
                    $set->workout_exercise_id=$we->id; $set->save(); $keptSets[]=$set->id;
                }
                $we->sets()->whereNotIn('id',$keptSets ?: [0])->delete();
            }
            $workout->exercises()->whereNotIn('id',$keptExercises ?: [0])->delete();
            return $workout->fresh($this->relations());
        });
    }
    public function relations(): array { return ['exercises.exercise.primaryMuscleGroup','exercises.exercise.secondaryMuscleGroups','exercises.sets']; }
}
