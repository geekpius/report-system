<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string|null $client_id
 * @property string $school_id
 * @property string|null $school_class_id
 * @property string $first_name
 * @property string $last_name
 * @property Gender $gender
 * @property string $admission_number
 * @property Carbon $date_of_birth
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'client_id',
    'school_id',
    'school_class_id',
    'first_name',
    'last_name',
    'gender',
    'admission_number',
    'date_of_birth',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasUuids;

    /**
     * @return Attribute<string, string>
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => Str::title($value),
            set: fn (string $value): string => Str::squish($value),
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => Str::title($value),
            set: fn (string $value): string => Str::squish($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return HasMany<StudentClassEnrollment, $this>
     */
    public function classEnrollments(): HasMany
    {
        return $this->hasMany(StudentClassEnrollment::class);
    }

    /**
     * @return HasOne<StudentClassEnrollment, $this>
     */
    public function activeClassEnrollment(): HasOne
    {
        return $this->hasOne(StudentClassEnrollment::class)->active();
    }

    /**
     * @return HasMany<StudentSubject, $this>
     */
    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    /**
     * @return HasMany<StudentTermResult, $this>
     */
    public function termResults(): HasMany
    {
        return $this->hasMany(StudentTermResult::class);
    }
}
