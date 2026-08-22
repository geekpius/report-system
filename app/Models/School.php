<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $address
 * @property string|null $image_url
 * @property string $phone
 * @property string|null $motto
 * @property string|null $email
 * @property string $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'address', 'image_url', 'phone', 'motto', 'email', 'owner_id'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Client, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'owner_id');
    }

    /**
     * @return HasMany<Teacher, $this>
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * @return HasMany<SchoolClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * @return HasMany<Subject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * @return HasManyThrough<ClassSubjectTeacher, SchoolClass, $this>
     */
    public function classSubjectTeachers(): HasManyThrough
    {
        return $this->hasManyThrough(
            ClassSubjectTeacher::class,
            SchoolClass::class,
            'school_id',
            'school_class_id',
        );
    }
}
