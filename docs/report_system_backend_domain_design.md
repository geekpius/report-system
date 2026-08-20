# School Management System --- Backend Domain Design

This document defines the core domain models, relationships, pivot
tables, use cases, and Laravel API structure for the School Management
System.

The system is designed around **Laravel as the backend** and **JWT-based
authentication**. Since everything is scoped to a particular school,
**School** is the main domain boundary.

------------------------------------------------------------------------

# 1. Core Models (Eloquent)

These are the main tables/entities.

## 1.1 Client

The login identity for the application. A client authenticates with
email and password. The `role` field says how that client acts:
owner, teacher, or student.

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

This determines which subjects a class learns.

### `class_subjects`

  Field               Description
  ------------------- ------------------------------------
  `id`                Primary key
  `school_class_id`   Foreign key to `school_classes.id`
  `subject_id`        Foreign key to `subjects.id`
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

Example:

``` text
JHS 1A
 ├── Mathematics
 ├── English
 ├── Science
 └── ICT
```

------------------------------------------------------------------------

## 2.3 Student ↔ Class

For the initial implementation, a student belongs to one current class.

This can be represented directly in the `students` table:

``` text
students.school_class_id
```

However, if you want to support class promotion/history later, it is
better to introduce a separate enrollment/history table.

For example:

### `student_class_enrollments`

  Field                Description
  -------------------- --------------------
  `id`                 Primary key
  `student_id`         Student
  `school_class_id`    Class
  `academic_year_id`   Academic year
  `created_at`         Creation timestamp
  `updated_at`         Update timestamp

This approach is recommended for a production school system because
students move from one class to another over time.

------------------------------------------------------------------------

## 2.4 Student ↔ Subject

This determines which subjects a student takes.

### `student_subjects`

  Field               Description
  ------------------- -------------------------------------
  `id`                Primary key
  `student_id`        Foreign key to `students.id`
  `subject_id`        Foreign key to `subjects.id`
  `school_class_id`   Class in which the subject is taken
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

------------------------------------------------------------------------

# 3. Marks & Results

## 3.1 Marks

Marks represent a student's score for a subject in a particular class
and term.

### `marks`

  Field               Description
  ------------------- -----------------------------------
  `id`                Primary key
  `student_id`        Student
  `subject_id`        Subject
  `school_class_id`   Class
  `term`              Term 1, 2, or 3
  `score`             Score, e.g. 0--100
  `teacher_id`        Teacher who entered/owns the mark
  `created_at`        Creation timestamp
  `updated_at`        Update timestamp

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
  StudentSubject        `student_subjects`
  Mark                  `marks`
  Aggregate             `aggregates`
  StudentRemark         `student_remarks`

Recommended additional model for future-proofing:

  Model                    Table
  ------------------------ -----------------------------
  AcademicYear             `academic_years`
  StudentClassEnrollment   `student_class_enrollments`

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
        );
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

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'student_subjects'
        )->withPivot('school_class_id');
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
        );
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'student_subjects'
        )->withPivot('school_class_id');
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
 ├── Mathematics
 ├── English
 ├── Science
 └── ICT
```

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
StudentSubject
   ↓
Subject
```

This allows the system to support situations where not every student
takes exactly the same subjects.

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

# 11. Authentication --- Laravel + JWT

The system should use JWT for API authentication.

Basic flow:

``` text
Frontend
   ↓
POST /api/auth/login
   ↓
Laravel
   ↓
Validate credentials
   ↓
Generate JWT
   ↓
Return token
   ↓
Frontend
```

Subsequent requests should include:

``` http
Authorization: Bearer <JWT_TOKEN>
```

Recommended authentication endpoints:

``` text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/refresh
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
-   Student promotion
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
13. Student ↔ Subject
14. Academic Year / Terms
15. Marks
16. Aggregate / Grading
17. Student Remarks
18. Results calculation
19. Report cards
20. Authorization hardening and testing
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
-   Student-subject assignments
-   Marks
-   Grading/aggregates
-   Class teacher remarks
-   Academic results
-   Future report cards
-   Student promotion/history

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
