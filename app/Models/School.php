<?php

namespace App\Models;

use App\Enums\SchoolStatus;
use App\Enums\SchoolType;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $name
 * @property string $address
 * @property string $city
 * @property SchoolType $type
 * @property string|null $logo_url
 * @property string|null $stamp_url
 * @property string|null $signature_url
 * @property string $phone
 * @property string|null $motto
 * @property string|null $email
 * @property SchoolStatus $status
 * @property bool $in_session
 * @property string $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'address', 'city', 'type', 'logo_url', 'stamp_url', 'signature_url', 'phone', 'motto', 'email', 'status', 'in_session', 'owner_id'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUuids;

    /**
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => Str::title($value),
            set: fn (string $value): string => Str::squish($value),
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function city(): Attribute
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
            'type' => SchoolType::class,
            'status' => SchoolStatus::class,
            'in_session' => 'boolean',
        ];
    }

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

    /**
     * @return HasMany<AcademicYear, $this>
     */
    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    /**
     * @return HasOne<AcademicYear, $this>
     */
    public function currentAcademicYear(): HasOne
    {
        return $this->hasOne(AcademicYear::class)->where('is_current', true);
    }

    /**
     * @return HasMany<Aggregate, $this>
     */
    public function aggregates(): HasMany
    {
        return $this->hasMany(Aggregate::class);
    }

    /**
     * @return HasOne<MarkSetting, $this>
     */
    public function markSetting(): HasOne
    {
        return $this->hasOne(MarkSetting::class);
    }

    /**
     * @return HasMany<Mark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
