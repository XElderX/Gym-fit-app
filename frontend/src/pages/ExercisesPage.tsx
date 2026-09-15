import { FormEvent, useEffect, useMemo, useState } from 'react';
import { api } from '../api/client';
import type { Exercise } from '../types';

export default function ExercisesPage() {
  const [items, setItems] = useState<Exercise[]>([]);
  const [q, setQ] = useState('');
  const [show, setShow] = useState(false);

  const load = () =>
    api.get<any>(`/exercises?per_page=100&search=${encodeURIComponent(q)}`).then(r => {
      setItems(r.data);
    });

  useEffect(() => {
    load();
  }, []);

  const muscleGroups = useMemo(
    () =>
      Array.from(new Map(items.map(item => [item.primary_muscle_group.id, item.primary_muscle_group])).values()).sort((a, b) =>
        a.name.localeCompare(b.name),
      ),
    [items],
  );

  const create = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const f = new FormData(e.currentTarget);

    await api.post('/exercises', {
      name: f.get('name'),
      primary_muscle_group_id: Number(f.get('primary_muscle_group_id')),
      equipment: f.get('equipment'),
      tracking_type: f.get('tracking_type'),
      secondary_muscle_group_ids: [],
      instructions: f.get('instructions') || null,
      personal_notes: f.get('personal_notes') || null,
    });

    setShow(false);
    load();
  };

  return (
    <section>
      <header className="page-head">
        <div>
          <p className="eyebrow">Movement catalog</p>
          <h1>Exercise library</h1>
        </div>
        <button className="primary" onClick={() => setShow(!show)}>
          New exercise
        </button>
      </header>

      {show && (
        <form className="card form-grid" onSubmit={create}>
          <label>
            Name
            <input name="name" required />
          </label>
          <label>
            Primary muscle
            <select name="primary_muscle_group_id" required disabled={!muscleGroups.length}>
              {muscleGroups.map(muscle => (
                <option key={muscle.id} value={muscle.id}>
                  {muscle.name}
                </option>
              ))}
            </select>
          </label>
          <label>
            Equipment
            <input name="equipment" required />
          </label>
          <label>
            Tracking
            <select name="tracking_type">
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
          <button className="primary" disabled={!muscleGroups.length}>
            Create custom exercise
          </button>
        </form>
      )}

      <div className="card filters">
        <input
          placeholder="Search exercises"
          value={q}
          onChange={e => setQ(e.target.value)}
          onKeyDown={e => {
            if (e.key === 'Enter') load();
          }}
        />
        <button className="secondary" onClick={load}>
          Search
        </button>
      </div>

      <div className="exercise-library">
        {items.map(exercise => (
          <div className="card" key={exercise.id}>
            <div>
              <h3>{exercise.name}</h3>
              <small>
                {exercise.primary_muscle_group.name} · {exercise.equipment}
              </small>
            </div>
            <span className="pill">{exercise.tracking_type.replaceAll('_', ' ')}</span>
          </div>
        ))}
      </div>
    </section>
  );
}
