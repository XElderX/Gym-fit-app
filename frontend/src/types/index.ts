export type Muscle = {
  id: number;
  name: string;
  slug: string;
};

export type Exercise = {
  id: number;
  name: string;
  equipment: string;
  tracking_type: 'weighted_reps' | 'bodyweight_reps' | 'weighted_bodyweight_reps';
  primary_muscle_group: Muscle;
  secondary_muscle_groups: Muscle[];
  personal_notes?: string | null;
  instructions?: string | null;
  is_custom: boolean;
};

export type SetRow = {
  id?: number;
  client_uuid: string;
  position: number;
  set_type: 'warmup' | 'working';
  weight_kg: number | null;
  bodyweight_extra_kg: number | null;
  reps: number | null;
  completed: boolean;
  rpe: number | null;
};

export type WorkoutExercise = {
  id?: number;
  exercise_id: number;
  position: number;
  feeling_rating: number | null;
  feeling_notes: string;
  exercise?: Exercise;
  sets: SetRow[];
};

export type Workout = {
  id?: number;
  client_uuid: string;
  workout_date: string;
  status: 'active' | 'completed';
  started_at: string | null;
  ended_at: string | null;
  duration_seconds: number | null;
  notes: string;
  mood: number | null;
  exercises: WorkoutExercise[];
};

export type User = {
  id: number;
  name: string;
  email: string;
  timezone: string;
};
