<?php

namespace App\Models;

use Database\Factories\InternGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class InternGroup extends Model
{
    /** @use HasFactory<InternGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'institution',
        'division',
        'field_supervisor',
        'supervisor_phone',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $group): void {
            if ($group->wasRecentlyCreated || ! $group->wasChanged($group->sharedProfileColumns())) {
                return;
            }

            $group->internProfiles()->update($group->profileAttributes());
        });
    }

    /**
     * @return array<int, string>
     */
    public function sharedProfileColumns(): array
    {
        return [
            'institution',
            'field_supervisor',
            'supervisor_phone',
            'start_date',
            'end_date',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return [
            'institution' => $this->institution,
            'field_supervisor' => $this->field_supervisor,
            'supervisor_phone' => $this->supervisor_phone,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }

    public function internProfiles(): HasMany
    {
        return $this->hasMany(InternProfile::class);
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, InternProfile::class, 'intern_group_id', 'id', 'id', 'user_id');
    }

    public function periodLabel(): string
    {
        return sprintf(
            '%s s.d. %s',
            $this->start_date?->translatedFormat('d M Y') ?? '-',
            $this->end_date?->translatedFormat('d M Y') ?? '-',
        );
    }
}
