<?php
namespace Tests\Feature;
use App\Models\{Exercise,MuscleGroup,User,Workout};
use Database\Seeders\ExerciseLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FitnessApiTest extends TestCase {
 use RefreshDatabase;
 protected function setUp(): void { parent::setUp(); $this->seed(ExerciseLibrarySeeder::class); }
 private function user($email='a@example.test'){return User::create(['name'=>'Tester','email'=>$email,'password'=>'password123','timezone'=>'Europe/Vilnius']);}
 private function payload(Exercise $e,string $date='2026-09-15'){return ['client_uuid'=>(string)Str::uuid(),'workout_date'=>$date,'status'=>'completed','started_at'=>$date.'T15:00:00Z','ended_at'=>$date.'T16:00:00Z','duration_seconds'=>3600,'mood'=>4,'exercises'=>[['exercise_id'=>$e->id,'position'=>0,'feeling_rating'=>5,'feeling_notes'=>'Strong','sets'=>[['client_uuid'=>(string)Str::uuid(),'position'=>0,'set_type'=>'warmup','weight_kg'=>20,'reps'=>10,'completed'=>true,'rpe'=>3],['client_uuid'=>(string)Str::uuid(),'position'=>1,'set_type'=>'working','weight_kg'=>80.5,'reps'=>5,'completed'=>true,'rpe'=>8],['client_uuid'=>(string)Str::uuid(),'position'=>2,'set_type'=>'working','weight_kg'=>80.5,'reps'=>5,'completed'=>false,'rpe'=>9]]]]];}
 public function test_users_cannot_read_another_users_workout(): void {$a=$this->user();$b=$this->user('b@example.test');$e=Exercise::where('name','Bench Press')->first();$res=$this->actingAs($a)->postJson('/api/v1/workouts',$this->payload($e));$id=$res->json('data.id');$this->actingAs($b)->getJson("/api/v1/workouts/$id")->assertNotFound();}
 public function test_autosave_retry_updates_same_workout_and_sets(): void {$u=$this->user();$e=Exercise::where('name','Bench Press')->first();$p=$this->payload($e);$first=$this->actingAs($u)->postJson('/api/v1/workouts',$p)->assertCreated();$id=$first->json('data.id');$p['notes']='edited';$this->actingAs($u)->putJson("/api/v1/workouts/$id",$p)->assertOk()->assertJsonPath('data.notes','edited');$this->assertDatabaseCount('workouts',1);$this->assertDatabaseCount('workout_sets',3);}
 public function test_date_filter_only_returns_selected_period(): void {$u=$this->user();$e=Exercise::where('name','Bench Press')->first();$this->actingAs($u)->postJson('/api/v1/workouts',$this->payload($e,'2026-09-01'));$this->actingAs($u)->postJson('/api/v1/workouts',$this->payload($e,'2026-09-15'));$this->actingAs($u)->getJson('/api/v1/workouts?from=2026-09-10&to=2026-09-20')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.workout_date','2026-09-15');}
 public function test_muscle_sets_exclude_warmups_and_unfinished_sets(): void {$u=$this->user();$e=Exercise::where('name','Bench Press')->first();$this->actingAs($u)->postJson('/api/v1/workouts',$this->payload($e));$r=$this->actingAs($u)->getJson('/api/v1/statistics?preset=custom&from=2026-09-15&to=2026-09-15')->assertOk();$chest=collect($r->json('data.primary_muscle_sets'))->firstWhere('name','Chest');$this->assertSame(1,(int)$chest['sets']);$ex=collect($r->json('data.exercises'))->firstWhere('name','Bench Press');$this->assertSame(402.5,(float)$ex['volume_kg']);}
}
