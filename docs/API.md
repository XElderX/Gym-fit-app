# GYM Fit API v1

Base path: `/api/v1`. JSON is used for request and response bodies. Web authentication uses Sanctum's CSRF cookie + session cookie. Protected endpoints return `401` when the session is missing/expired. Laravel validation errors use HTTP `422`:

```json
{
  "message": "Validation failed.",
  "errors": { "workout_date": ["The workout date field is required."] }
}
```

## Authentication

### Register
`POST /auth/register`

```json
{
  "name": "Alex",
  "email": "alex@example.test",
  "password": "long-password",
  "password_confirmation": "long-password",
  "timezone": "Europe/Vilnius"
}
```

### Login
`POST /auth/login`

```json
{ "email": "alex@example.test", "password": "long-password" }
```

Other endpoints: `GET /auth/me`, `POST /auth/logout`, `PATCH /settings`.

## Workouts

- `GET /workouts?from=2026-09-01&to=2026-09-30&exercise_id=3&page=1`
- `GET /workouts/active`
- `POST /workouts`
- `GET /workouts/{id}`
- `PUT /workouts/{id}`
- `DELETE /workouts/{id}`
- `POST /workouts/{id}/copy`

Create / autosave example:

```json
{
  "client_uuid": "820ec9f2-cdde-43cf-b608-2dd971a6ab40",
  "workout_date": "2026-09-15",
  "status": "active",
  "started_at": "2026-09-15T17:10:00+03:00",
  "ended_at": null,
  "duration_seconds": null,
  "notes": "",
  "mood": null,
  "exercises": [
    {
      "exercise_id": 1,
      "position": 0,
      "feeling_rating": 4,
      "feeling_notes": "Shoulder felt good",
      "sets": [
        {
          "client_uuid": "9cdaaf81-e6ca-4df5-b890-19d24cf61d80",
          "position": 0,
          "set_type": "warmup",
          "weight_kg": 20,
          "bodyweight_extra_kg": null,
          "reps": 10,
          "completed": true,
          "rpe": 3
        },
        {
          "client_uuid": "dfab489e-59d8-4f5c-b570-214251c29aa3",
          "position": 1,
          "set_type": "working",
          "weight_kg": 82.5,
          "bodyweight_extra_kg": null,
          "reps": 6,
          "completed": true,
          "rpe": 8
        }
      ]
    }
  ]
}
```

`client_uuid` is the idempotency identity. Retrying the same payload updates those records. A workout response contains its exercises, exercise metadata, and sets.

## Exercises

- `GET /exercises?search=press&muscle_group_id=1&per_page=50`
- `POST /exercises` creates a custom exercise for the authenticated user.
- `GET /exercises/{id}`
- `PUT /exercises/{id}` / `DELETE /exercises/{id}` for user-owned custom exercises.
- `GET /exercises/{id}/previous` returns the most recent performed sets for fast logging.
- `GET /exercises/{id}/history` returns paginated completed set history.

Custom exercise example:

```json
{
  "name": "Cable Y Raise",
  "primary_muscle_group_id": 3,
  "secondary_muscle_group_ids": [2],
  "equipment": "Cable",
  "tracking_type": "weighted_reps",
  "instructions": "Raise in the scapular plane.",
  "personal_notes": "Use the low pulley."
}
```

Tracking types: `weighted_reps`, `bodyweight_reps`, `weighted_bodyweight_reps`.

## Statistics

`GET /statistics?preset=week|month|last10|last30`

Custom range:

`GET /statistics?preset=custom&from=2026-09-01&to=2026-09-15`

Response shape:

```json
{
  "data": {
    "from": "2026-09-01",
    "to": "2026-09-15",
    "workouts": 6,
    "training_days": 6,
    "primary_muscle_sets": [{"id":1,"name":"Chest","sets":18}],
    "secondary_muscle_sets": [{"id":5,"name":"Triceps","sets":14}],
    "exercises": [{"id":1,"name":"Bench Press","sets":9,"reps":52,"volume_kg":"4210.00"}],
    "feelings": [{"workout_date":"2026-09-15","feeling":"4.50"}],
    "moods": [{"id":42,"workout_date":"2026-09-15","mood":4}],
    "counting_method": "Completed working sets only..."
  }
}
```

Exercise progress/PRs: `GET /statistics/exercises/{exercise}?preset=last30`. It returns per-set date/weight/reps/volume/RPE points, heaviest completed weight, and maximum reps grouped by weight.

## Templates

- `GET /templates`
- `POST /templates`
- `GET /templates/{id}`
- `PUT /templates/{id}`
- `DELETE /templates/{id}`
- `POST /templates/{id}/start`

Template payload:

```json
{
  "name": "Push",
  "notes": "Chest / shoulders / triceps",
  "exercises": [
    {
      "exercise_id": 1,
      "position": 0,
      "sets": [
        {"position":0,"set_type":"warmup","planned_weight_kg":20,"planned_reps":10},
        {"position":1,"set_type":"working","planned_weight_kg":80,"planned_reps":6}
      ]
    }
  ]
}
```

Starting from a template copies planned data into new performed-set rows. Subsequent workout edits do not modify the template.
