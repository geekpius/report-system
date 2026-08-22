# School Management System --- Backend Domain Design

This document defines the core domain models, relationships, pivot
tables, use cases, and Laravel API structure for the School Management
System.

The system is designed around **Laravel as the backend**.

-   **Web (Fortify / session)** authenticates the `User` model.
    Users are **platform admins** who manage **all schools**.
-   **API (Sanctum / bearer token)** authenticates the `Client` model.
    Clients are school-scoped: owner, teacher, or student.

Since school data is scoped to a particular school, **School** is the
main domain boundary. Admins (`users`) sit above that boundary.

------------------------------------------------------------------------

# 1. Core Models (Eloquent)

These are the main tables/entities.

## 1.0 User (platform admin)

Laravel's default `User` model. Used **only for the web app**.

A user is a platform administrator who can manage every school. Users
do not own a school (`schools.owner_id` points at `clients`, not
`users`). Users do not log in through Sanctum API tokens.

### `users`

  Field          Description
  -------------- ----------------------------------------------
  `id`           Primary key
  `name`         Admin name
  `email`        Web login email
  `password`     Hashed password
  `created_at`   Creation timestamp
  `updated_at`   Update timestamp

------------------------------------------------------------------------

## 1.1 Client

The login identity for the **school API**. A client authenticates with
email and password via Sanctum (bearer token), not the web admin
session. The `role` field says how that client acts: owner, teacher, or
student.

A client can:

-   Own a school (`schools.owner_id`) when `role` is owner
-   Be a teacher (`teachers.client_id`) when `role` is teacher
-   Be a student (`students.client_id`) when `role` is student

Teacher and student **data** stay on `teachers` / `students`; the client
is the shared login.

### `clients`

  Field          Description
  -------------- ----------------------------------------------
  `id`           Primary key
  `name`         Client's name
  `email`        Login email
  `password`     Hashed password
  `role`         `owner`, `teacher`, or `student`
  `created_at`   Creation timestamp
  `updated_at`   Update timestamp

------------------------------------------------------------------------

## 1.2 School

### `schools`

  Field          Description
  -------------- ---------------------------
  `id`           Primary key
  `name`         School name
  `address`      School address
  `image_url`    School image/logo URL, nullable
  `phone`        School phone number
  `motto`        School motto, nullable
  `email`        School email, nullable
  `owner_id`     Foreign key to `clients.id`
  `created_at`   Creation timestamp
  `updated_at`   Update timestamp

### Relationships

A school has many:

-   Teachers
-   Students
-   Classes
-   Subjects
-   Aggregates

------------------------------------------------------------------------

## 1.3 Teacher

Even if a teacher is a client, a separate `teachers` table is important
because it stores school-specific teacher information.

### `teachers`

  Field            Description
  ---------------- -------------------------------------
  `id`             Primary key
  `client_id`      Foreign key to `clients.id`
  `school_id`      Foreign key to `schools.id`
  `staff_number`   Teacher/staff identification number
  `phone`          Teacher phone number
  `created_at`     Creation timestamp
  `updated_at`     Update timestamp

------------------------------------------------------------------------

## 1.4 Student

### `students`

  Field                Description
  -------------------- -----------------------------
  `id`                 Primary key
  `client_id`          Foreign key to `clients.id`, nullable
  `school_id`          Foreign key to `schools.id`
  `school_class_id`    Current class
  `first_name`         First name
  `last_name`          Last name
  `gender`             Gender
  `admission_number`   School admission number
  `date_of_birth`      Date of birth
  `created_at`         Creation timestamp
  `updated_at`         Update timestamp

------------------------------------------------------------------------

## 1.5 Class (`SchoolClass`)

Because `class` can conflict with PHP/Laravel terminology, use
`school_classes` as the table name and `SchoolClass` as the model.

### `school_classes`

  Field                Description
  -------------------- ----------------------------------------
  `id`                 Primary key
  `school_id`          Foreign key to `schools.id`
  `name`               e.g. JHS 1A
  `class_teacher_id`   Foreign key to `teachers.id`, nullable
  `created_at`         Creation timestamp
  `updated_at`         Update timestamp

------------------------------------------------------------------------

## 1.6 Subject

### `subjects`

  Field          Description
  -------------- --------------------------------
  `id`           Primary key
  `school_id`    Foreign key to `schools.id`
  `name`         e.g. Mathematics, English, ICT
  `created_at`   Creation timestamp
  `updated_at`   Update timestamp

------------------------------------------------------------------------

# 2. Pivot / Relationship Tables

These tables handle relationships between teachers, classes, subjects,
and students.

Optional subjects and class promotion are modeled in three layers:

1. **`class_subjects`** — subject menu for a class (mandatory vs elective)
2. **`student_class_enrollments`** — time-bounded class history per academic year
3. **`student_subjects`** — subjects a student actually takes during a class stint

Marks, report cards, and positions should only be created for subjects
listed in `student_subjects`, not inferred from `class_subjects` alone.

`students.school_class_id` remains a cached **current class** for fast
reads. Historical class and subject data lives in the enrollment and
student-subject tables.

------------------------------------------------------------------------

## 2.1 Teacher ↔ Class ↔ Subject

A teacher teaches a particular subject in a specific class.

This is a three-way relationship.

### `class_subject_teachers`

  Field               Description
  ------------------- ------------------------------------
  `id`                Primary key
  `school_class_id`   Foreign key to `school_classes.id`
  `subject_id`        Foreign key to `subjects.id`
  `teacher_id`        Foreign key to `teachers.id`
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

This table answers:

> Who teaches Mathematics in JHS 1A?

------------------------------------------------------------------------

## 2.2 Class ↔ Subject

This determines which subjects a class **offers**. Not every offered
subject is taken by every student — see §2.4.

### `class_subjects`

  Field               Description
  ------------------- ------------------------------------
  `id`                Primary key
  `school_class_id`   Foreign key to `school_classes.id`
  `subject_id`        Foreign key to `subjects.id`
  `is_mandatory`      Whether every student in the class must take this subject (default `true`)
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

Unique constraint: `(school_class_id, subject_id)`

**Mandatory vs elective**

- `is_mandatory = true` — core subject; auto-provisioned into
  `student_subjects` when a student is placed in the class
- `is_mandatory = false` — elective/optional; a `student_subjects` row is
  created only when the student (or owner) selects it

Example:

``` text
JHS 1A (class menu)
 ├── Mathematics   (mandatory)
 ├── English       (mandatory)
 ├── Science       (mandatory)
 ├── French        (elective)
 └── ICT           (elective)
```

When John and Mary are both in JHS 1A, they share the mandatory subjects
but may differ on electives:

``` text
John  → Mathematics, English, Science, French
Mary  → Mathematics, English, Science, ICT
```

------------------------------------------------------------------------

## 2.3 Student ↔ Class

A student has one **current** class via `students.school_class_id`. This
is a denormalized snapshot for profile reads and simple queries.

Class **history** and promotion are tracked in `student_class_enrollments`.

### `student_class_enrollments`

  Field                Description
  -------------------- ------------------------------------------
  `id`                 Primary key
  `student_id`         Foreign key to `students.id`
  `school_class_id`    Foreign key to `school_classes.id`
  `academic_year_id`   Foreign key to `academic_years.id`
  `status`             `active`, `promoted`, `transferred`, or `withdrawn`
  `started_at`         When the student entered this class
  `ended_at`           When the stint ended; `null` while active
  `created_at`         Creation timestamp
  `updated_at`         Update timestamp

Unique constraint: `(student_id, academic_year_id)` — one class per student
per academic year (adjust if mid-year transfers are supported later).

**Initial implementation**

Create one active enrollment whenever `students.school_class_id` is set.
Promotion UI can be added later without changing the schema.

**Promotion flow (future)**

``` text
1. Close the active enrollment (status=promoted, ended_at=now)
2. Create a new enrollment for the next class and academic year
3. Update students.school_class_id
4. Mark old student_subjects rows status=completed (do not delete)
5. Auto-create mandatory student_subjects for the new class enrollment
6. Re-select electives from the new class menu as needed
```

Historical report cards query by `student_class_enrollment_id` and term,
not by the student's current `school_class_id`.

------------------------------------------------------------------------

## 2.4 Student ↔ Subject

This is the source of truth for **which subjects a student actually
takes** during a class stint. It is separate from the class subject menu
in `class_subjects`.

### `student_subjects`

  Field                          Description
  ------------------------------ ---------------------------------------------
  `id`                           Primary key
  `student_id`                   Foreign key to `students.id`
  `subject_id`                   Foreign key to `subjects.id`
  `school_class_id`              Class in which the subject is taken (denormalized context)
  `student_class_enrollment_id`  Foreign key to `student_class_enrollments.id`
  `status`                       `active`, `dropped`, or `completed` (default `active`)
  `created_at`                   Creation timestamp
  `updated_at`                   Update timestamp

Unique constraint: `(student_class_enrollment_id, subject_id)`

Before enrollments are implemented, use
`(student_id, school_class_id, subject_id)` as a temporary unique key.

**How rows are created**

1. Owner assigns subjects to the class via `class_subjects`.
2. When a student is placed in a class, create `student_subjects` for all
   mandatory class subjects automatically.
3. Create additional rows only for electives the student selects.
4. No row for an elective means the student does not take that subject.

**Validation rules**

- `subject_id` must exist in `class_subjects` for the student's class
- electives cannot be added unless offered on the class menu
- mandatory subjects cannot be dropped without an admin override (optional policy)

**Promotion**

Do not delete `student_subjects` when a student is promoted. Set
`status=completed` on the old enrollment's rows and create new rows linked
to the new `student_class_enrollment_id`.

------------------------------------------------------------------------

# 3. Marks & Results

## 3.1 Marks

Marks represent a student's score for a subject in a particular class
and term.

### `marks`

  Field                          Description
  ------------------------------ -----------------------------------
  `id`                           Primary key
  `student_id`                   Student
  `subject_id`                   Subject
  `school_class_id`              Class
  `student_class_enrollment_id`  Class stint this mark belongs to
  `academic_year_id`             Academic year (optional but recommended)
  `term`                         Term 1, 2, or 3
  `score`                        Score, e.g. 0--100
  `teacher_id`                   Teacher who entered/owns the mark
  `created_at`                   Creation timestamp
  `updated_at`                   Update timestamp

A mark may only be created when a matching **active** `student_subjects`
row exists for the same enrollment, student, and subject.

Example:

``` text
Student: John Doe
Class: JHS 1A
Subject: Mathematics
Term: 1
Score: 82
Teacher: Mr. Mensah
```

------------------------------------------------------------------------

# 4. Aggregate / Grading System

The aggregate table defines the school's grading rules.

### `aggregates`

  Field          Description
  -------------- ------------------------------
  `id`           Primary key
  `school_id`    Foreign key to `schools.id`
  `min_score`    Minimum score
  `max_score`    Maximum score
  `grade`        Grade/aggregate, e.g. A1, B2
  `remarks`      e.g. Excellent, Very Good
  `created_at`   Creation timestamp
  `updated_at`   Update timestamp

Example:

    Minimum   Maximum Grade   Remarks
  --------- --------- ------- -----------
         80       100 A1      Excellent
         70        79 B2      Very Good
         60        69 B3      Good
         50        59 C4      Credit
         40        49 C5      Pass
         30        39 C6      Pass

### Finding an aggregate

``` php
$aggregate = Aggregate::where('min_score', '<=', $score)
    ->where('max_score', '>=', $score)
    ->first();
```

------------------------------------------------------------------------

# 5. Class Teacher Remarks

A class teacher can provide a general remark for a student for a
particular term.

### `student_remarks`

  Field               Description
  ------------------- --------------------
  `id`                Primary key
  `student_id`        Student
  `school_class_id`   Class
  `term`              Term
  `remark`            Teacher's remark
  `teacher_id`        Class teacher
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

Example:

``` text
Student: John Doe
Class: JHS 1A
Term: 1
Remark: John has shown great improvement this term.
Teacher: Mr. Mensah
```

------------------------------------------------------------------------

# 6. Final Model List

  Model                 Table
  --------------------- --------------------------
  User                  `users`
  School                `schools`
  Teacher               `teachers`
  Student               `students`
  SchoolClass           `school_classes`
  Subject               `subjects`
  ClassSubject          `class_subjects`
  ClassSubjectTeacher   `class_subject_teachers`
  StudentClassEnrollment   `student_class_enrollments`
  StudentSubject        `student_subjects`
  Mark                  `marks`
  Aggregate             `aggregates`
  StudentRemark         `student_remarks`

Recommended additional model for future-proofing:

  Model                    Table
  ------------------------ -----------------------------
  AcademicYear             `academic_years`

------------------------------------------------------------------------

# 7. Key Laravel Relationships

## School

``` php
class School extends Model
{
    public function owner()
    {
        return $this->belongsTo(Client::class, 'owner_id');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function aggregates()
    {
        return $this->hasMany(Aggregate::class);
    }
}
```

------------------------------------------------------------------------

## SchoolClass

``` php
class SchoolClass extends Model
{
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subjects'
        )->withPivot('is_mandatory');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }
}
```

------------------------------------------------------------------------

## Teacher

``` php
class Teacher extends Model
{
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teachings()
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }

    public function classes()
    {
        return $this->hasMany(
            SchoolClass::class,
            'class_teacher_id'
        );
    }
}
```

------------------------------------------------------------------------

## Student

``` php
class Student extends Model
{
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classEnrollments()
    {
        return $this->hasMany(StudentClassEnrollment::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'student_subjects'
        )->withPivot([
            'school_class_id',
            'student_class_enrollment_id',
            'status',
        ]);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    public function remarks()
    {
        return $this->hasMany(StudentRemark::class);
    }
}
```

------------------------------------------------------------------------

## Subject

``` php
class Subject extends Model
{
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function classes()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'class_subjects'
        )->withPivot('is_mandatory');
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'student_subjects'
        )->withPivot([
            'school_class_id',
            'student_class_enrollment_id',
            'status',
        ]);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }
}
```

------------------------------------------------------------------------

## Teacher Assignment

``` php
class ClassSubjectTeacher extends Model
{
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(
            SchoolClass::class,
            'school_class_id'
        );
    }
}
```

------------------------------------------------------------------------

# 8. Core Use Cases / Flows

## Flow 1 --- User creates a school

``` text
User
  ↓
School
```

The authenticated user becomes the school owner/admin.

------------------------------------------------------------------------

## Flow 2 --- Add teacher

``` text
User
  ↓
Teacher
  ↓
School
```

A teacher can be linked to an existing user account or have an account
created as part of the teacher onboarding process.

------------------------------------------------------------------------

## Flow 3 --- Add class

``` text
School
  ↓
SchoolClass
```

Example:

``` text
School
 ├── JHS 1A
 ├── JHS 1B
 ├── JHS 2A
 └── JHS 3A
```

------------------------------------------------------------------------

## Flow 4 --- Assign class teacher

``` text
SchoolClass
      ↓
class_teacher_id
      ↓
Teacher
```

Example:

``` text
JHS 1A → Mr. Mensah
```

------------------------------------------------------------------------

## Flow 5 --- Add subject

``` text
School
  ↓
Subject
```

Example:

``` text
Mathematics
English
Science
ICT
Social Studies
```

------------------------------------------------------------------------

## Flow 6 --- Assign subject to class

``` text
SchoolClass
      ↓
ClassSubject
      ↓
Subject
```

Example:

``` text
JHS 1A
 ├── Mathematics   (mandatory)
 ├── English       (mandatory)
 ├── Science       (mandatory)
 ├── French        (elective)
 └── ICT           (elective)
```

This defines the class subject **menu**, not every student's final subject list.

------------------------------------------------------------------------

## Flow 7 --- Assign teacher to subject in a class

``` text
Teacher
   ↓
ClassSubjectTeacher
   ↑
Subject + SchoolClass
```

Example:

``` text
Mr. Mensah
   ↓
Mathematics
   ↓
JHS 1A
```

------------------------------------------------------------------------

## Flow 8 --- Add students

``` text
School
  ↓
Student
  ↓
SchoolClass
```

Example:

``` text
JHS 1A
 ├── John Doe
 ├── Mary Smith
 └── Peter Mensah
```

------------------------------------------------------------------------

## Flow 9 --- Assign subjects to students

``` text
Student
   ↓
StudentClassEnrollment   (class stint)
   ↓
StudentSubject
   ↓
Subject
```

When a student joins a class:

1. Create or update the active `student_class_enrollment`
2. Auto-provision mandatory subjects from `class_subjects`
3. Add elective rows only for subjects the student chooses

This supports situations where students in the same class take different
subject sets. Marks are entered only for subjects with an active
`student_subjects` row.

------------------------------------------------------------------------

## Flow 10 --- Assign marks

``` text
Teacher
   ↓
Mark
   ↓
Student + Subject + Class + Term
```

Example:

``` text
Teacher: Mr. Mensah
Student: John Doe
Class: JHS 1A
Subject: Mathematics
Term: 1
Score: 82
```

------------------------------------------------------------------------

## Flow 11 --- Calculate grade/aggregate

``` text
Score
  ↓
Aggregate grading rules
  ↓
Grade + Remarks
```

Example:

``` text
82
 ↓
80–100
 ↓
A1
 ↓
Excellent
```

------------------------------------------------------------------------

## Flow 12 --- Class teacher assigns remarks

``` text
Class Teacher
      ↓
Student Remark
      ↓
Student + Class + Term
```

Example:

``` text
"John has shown great improvement this term."
```

------------------------------------------------------------------------

# 9. Important Design Decisions

## 9.1 School-level data isolation

Every school-owned domain table should be traceable to a `school_id`.

At minimum, these should have `school_id` directly:

``` text
schools
teachers
students
school_classes
subjects
aggregates
```

For relationship and transaction tables such as marks and assignments,
the school can be determined through their related entities, although
storing `school_id` directly can be useful for strict multi-tenant
isolation and query performance.

The key requirement is:

> A user must never be able to access or modify another school's data.

This should be enforced through Laravel authorization, policies, scoped
queries, and/or a school context.

------------------------------------------------------------------------

# 9.2 Do not make Teacher and Student only user roles

Use:

``` text
User
  ↓
Teacher
```

for teachers.

Students should generally remain separate domain entities:

``` text
Student
```

because students do not necessarily need application login accounts.

This gives you a clean separation between:

-   Authentication identity (`User`)
-   School staff (`Teacher`)
-   Learner records (`Student`)

------------------------------------------------------------------------

# 9.3 User can also become a teacher

This is an important requirement.

A school owner/admin may also be a teacher.

For example:

``` text
User
 ├── owns School
 └── Teacher profile
       └── teaches subjects
```

Therefore, avoid designing the system so that being an admin prevents a
user from also having a teacher profile.

------------------------------------------------------------------------

# 9.4 UUIDs

UUIDs can be used instead of exposing sequential numeric IDs for
resources such as:

-   Schools
-   Students
-   Teachers
-   Classes
-   Marks

This is optional but useful for public API identifiers.

------------------------------------------------------------------------

# 10. Recommended API Modules

Organize the Laravel API into logical modules:

``` text
/api
    /auth
    /schools
    /teachers
    /students
    /classes
    /subjects
    /marks
    /results
    /aggregates
```

Each module can contain:

-   Controller
-   Service
-   Form Request
-   Policy
-   Resource
-   Model

Example:

``` text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Auth/
│   │       ├── School/
│   │       ├── Teacher/
│   │       ├── Student/
│   │       ├── Class/
│   │       ├── Subject/
│   │       ├── Mark/
│   │       └── Result/
│   │
│   ├── Requests/
│   └── Resources/
│
├── Models/
├── Services/
└── Policies/
```

------------------------------------------------------------------------

# 11. Authentication --- Web User + API Client (Sanctum)

There are **two** authenticatable models.

### Web --- `User` (Fortify session)

Platform admins sign in on the Inertia site. Guard: `web`. Provider
model: `App\Models\User`. Table: `users`.

Admins manage all schools. They do not use Sanctum tokens.

### API --- `Client` (Sanctum bearer token)

School owners, teachers, and students sign in on the API. Guard:
`auth:sanctum`. Model: `App\Models\Client` with `HasApiTokens`. Table:
`clients`.

Sanctum must **not** fall back to the `web` session for API routes, so
an admin cookie cannot authenticate as a client. Use bearer tokens only
for `/api/*`.

Basic API flow:

``` text
API client
   ↓
POST /api/auth/login  (Client email/password)
   ↓
Laravel
   ↓
Validate against `clients`
   ↓
$client->createToken('api')
   ↓
Return plain-text token
   ↓
Authorization: Bearer <TOKEN>
```

Recommended API authentication endpoints:

``` text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

Protected school APIs should require authentication.

------------------------------------------------------------------------

# 12. Authorization

Authentication answers:

> Who is this user?

Authorization answers:

> What is this user allowed to do?

For this system, Laravel Policies and middleware should enforce
permissions.

Examples:

### School Admin

Can:

-   Create classes
-   Add teachers
-   Add students
-   Add subjects
-   Assign class teachers
-   Assign subjects
-   Configure grading
-   View results

### Teacher

Can:

-   View assigned classes
-   View assigned subjects
-   Enter marks for assigned subjects/classes
-   View relevant students

### Class Teacher

Can additionally:

-   View students in their class
-   Enter class teacher remarks
-   View class performance

A teacher should not be able to submit marks for a class/subject they
have not been assigned.

------------------------------------------------------------------------

# 13. Example API Structure

``` text
AUTH
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me

SCHOOLS
GET    /api/schools
POST   /api/schools
GET    /api/schools/{school}
PUT    /api/schools/{school}
DELETE /api/schools/{school}

TEACHERS
GET    /api/teachers
POST   /api/teachers
GET    /api/teachers/{teacher}
PUT    /api/teachers/{teacher}
DELETE /api/teachers/{teacher}

STUDENTS
GET    /api/students
POST   /api/students
GET    /api/students/{student}
PUT    /api/students/{student}
DELETE /api/students/{student}

CLASSES
GET    /api/classes
POST   /api/classes
PUT    /api/classes/{class}
DELETE /api/classes/{class}

SUBJECTS
GET    /api/subjects
POST   /api/subjects
PUT    /api/subjects/{subject}
DELETE /api/subjects/{subject}

MARKS
GET    /api/marks
POST   /api/marks
PUT    /api/marks/{mark}

RESULTS
GET    /api/results/student/{student}
GET    /api/results/class/{class}
GET    /api/results/class/{class}/term/{term}

AGGREGATES
GET    /api/aggregates
POST   /api/aggregates
PUT    /api/aggregates/{aggregate}

REMARKS
POST   /api/students/{student}/remarks
PUT    /api/students/{student}/remarks/{remark}
```

------------------------------------------------------------------------

# 14. Future-Proofing

The initial model can support the following features later:

-   Academic years
-   Terms/semesters
-   Student promotion (via `student_class_enrollments`; close enrollments
    and subject rows instead of deleting them)
-   Optional/elective subjects (via `class_subjects.is_mandatory` and
    explicit `student_subjects` rows)
-   Student transfers
-   Report cards
-   Class positions
-   Subject positions
-   Result publishing
-   Parent accounts
-   Student accounts
-   Attendance
-   Fees
-   Timetables
-   Assignments
-   Notifications
-   School branches
-   Multiple administrators
-   Multiple schools per user

For a production system, **Academic Year** and **Term** should
eventually become proper entities rather than relying only on a `term`
integer.

For example:

``` text
Academic Year
   │
   ├── Term 1
   ├── Term 2
   └── Term 3
```

This will make historical results and student promotion much easier to
manage.

------------------------------------------------------------------------

# 15. High-Level System Architecture

``` text
User
 │
 ├── Authentication (JWT)
 │
 └── School
      │
      ├── Teachers
      │     └── Class/Subject Assignments
      │
      ├── Students
      │     ├── Class
      │     ├── Subjects
      │     ├── Marks
      │     └── Remarks
      │
      ├── Classes
      │     ├── Class Teacher
      │     ├── Subjects
      │     └── Students
      │
      ├── Subjects
      │     ├── Classes
      │     ├── Students
      │     └── Teachers
      │
      └── Aggregates / Grading Rules
```

------------------------------------------------------------------------

# 16. Recommended Development Order

Build the backend in this order:

``` text
1. Laravel project setup
2. Database configuration
3. JWT authentication
4. User model/authentication
5. School
6. School membership/authorization
7. Teacher
8. Class
9. Subject
10. Class ↔ Subject
11. Teacher ↔ Class ↔ Subject
12. Student
13. Student class enrollments (stub: create active enrollment on class assignment)
14. Student ↔ Subject (auto-provision mandatory; assign/remove electives)
15. Academic Year / Terms
16. Marks
17. Aggregate / Grading
18. Student Remarks
19. Results calculation
20. Report cards
21. Student promotion
22. Authorization hardening and testing
```

This order ensures that the foundational relationships are established
before building the result system.

------------------------------------------------------------------------

# 17. Product Perspective

This is essentially a **School Management System (SMS)** designed around
a school as the primary tenant.

The architecture supports:

-   Multi-school data isolation
-   JWT API authentication
-   Role-based authorization
-   Teacher management
-   Student management
-   Class management
-   Subject management
-   Teacher-subject-class assignments
-   Student-subject assignments (mandatory auto-provision + elective selection)
-   Class subject menus (mandatory vs elective)
-   Marks
-   Grading/aggregates
-   Class teacher remarks
-   Academic results
-   Future report cards
-   Student promotion/history (via `student_class_enrollments`)

The design is normalized, scalable, and suitable for implementation with
Laravel Eloquent.

------------------------------------------------------------------------

# 18. Mental Model

At the highest level:

``` text
User
 └── School
      ├── Teachers
      │     └── Teach Subjects in Classes
      │
      ├── Students
      │     ├── Belong to Classes
      │     ├── Take Subjects
      │     ├── Receive Marks
      │     └── Receive Teacher Remarks
      │
      ├── Classes
      │     ├── Have Class Teachers
      │     └── Learn Subjects
      │
      ├── Subjects
      │     ├── Assigned to Classes
      │     ├── Assigned to Students
      │     └── Assigned to Teachers
      │
      └── Aggregates
            └── Define Score → Grade → Remark
```

------------------------------------------------------------------------

# Conclusion

The proposed design provides a strong foundation for the School
Management System.

The most important architectural principles are:

1.  **School is the primary data boundary.**
2.  **User handles authentication; Teacher and Student handle
    school-domain data.**
3.  **JWT handles API authentication.**
4.  **Policies and middleware handle authorization.**
5.  **Pivot tables handle many-to-many and three-way relationships.**
6.  **Marks must always be associated with the student, subject, class,
    teacher, and academic period.**
7.  **Academic year and term should eventually be first-class
    entities.**
8.  **All school data must be isolated so one school cannot access
    another school's records.**

This gives the project a clean foundation for building the Laravel API
and frontend independently.
