<?php

namespace App\Services;

use App\Models\Trainee;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a trainee profile with its training-provider affiliations and
 * prevents deleting trainees that still have business records.
 */
class TraineeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Trainee
    {
        return DB::transaction(function () use ($data) {
            [$attributes, $providers] = $this->extract($data);

            $trainee = Trainee::create($attributes);

            $this->replaceProviders($trainee, $providers);

            return $trainee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Trainee $trainee, array $data): Trainee
    {
        return DB::transaction(function () use ($trainee, $data) {
            [$attributes, $providers] = $this->extract($data);

            $trainee->update($attributes);

            $this->replaceProviders($trainee, $providers);

            return $trainee;
        });
    }

    public function delete(Trainee $trainee): void
    {
        if ($blockers = $this->deletionBlockers($trainee)) {
            throw ValidationException::withMessages([
                'trainee' => 'لا يمكن حذف المتدرب لارتباطه بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        DB::transaction(function () use ($trainee) {
            $trainee->traineeWarnings()->delete();
            $trainee->traineeProviders()->delete();
            $trainee->delete();
        });
    }

    /**
     * Reasons preventing deletion (empty array means the trainee can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(Trainee $trainee): array
    {
        $blockers = [];

        if ($trainee->user()->exists()) {
            $blockers[] = 'حساب مستخدم مرتبط';
        }
        if ($trainee->nominations()->exists()) {
            $blockers[] = 'ترشيحات';
        }
        if ($trainee->enrollments()->exists()) {
            $blockers[] = 'تسجيلات';
        }
        if ($trainee->invoices()->exists()) {
            $blockers[] = 'فواتير';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function extract(array $data): array
    {
        $providers = Arr::pull($data, 'providers', []);

        if (($data['is_active'] ?? null) === null) {
            $data['is_active'] = true;
        }

        return [
            $data,
            array_values(array_filter((array) $providers, 'is_array')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function replaceProviders(Trainee $trainee, array $rows): void
    {
        $trainee->traineeProviders()->delete();

        $primaryAssigned = false;

        foreach ($rows as $row) {
            $isPrimary = (bool) ($row['is_primary'] ?? false) && ! $primaryAssigned;
            $primaryAssigned = $primaryAssigned || $isPrimary;

            $trainee->traineeProviders()->create([
                'provider_id' => $row['provider_id'],
                'job_title_at_provider' => $row['job_title_at_provider'] ?? null,
                'from_date' => $row['from_date'] ?? null,
                'to_date' => $row['to_date'] ?? null,
                'is_primary' => $isPrimary,
            ]);
        }
    }
}
