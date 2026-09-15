<?php
namespace Database\Seeders;
use App\Models\{Exercise,MuscleGroup};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseLibrarySeeder extends Seeder {
    public function run(): void {
        $groups=['Chest','Back','Shoulders','Biceps','Triceps','Quadriceps','Hamstrings','Glutes','Calves','Abs'];
        foreach($groups as $name) MuscleGroup::firstOrCreate(['slug'=>Str::slug($name)],['name'=>$name]);
        $id=MuscleGroup::pluck('id','name');
        $rows=[
          ['Bench Press','Chest',['Triceps','Shoulders'],'Barbell','weighted_reps'],['Incline Dumbbell Press','Chest',['Triceps','Shoulders'],'Dumbbells','weighted_reps'],['Push-Up','Chest',['Triceps','Shoulders'],'Bodyweight','bodyweight_reps'],
          ['Pull-Up','Back',['Biceps'],'Pull-up bar','bodyweight_reps'],['Weighted Pull-Up','Back',['Biceps'],'Pull-up bar + belt','weighted_bodyweight_reps'],['Barbell Row','Back',['Biceps'],'Barbell','weighted_reps'],['Lat Pulldown','Back',['Biceps'],'Cable','weighted_reps'],
          ['Overhead Press','Shoulders',['Triceps'],'Barbell','weighted_reps'],['Dumbbell Lateral Raise','Shoulders',[],'Dumbbells','weighted_reps'],['Rear Delt Fly','Shoulders',['Back'],'Cable / dumbbells','weighted_reps'],
          ['Barbell Curl','Biceps',[],'Barbell','weighted_reps'],['Hammer Curl','Biceps',[],'Dumbbells','weighted_reps'],['Triceps Pushdown','Triceps',[],'Cable','weighted_reps'],['Skull Crusher','Triceps',[],'EZ bar','weighted_reps'],
          ['Back Squat','Quadriceps',['Glutes','Hamstrings'],'Barbell','weighted_reps'],['Leg Press','Quadriceps',['Glutes'],'Machine','weighted_reps'],['Leg Extension','Quadriceps',[],'Machine','weighted_reps'],
          ['Romanian Deadlift','Hamstrings',['Glutes','Back'],'Barbell','weighted_reps'],['Leg Curl','Hamstrings',[],'Machine','weighted_reps'],['Hip Thrust','Glutes',['Hamstrings'],'Barbell','weighted_reps'],['Bulgarian Split Squat','Glutes',['Quadriceps'],'Dumbbells','weighted_reps'],
          ['Standing Calf Raise','Calves',[],'Machine','weighted_reps'],['Seated Calf Raise','Calves',[],'Machine','weighted_reps'],['Hanging Leg Raise','Abs',[],'Bodyweight','bodyweight_reps'],['Cable Crunch','Abs',[],'Cable','weighted_reps'],['Plank','Abs',[],'Bodyweight','bodyweight_reps'],
        ];
        foreach($rows as [$name,$primary,$secondary,$equipment,$tracking]){
            $e=Exercise::firstOrCreate(['user_id'=>null,'name'=>$name],['primary_muscle_group_id'=>$id[$primary],'equipment'=>$equipment,'tracking_type'=>$tracking,'is_custom'=>false]);
            $e->secondaryMuscleGroups()->sync(collect($secondary)->map(fn($x)=>$id[$x])->all());
        }
    }
}
