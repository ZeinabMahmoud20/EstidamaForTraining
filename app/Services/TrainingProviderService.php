<?php

namespace App\Services;

use App\Models\TrainingProvider;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Creates/updates training providers and prevents deleting ones still in use.
 */
class TrainingProviderService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TrainingProvider
    {
        return TrainingProvider::create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TrainingProvider $provider, array $data): TrainingProvider
    {
        $provider->update($this->attributes($data));

        return $provider;
    }

    public function delete(TrainingProvider $provider): void
    {
        if ($blockers = $this->deletionBlockers($provider)) {
            throw ValidationException::withMessages([
                'provider' => 'لا يمكن حذف جهة التدريب لارتباطها بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        $provider->delete();
    }

    /**
     * Reasons preventing deletion (empty array means the provider can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(TrainingProvider $provider): array
    {
        $blockers = [];

        if ($provider->trainers()->exists()) {
            $blockers[] = 'مدربين';
        }
        if ($provider->traineeProviders()->exists()) {
            $blockers[] = 'متدربين مرتبطين';
        }
        if ($provider->courseSessions()->exists()) {
            $blockers[] = 'جلسات مجدولة';
        }
        if ($provider->nominations()->exists()) {
            $blockers[] = 'ترشيحات';
        }
        if ($provider->invoices()->exists()) {
            $blockers[] = 'فواتير';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        Arr::forget($data, ['_token', '_method']);

        if (($data['is_active'] ?? null) === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
