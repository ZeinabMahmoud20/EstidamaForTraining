<?php

namespace App\Services;

use App\Models\Trainer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a trainer with specialties, certifications and work experiences.
 */
class TrainerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Trainer
    {
        return DB::transaction(function () use ($data) {
            [$attributes, $specialties, $certifications, $experiences] = $this->extract($data);

            $trainer = Trainer::create($attributes);

            $this->syncSpecialties($trainer, $specialties);
            $this->replaceCertifications($trainer, $certifications);
            $this->replaceExperiences($trainer, $experiences);

            return $trainer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Trainer $trainer, array $data): Trainer
    {
        return DB::transaction(function () use ($trainer, $data) {
            [$attributes, $specialties, $certifications, $experiences] = $this->extract($data);

            $trainer->update($attributes);

            $this->syncSpecialties($trainer, $specialties);
            $this->replaceCertifications($trainer, $certifications);
            $this->replaceExperiences($trainer, $experiences);

            return $trainer;
        });
    }

    public function delete(Trainer $trainer): void
    {
        if ($blockers = $this->deletionBlockers($trainer)) {
            throw ValidationException::withMessages([
                'trainer' => 'لا يمكن حذف المدرب لارتباطه بـ: '.implode('، ', $blockers).'.',
            ]);
        }

        DB::transaction(function () use ($trainer) {
            $trainer->trainerCertifications()->delete();
            $trainer->trainerExperiences()->delete();
            $trainer->trainerCourseApprovals()->delete();
            $trainer->courseCategories()->detach();
            $trainer->delete();
        });
    }

    /**
     * Reasons preventing deletion (empty array means the trainer can be deleted).
     *
     * @return list<string>
     */
    public function deletionBlockers(Trainer $trainer): array
    {
        $blockers = [];

        if ($trainer->user()->exists()) {
            $blockers[] = 'حساب مستخدم مرتبط';
        }
        if ($trainer->sessionTrainers()->exists()) {
            $blockers[] = 'جلسات مخططة';
        }
        if ($trainer->sessionDayTrainersAsTrainer()->exists()) {
            $blockers[] = 'أيام تدريب فعلية';
        }
        if ($trainer->sessionDayTrainersAsReplacedTrainer()->exists()) {
            $blockers[] = 'استبدال مدرب في أيام تدريب';
        }
        if ($trainer->sessionDayModules()->exists()) {
            $blockers[] = 'وحدات جلسات مرتبطة به';
        }
        if ($trainer->trainingKits()->exists()) {
            $blockers[] = 'حقائب تدريبية';
        }
        if ($trainer->trainerAttendance()->exists()) {
            $blockers[] = 'سجلات حضور';
        }
        if ($trainer->evaluationParticipationAsTrainer()->exists()
            || $trainer->evaluationParticipationAsTargetTrainer()->exists()
            || $trainer->evaluationSubmissions()->exists()) {
            $blockers[] = 'تقييمات';
        }
        if ($trainer->trainerPayments()->exists()) {
            $blockers[] = 'مستحقات مالية';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, int>, 2: array<int, array<string, mixed>>, 3: array<int, array<string, mixed>>}
     */
    private function extract(array $data): array
    {
        $specialties = Arr::pull($data, 'specialties', []);
        $certifications = Arr::pull($data, 'certifications', []);
        $experiences = Arr::pull($data, 'experiences', []);

        if (($data['is_active'] ?? null) === null) {
            $data['is_active'] = true;
        }

        return [
            $data,
            array_values(array_unique(array_map('intval', array_filter((array) $specialties, fn ($id) => filled($id))))),
            array_values(array_filter((array) $certifications, 'is_array')),
            array_values(array_filter((array) $experiences, 'is_array')),
        ];
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function syncSpecialties(Trainer $trainer, array $ids): void
    {
        $trainer->courseCategories()->sync($ids);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function replaceCertifications(Trainer $trainer, array $rows): void
    {
        $trainer->trainerCertifications()->delete();

        foreach ($rows as $row) {
            $trainer->trainerCertifications()->create([
                'title' => $row['title'],
                'issuer' => $row['issuer'] ?? null,
                'issue_date' => $row['issue_date'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function replaceExperiences(Trainer $trainer, array $rows): void
    {
        $trainer->trainerExperiences()->delete();

        foreach ($rows as $row) {
            $trainer->trainerExperiences()->create([
                'organization' => $row['organization'] ?? null,
                'position' => $row['position'] ?? null,
                'from_date' => $row['from_date'] ?? null,
                'to_date' => $row['to_date'] ?? null,
                'description' => $row['description'] ?? null,
            ]);
        }
    }
}
