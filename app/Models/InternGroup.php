<?php

namespace App\Models;

use Database\Factories\InternGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
        'is_individual',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'is_individual' => 'boolean',
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

    public function isCompleted(): bool
    {
        return $this->end_date?->lessThan(today()) ?? false;
    }

    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->whereDate('end_date', '<', today()->toDateString());
    }

    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->whereNull('end_date')
            ->orWhereDate('end_date', '>=', today()->toDateString()));
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
