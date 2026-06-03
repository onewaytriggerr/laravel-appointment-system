# Appointment Scheduling System

A multi-branch appointment booking system built as a Laravel assessment. It has a public booking form for customers, a staff panel where staff can manage their own appointments, and an admin panel with full control of branches, services, users, and all appointments.

## Stack

Framework      | Laravel 13.8
PHP            | 8.3
Admin UI       | Filament 5.x
Database       | SQLite (default) / MySQL
Frontend build | Vite 8 + Tailwind CSS 4

---

## Setup

### 1. Download the entire project source code from the repository
```bash
git clone <repo>
```

### 2. Change your active directory into the project folder
```bash
cd appointment-system
```

### 3. Install all backend PHP dependencies and framework packages
```bash
composer install
```

### 4. Install frontend packages and compile the assets
```bash
npm install && npm run build
```

### 5. Create your private local environment configuration file
```bash
cp .env.example .env
```

### 6. Generate a unique cryptographic application encryption key
```bash
php artisan key:generate
```

The default database is SQLite, so no database server is needed out of the box. If you want MySQL instead, update `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

### 7. Run database migrations and populate tables with default seed data
```bash
php artisan migrate --seed
```

### 8. Start the local PHP development server
```bash
php artisan serve
```

The app will be running at `http://localhost:8000`.

---

## Seeded Accounts

The seeder creates three named accounts you can use right away:

| Role  |       Email       | Password | Branch         |
|-------|-------------------|----------|----------------|
| Admin | admin@example.com | password | —              |
| Staff | staff@example.com | password | KL City Centre |
| Staff | bob@example.com   | password | JB Flagship    |

The seeder also generates 5 additional random staff members, 17 customers, 5 extra services, and a batch of sample appointments. The named accounts above are the reliable ones to be used for login.

**URLs:**
- Public booking form: `http://localhost:8000`
- Admin panel: `http://localhost:8000/admin`
- Staff panel: `http://localhost:8000/staff`

---

## Architecture Overview

Controllers and Filament pages are kept thin - they collect input and return responses. All business logic and workflows are written in `app/Actions/`.

`CreateAppointmentAction` is the entry point for creating an appointment. It takes raw form data, converts the datetime from branch local time to UTC, then delegates to `ValidateAppointmentAction`. If validation passes, it persists the appointment with a `pending` status.

`ValidateAppointmentAction` enforces four rules:
1. The selected staff member must belong to the selected branch.
2. The appointment (start to end, based on service duration) must fall entirely within branch operating hours.
3. The staff member must not have an existing active appointment that overlaps the proposed timeframe.
4. When updating an existing appointment, it excludes that appointment's own ID from the overlap check so a staff member can reschedule within the same slot.

`UpdateAppointmentStatusAction` controls how appointment statuses can transition. Valid transitions are defined on the `AppointmentStatus` enum itself (`allowedTransitions()`) so the rules live with the data rather than scattered across controllers. Cancellations require a reason. Invalid transitions throw a validation exception.

With this approach, the business logic can be tested in isolation, reusable, and easy to find.

---

## Timezone Handling

Each branch stores its own IANA timezone string (e.g. `Asia/Kuala_Lumpur`). All datetimes in the database are UTC.

**Input:** The public booking form and the Filament appointment form both treat the submitted datetime as branch local time. `CreateAppointmentAction` converts it:
```php
$startsAt = Carbon::parse($data['starts_at'], $branch->timezone)->setTimezone('UTC');
```

**Storage:** `starts_at` and `ends_at` are stored as UTC timestamps. The application-level timezone in `config/app.php` is also UTC, so there's no implicit conversion happening at the framework layer.

**Validation:** Operating hours are stored as plain time strings (`09:00:00`, `18:00:00`) without a timezone. When validating, the UTC appointment times are converted back to branch local time first, and then compared against the branch's opening/closing times for that date. This correctly handles edge cases, like an appointment that crosses midnight UTC but is still within local business hours.

**Display:** Filament and the success page convert UTC back to branch local time using `Carbon::setTimezone($branch->timezone)` before rendering. A customer booking at 9:00 AM KL time will see "9:00 AM" on the confirmation page, not "1:00 AM UTC".

One important note: the booking form expects the user to pick a time in the branch's local timezone. There's no timezone selector on the form — if a customer in a different timezone tries to book, they need to do their own conversion. This would be reasonable for a single-region business.

---

## Known Limitations and Tradeoffs

**No email notifications.** When a booking is created or a status changes, nothing is sent to the customer or staff.

**No customer portal.** Customers can book appointments through the public form but have no account, no way to view upcoming bookings, and no self-serve cancellation. A staff member or admin has to handle any changes.

**Unauthenticated public booking.** Anyone can submit the booking form. There's no rate limiting or CAPTCHA on it either. Fine for an internal-facing tool or low-traffic scenario, but would need hardening before going public.

**No payment integration.** Services have a `price` field and it shows in the admin panel, but there's no checkout flow. The field is there for reference only.

**Scheduler dependency for no-shows.** There's a console command (`appointments:mark-no-show`) that auto-marks confirmed appointments as no-show when they are 15 minutes past their start time. This only runs if the Laravel scheduler is running. For local development, `php artisan schedule:work` handles it. For production, you need a crontab entry:
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**SQLite for local, but production needs MySQL.** The seeded factory data and migrations work with both.
---

## Running Tests

The test suite covers the core business logic, HTTP endpoints, console commands, policies, and status enum behaviour.

```bash
php artisan test
```

To run a specific file:
```bash
php artisan test tests/Feature/Actions/ValidateAppointmentActionTest.php
```

Test coverage includes:
- `ValidateAppointmentActionTest` — all four validation rules, including edge cases like exact boundary times and the overlap exclusion for edits
- `CreateAppointmentActionTest` — timezone conversion, successful creation, failure paths
- `UpdateAppointmentStatusActionTest` — valid and invalid status transitions, cancellation reason requirement
- `BookingControllerTest` — form display, successful booking, validation errors
- `MarkNoShowAppointmentsTest` — scheduler command only marks the right appointments
- `AppointmentPolicyTest` — admin sees all, staff only sees their own
- `AppointmentStatusTest` — enum transition logic

The test database uses an in-memory SQLite database via `RefreshDatabase`, so no separate test DB setup is needed.

---

## AI Tool Usage

**Claude Code** was used for recommending Filament plugins (the media library plugin for service images), generating factory and seeder boilerplate, generating boilerplate structure and writing tests.

**Windsurf** was used for autocompletion.

**Claude** was used to format and enhance this README.md.

The AI-generated factory and seeder code was reviewed before use and adjusted to match the actual enum values and model attribute names.
