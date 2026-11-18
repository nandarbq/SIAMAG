<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HolidaySyncService
{
    /**
     * @return array{year: int, created: int, updated: int, skipped: int, total: int, source: string}
     */
    public function syncYear(int $year, bool $force = false): array
    {
        $payload = $this->fetchYear($year);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($payload, $force, &$created, &$updated, &$skipped): void {
            foreach ($payload['entries'] as $entry) {
                $holiday = Holiday::query()->whereDate('date', $entry['date'])->first()
                    ?? new Holiday(['date' => $entry['date']]);

                if ($holiday->exists && $holiday->source !== Holiday::SOURCE_AUTO && ! $force) {
                    $skipped++;

                    continue;
                }

                $holiday->fill([
                    'name' => $entry['name'],
                    'source' => Holiday::SOURCE_AUTO,
                ]);

                if (! $holiday->isDirty()) {
                    $skipped++;

                    continue;
                }

                $wasExisting = $holiday->exists;
                $holiday->save();

                $wasExisting ? $updated++ : $created++;
            }
        });

        return [
            'year' => $year,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($payload['entries']),
            'source' => $payload['source'],
        ];
    }

    /**
     * @return array{source: string, entries: array<int, array{date: string, name: string}>}
     */
    private function fetchYear(int $year): array
    {
        $primary = $this->fetchPrimary($year);

        if ($primary !== null) {
            return ['source' => 'primary', 'entries' => $primary];
        }

        $fallback = $this->fetchFallback($year);

        if ($fallback !== null) {
            return ['source' => 'fallback', 'entries' => $fallback];
        }

        throw new RuntimeException("Gagal mengambil data hari libur untuk tahun {$year}.");
    }

    /**
     * @return array<int, array{date: string, name: string}>|null
     */
    private function fetchPrimary(int $year): ?array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.holidays.connect_timeout'))
                ->timeout((int) config('services.holidays.timeout'))
                ->retry(2, 0, throw: false)
                ->get((string) config('services.holidays.primary_url'), ['year' => $year]);

            if (! $response->successful()) {
                Log::warning('Holiday primary source returned an unsuccessful response.', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json('data');

            if (! is_array($data)) {
                return null;
            }

            $entries = $this->normalize($data, 'date', 'name', $year);

            return $entries === [] ? null : $entries;
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Holiday primary source failed.', [
                'year' => $year,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<int, array{date: string, name: string}>|null
     */
    private function fetchFallback(int $year): ?array
    {
        $url = sprintf((string) config('services.holidays.fallback_url'), $year);

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.holidays.connect_timeout'))
                ->timeout((int) config('services.holidays.timeout'))
                ->retry(2, 0, throw: false)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Holiday fallback source returned an unsuccessful response.', [
                    'year' => $year,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json('hari');

            if (! is_array($data)) {
                return null;
            }

            $entries = $this->normalize($data, 'tanggal', 'nama', $year);

            return $entries === [] ? null : $entries;
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Holiday fallback source failed.', [
                'year' => $year,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{date: string, name: string}>
     */
    private function normalize(array $items, string $dateKey, string $nameKey, int $year): array
    {
        $entries = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $date = $item[$dateKey] ?? null;
            $name = $item[$nameKey] ?? null;

            if (! is_string($date) || ! is_string($name) || trim($name) === '') {
                continue;
            }

            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);

            if (! $parsed instanceof CarbonImmutable || $parsed->format('Y-m-d') !== $date || $parsed->year !== $year) {
                continue;
            }

            $entries[$date] = [
                'date' => $date,
                'name' => trim($name),
            ];
        }

        ksort($entries);

        return array_values($entries);
    }
}
