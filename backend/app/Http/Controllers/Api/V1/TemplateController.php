<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\{Exercise,WorkoutTemplate,TemplateExercise,TemplateSet};
use App\Services\WorkoutSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplateController extends Controller {
    public function __construct(private WorkoutSyncService $sync) {}
    public function index(Request $r){return ['data'=>WorkoutTemplate::where('user_id',$r->user()->id)->with('exercises.exercise.primaryMuscleGroup','exercises.sets')->orderBy('name')->get()];}
    public function store(Request $r){return response()->json(['data'=>$this->save($r)],201);}
    public function show(Request $r,WorkoutTemplate $template){$this->own($r,$template);return ['data'=>$template->load('exercises.exercise.primaryMuscleGroup','exercises.sets')];}
    public function update(Request $r,WorkoutTemplate $template){$this->own($r,$template);return ['data'=>$this->save($r,$template)];}
    public function destroy(Request $r,WorkoutTemplate $template){$this->own($r,$template);$template->delete();return response()->noContent();}
    public function start(Request $r,WorkoutTemplate $template){
        $this->own($r,$template);$template->load('exercises.sets');
        $payload=['client_uuid'=>(string)Str::uuid(),'workout_date'=>$r->input('workout_date',now($r->user()->timezone)->toDateString()),'status'=>'active','started_at'=>now(),'exercises'=>$template->exercises->map(fn($te)=>['exercise_id'=>$te->exercise_id,'position'=>$te->position,'sets'=>$te->sets->map(fn($s)=>['client_uuid'=>(string)Str::uuid(),'position'=>$s->position,'set_type'=>$s->set_type,'weight_kg'=>$s->planned_weight_kg,'reps'=>$s->planned_reps,'completed'=>false])->all()])->all()];
        return response()->json(['data'=>$this->sync->sync($r->user()->id,$payload)],201);
    }
    private function save(Request $r,?WorkoutTemplate $template=null){
        $d=$r->validate(['name'=>'required|string|max:120','notes'=>'nullable|string','exercises'=>'array','exercises.*.exercise_id'=>'required|integer|exists:exercises,id','exercises.*.position'=>'required|integer|min:0','exercises.*.sets'=>'array','exercises.*.sets.*.position'=>'required|integer|min:0','exercises.*.sets.*.set_type'=>'required|in:warmup,working','exercises.*.sets.*.planned_weight_kg'=>'nullable|numeric|min:0','exercises.*.sets.*.planned_reps'=>'nullable|integer|min:0']);
        return DB::transaction(function() use($r,$d,$template){$template??=new WorkoutTemplate(['user_id'=>$r->user()->id]);$template->fill(['name'=>$d['name'],'notes'=>$d['notes']??null]);$template->user_id=$r->user()->id;$template->save();$template->exercises()->delete();foreach($d['exercises']??[] as $ex){abort_unless(Exercise::visibleTo($r->user()->id)->whereKey($ex['exercise_id'])->exists(),422);$te=TemplateExercise::create(['workout_template_id'=>$template->id,'exercise_id'=>$ex['exercise_id'],'position'=>$ex['position']]);foreach($ex['sets']??[] as $s)TemplateSet::create($s+['template_exercise_id'=>$te->id]);}return $template->fresh('exercises.exercise.primaryMuscleGroup','exercises.sets');});
    }
    private function own(Request $r,WorkoutTemplate $t){abort_unless($t->user_id===$r->user()->id,404);}
}
