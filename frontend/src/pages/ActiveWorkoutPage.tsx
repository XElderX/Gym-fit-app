import { useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Check, ChevronDown, ChevronUp, Plus, Trash2, X } from 'lucide-react';
import { api } from '../api/client';
import type { Exercise, SetRow, Workout, WorkoutExercise } from '../types';

const uuid = () => crypto.randomUUID();
const today = () => new Date().toLocaleDateString('en-CA');

const emptyWorkout = (): Workout => ({
  client_uuid: uuid(),
  workout_date: today(),
  status: 'active',
  started_at: new Date().toISOString(),
  ended_at: null,
  duration_seconds: null,
  notes: '',
  mood: null,
  exercises: [],
});

export default function ActiveWorkoutPage() {
  const [params] = useSearchParams();
  const editId = params.get('edit');
  const [w, setW] = useState<Workout>(emptyWorkout);
  const [library, setLibrary] = useState<Exercise[]>([]);
  const [query, setQuery] = useState('');
  const [save, setSave] = useState<'saved' | 'saving' | 'error'>('saved');
  const [showPicker, setShowPicker] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [createError, setCreateError] = useState('');
  const ready = useRef(false);

  useEffect(() => {
    Promise.all([
      api.get<any>(editId ? `/workouts/${editId}` : '/workouts/active'),
      api.get<any>('/exercises?per_page=100'),
    ]).then(([a, e]) => {
      const local = localStorage.getItem('gym-fit-draft');
      const draft = local ? JSON.parse(local) : null;
      setW(a.data || (draft?.status === 'active' ? draft : emptyWorkout()));
      setLibrary(e.data);
      ready.current = true;
    });
  }, [editId]);

  useEffect(() => {
    if (!ready.current) return;
    localStorage.setItem('gym-fit-draft', JSON.stringify(w));
    setSave('saving');
    const t = setTimeout(async () => {
      try {
        const r = w.id ? await api.put<any>(`/workouts/${w.id}`, w) : await api.post<any>('/workouts', w);
        setW(cur => ({ ...cur, id: r.data.id, exercises: r.data.exercises }));
        if (r.data.status === 'completed') localStorage.removeItem('gym-fit-draft');
        else localStorage.setItem('gym-fit-draft', JSON.stringify(r.data));
        setSave('saved');
      } catch {
        setSave('error');
      }
    }, 650);
    return () => clearTimeout(t);
  }, [w]);

  const addExercise = async (ex: Exercise) => {
    let prev: any = null;
    try {
      prev = (await api.get<any>(`/exercises/${ex.id}/previous`)).data;
    } catch {}
    const sets: SetRow[] = (prev?.sets?.length ? prev.sets : [{}]).slice(0, 4).map((s: any, i: number) => ({
      client_uuid: uuid(),
      position: i,
      set_type: s.set_type || 'working',
      weight_kg: s.weight_kg ? Number(s.weight_kg) : null,
      bodyweight_extra_kg: s.bodyweight_extra_kg ? Number(s.bodyweight_extra_kg) : null,
      reps: s.reps ?? null,
      completed: false,
      rpe: null,
    }));
    setW(x => ({
      ...x,
      exercises: [
        ...x.exercises,
        {
          exercise_id: ex.id,
          position: x.exercises.length,
          feeling_rating: null,
          feeling_notes: '',
          exercise: ex,
          sets,
        },
      ],
    }));
    setShowPicker(false);
    setShowCreate(false);
    setQuery('');
  };

  const updateEx = (i: number, fn: (x: WorkoutExercise) => WorkoutExercise) =>
    setW(x => ({ ...x, exercises: x.exercises.map((e, j) => (j === i ? fn(e) : e)) }));

  const addSet = (i: number) =>
    updateEx(i, e => {
      const p = e.sets.at(-1);
      return {
        ...e,
        sets: [
          ...e.sets,
          {
            client_uuid: uuid(),
            position: e.sets.length,
            set_type: p?.set_type || 'working',
            weight_kg: p?.weight_kg ?? null,
            bodyweight_extra_kg: p?.bodyweight_extra_kg ?? null,
            reps: p?.reps ?? null,
            completed: false,
            rpe: null,
          },
        ],
      };
    });

  const move = (i: number, d: number) =>
    setW(x => {
      const a = [...x.exercises];
      const j = i + d;
      if (j < 0 || j >= a.length) return x;
      [a[i], a[j]] = [a[j], a[i]];
      return { ...x, exercises: a.map((e, k) => ({ ...e, position: k })) };
    });

  const filtered = useMemo(
    () => library.filter(e => e.name.toLowerCase().includes(query.toLowerCase())),
    [library, query],
  );

  const muscleOptions = useMemo(
    () =>
      Array.from(new Map(library.map(e => [e.primary_muscle_group.id, e.primary_muscle_group])).values()).sort((a, b) =>
        a.name.localeCompare(b.name),
      ),
    [library],
  );

  const createExercise = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setCreateError('');
    const f = new FormData(e.currentTarget);
    try {
      const r = await api.post<{ data: Exercise }>('/exercises', {
        name: f.get('name'),
        primary_muscle_group_id: Number(f.get('primary_muscle_group_id')),
        equipment: f.get('equipment'),
        tracking_type: f.get('tracking_type'),
        secondary_muscle_group_ids: [],
        instructions: f.get('instructions') || null,
        personal_notes: f.get('personal_notes') || null,
      });
      const created = r.data;
      setLibrary(cur => [...cur.filter(x => x.id !== created.id), created].sort((a, b) => a.name.localeCompare(b.name)));
      await addExercise(created);
    } catch (err: any) {
      setCreateError(err.body?.message || err.message || 'Could not create exercise');
    }
  };

  const finish = () =>
    setW(x => ({
      ...x,
      status: 'completed',
      ended_at: new Date().toISOString(),
      duration_seconds: x.started_at ? Math.max(0, Math.floor((Date.now() - new Date(x.started_at).getTime()) / 1000)) : null,
    }));

  return (
    <section>
      <header className="page-head sticky">
        <div>
          <p className="eyebrow">{w.workout_date}</p>
          <h1>Active workout</h1>
        </div>
        <span className={`save ${save}`}>{save === 'saving' ? 'Saving...' : save === 'error' ? 'Save failed' : 'Saved'}</span>
      </header>

      <div className="workout-toolbar">
        <label>
          Date
          <input type="date" value={w.workout_date} onChange={e => setW({ ...w, workout_date: e.target.value })} />
        </label>
        <button className="secondary" onClick={() => setShowPicker(true)}>
          <Plus size={18} /> Add exercise
        </button>
      </div>

      {w.exercises.map((we, i) => (
        <div className="exercise-card" key={we.id || `${we.exercise_id}-${i}`}>
          <div className="exercise-title">
            <div>
              <h2>{we.exercise?.name || library.find(x => x.id === we.exercise_id)?.name || 'Exercise'}</h2>
              <small>{we.exercise?.primary_muscle_group?.name}</small>
            </div>
            <div className="icon-actions">
              <button onClick={() => move(i, -1)} aria-label="Move up">
                <ChevronUp />
              </button>
              <button onClick={() => move(i, 1)} aria-label="Move down">
                <ChevronDown />
              </button>
              <button
                onClick={() =>
                  setW(x => ({ ...x, exercises: x.exercises.filter((_, j) => j !== i).map((e, k) => ({ ...e, position: k })) }))
                }
                aria-label="Delete"
              >
                <Trash2 />
              </button>
            </div>
          </div>

          <div className="set-grid set-head">
            <span>Set</span>
            <span>kg</span>
            <span>Reps</span>
            <span>RPE</span>
            <span>Done</span>
          </div>

          {we.sets.map((s, j) => (
            <div className="set-grid" key={s.client_uuid}>
              <button
                className={`set-type ${s.set_type}`}
                onClick={() =>
                  updateEx(i, e => ({
                    ...e,
                    sets: e.sets.map((x, k) => (k === j ? { ...x, set_type: x.set_type === 'warmup' ? 'working' : 'warmup' } : x)),
                  }))
                }
              >
                {s.set_type === 'warmup' ? 'W' : j + 1}
              </button>
              <input
                inputMode="decimal"
                value={s.weight_kg ?? s.bodyweight_extra_kg ?? ''}
                placeholder={we.exercise?.tracking_type === 'bodyweight_reps' ? 'BW' : '0'}
                disabled={we.exercise?.tracking_type === 'bodyweight_reps'}
                onChange={ev =>
                  updateEx(i, e => ({
                    ...e,
                    sets: e.sets.map((x, k) =>
                      k === j
                        ? {
                            ...x,
                            [we.exercise?.tracking_type === 'weighted_bodyweight_reps' ? 'bodyweight_extra_kg' : 'weight_kg']:
                              ev.target.value === '' ? null : Number(ev.target.value),
                          }
                        : x,
                    ),
                  }))
                }
              />
              <input
                inputMode="numeric"
                value={s.reps ?? ''}
                onChange={ev =>
                  updateEx(i, e => ({
                    ...e,
                    sets: e.sets.map((x, k) => (k === j ? { ...x, reps: ev.target.value === '' ? null : Number(ev.target.value) } : x)),
                  }))
                }
              />
              <input
                inputMode="decimal"
                value={s.rpe ?? ''}
                placeholder="-"
                onChange={ev =>
                  updateEx(i, e => ({
                    ...e,
                    sets: e.sets.map((x, k) => (k === j ? { ...x, rpe: ev.target.value === '' ? null : Number(ev.target.value) } : x)),
                  }))
                }
              />
              <button
                className={`done ${s.completed ? 'active' : ''}`}
                onClick={() => updateEx(i, e => ({ ...e, sets: e.sets.map((x, k) => (k === j ? { ...x, completed: !x.completed } : x)) }))}
              >
                <Check />
              </button>
            </div>
          ))}

          <button className="add-set" onClick={() => addSet(i)}>
            <Plus size={17} /> Add set using previous values
          </button>

          <div className="feel">
            <span>How did it feel?</span>
            <div>
              {[1, 2, 3, 4, 5].map(n => (
                <button className={we.feeling_rating === n ? 'selected' : ''} key={n} onClick={() => updateEx(i, e => ({ ...e, feeling_rating: n }))}>
                  {['Very bad', 'Bad', 'Okay', 'Good', 'Excellent'][n - 1]}
                </button>
              ))}
            </div>
            <textarea placeholder="Exercise notes" value={we.feeling_notes || ''} onChange={ev => updateEx(i, e => ({ ...e, feeling_notes: ev.target.value }))} />
          </div>
        </div>
      ))}

      {!w.exercises.length && <div className="empty card">Add your first exercise. Previous weights and reps will be prefilled when available.</div>}

      <div className="card finish-card">
        <label>
          Workout mood
          <div className="mood-row">
            {[1, 2, 3, 4, 5].map(n => (
              <button className={w.mood === n ? 'selected' : ''} onClick={() => setW({ ...w, mood: n })} key={n}>
                {n}
              </button>
            ))}
          </div>
        </label>
        <label>
          Workout notes
          <textarea value={w.notes || ''} onChange={e => setW({ ...w, notes: e.target.value })} />
        </label>
        <button className="primary big" onClick={finish} disabled={w.status === 'completed'}>
          {w.status === 'completed' ? 'Workout completed' : 'Finish workout'}
        </button>
      </div>

      {showPicker && (
        <div className="modal-backdrop" onClick={() => setShowPicker(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-head">
              <h2>Add exercise</h2>
              <button className="ghost icon-only" onClick={() => setShowPicker(false)} aria-label="Close">
                <X size={20} />
              </button>
            </div>
            <div className="picker-actions">
              <input autoFocus placeholder="Search exercises" value={query} onChange={e => setQuery(e.target.value)} />
              <button className="secondary" onClick={() => setShowCreate(x => !x)}>
                <Plus size={18} /> New
              </button>
            </div>

            {showCreate && (
              <form className="create-exercise" onSubmit={createExercise}>
                <label>
                  Name
                  <input name="name" required placeholder="Exercise name" />
                </label>
                <label>
                  Primary muscle
                  <select name="primary_muscle_group_id" required disabled={!muscleOptions.length}>
                    {muscleOptions.map(m => (
                      <option key={m.id} value={m.id}>
                        {m.name}
                      </option>
                    ))}
                  </select>
                </label>
                <label>
                  Equipment
                  <input name="equipment" required placeholder="Dumbbell, machine, bodyweight" />
                </label>
                <label>
                  Tracking
                  <select name="tracking_type" defaultValue="weighted_reps">
                    <option value="weighted_reps">Weighted reps</option>
                    <option value="bodyweight_reps">Bodyweight reps</option>
                    <option value="weighted_bodyweight_reps">Weighted bodyweight reps</option>
                  </select>
                </label>
                <label>
                  Instructions
                  <textarea name="instructions" />
                </label>
                <label>
                  Personal notes
                  <textarea name="personal_notes" />
                </label>
                {createError && <div className="error">{createError}</div>}
                <button className="primary" disabled={!muscleOptions.length}>
                  Create and add
                </button>
              </form>
            )}

            <div className="picker-list">
              {filtered.map(ex => (
                <button key={ex.id} onClick={() => addExercise(ex)}>
                  <span>
                    <strong>{ex.name}</strong>
                    <small>
                      {ex.primary_muscle_group.name} · {ex.equipment}
                    </small>
                  </span>
                  <Plus />
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
