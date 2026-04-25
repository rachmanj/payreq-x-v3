<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\User;
use Carbon\Carbon;

class PcbcComplianceService
{
    public function isExemptProject(?string $projectCode): bool
    {
        if ($projectCode === null || $projectCode === '') {
            return true;
        }

        return in_array(
            $projectCode,
            config('pcbc_compliance.exception_project_codes', []),
            true
        );
    }

    public function isSanctioned(User $user): bool
    {
        $status = $this->getStatus($user);

        return $status !== null && $status['sanctioned'];
    }

    public function getStatus(?User $user): ?array
    {
        if (! $user || ! $user->project) {
            return null;
        }

        if ($this->isExemptProject($user->project)) {
            return [
                'exempt' => true,
                'sanctioned' => false,
                'variant' => 'info',
                'title' => 'PCBC upload not required',
                'title_id' => 'Unggah PCBC tidak wajib',
                'message' => 'Your project ('.$user->project.') is exempt from the weekly PCBC upload requirement.',
                'message_id' => 'Proyek Anda ('.$user->project.') dikecualikan dari kewajiban unggah PCBC per minggu.',
                'show_banner' => false,
                'current_week_label' => null,
                'weeks' => null,
            ];
        }

        $tz = config('pcbc_compliance.timezone', 'Asia/Makassar');

        [$w0Start, $w0End] = $this->weekBoundariesForWeeksAgo(0, $tz);
        [$w1Start, $w1End] = $this->weekBoundariesForWeeksAgo(1, $tz);
        [$w2Start, $w2End] = $this->weekBoundariesForWeeksAgo(2, $tz);

        $hasCurrent = $this->hasQualifyingUpload($user->project, $w0Start, $w0End);
        $hasW1 = $this->hasQualifyingUpload($user->project, $w1Start, $w1End);
        $hasW2 = $this->hasQualifyingUpload($user->project, $w2Start, $w2End);

        $sanctioned = ! $hasW1 && ! $hasW2;

        $weeks = [
            'current' => $this->weekDescriptor('this_week', $w0Start, $w0End, $hasCurrent),
            'w1' => $this->weekDescriptor('last_week', $w1Start, $w1End, $hasW1),
            'w2' => $this->weekDescriptor('two_weeks_ago', $w2Start, $w2End, $hasW2),
        ];

        if ($sanctioned) {
            return [
                'exempt' => false,
                'sanctioned' => true,
                'variant' => 'danger',
                'title' => 'PCBC compliance required',
                'title_id' => 'Kepatuhan PCBC diperlukan',
                'message' => 'No validated PCBC report was on file for the last two full weeks (Mon–Sun, '.$tz.'). "Ready to Pay" and "Incoming List" are disabled until there is a qualifying upload. Use a document date in the week you are reporting.',
                'message_id' => 'Tidak ada laporan PCBC tervalidasi untuk dua minggu penuh terakhir (Sen–Min, '.$tz.'). Menu "Ready to Pay" dan "Incoming List" dinonaktifkan hingga ada unggahan yang memenuhi syarat. Gunakan tanggal dokumen pada minggu yang dilaporkan.',
                'show_banner' => true,
                'current_week_label' => $w0Start->translatedFormat('d M Y').' – '.$w0End->translatedFormat('d M Y'),
                'weeks' => $weeks,
            ];
        }

        if (! $hasW1 && $hasW2) {
            return [
                'exempt' => false,
                'sanctioned' => false,
                'variant' => 'warning',
                'title' => 'You missed last week\'s PCBC',
                'title_id' => 'Anda melewatkan PCBC minggu lalu',
                'message' => 'Have a PCBC PDF validated with document date in last week’s range, or the next full miss will block cashier actions.',
                'message_id' => 'Pastikan PDF PCBC divalidasi dengan tanggal dokumen di rentang minggu lalu, atau lewatnya minggu penuh berikutnya akan memblokir aksi kasir.',
                'show_banner' => true,
                'current_week_label' => $w0Start->translatedFormat('d M Y').' – '.$w0End->translatedFormat('d M Y'),
                'weeks' => $weeks,
            ];
        }

        if (! $hasCurrent) {
            return [
                'exempt' => false,
                'sanctioned' => false,
                'variant' => 'warning',
                'title' => 'PCBC for this week',
                'title_id' => 'PCBC untuk minggu ini',
                'message' => 'At least one validated PCBC PDF per week is required. Upload a report, set the document date within this week, and have it validated.',
                'message_id' => 'Wajib minimal satu file PDF PCBC tervalidasi per minggu. Unggah laporan, atur tanggal dokumen di minggu ini, dan selesaikan validasi.',
                'show_banner' => true,
                'current_week_label' => $w0Start->translatedFormat('d M Y').' – '.$w0End->translatedFormat('d M Y'),
                'weeks' => $weeks,
            ];
        }

        return [
            'exempt' => false,
            'sanctioned' => false,
            'variant' => 'success',
            'title' => 'PCBC on track',
            'title_id' => 'PCBC sesuai jadwal',
            'message' => 'This week’s PCBC requirement is met (at least one validated upload with a document date in the current week).',
            'message_id' => 'Kewajiban PCBC minggu ini terpenuhi (minimal satu unggahan tervalidasi dengan tanggal dokumen di minggu berjalan).',
            'show_banner' => false,
            'current_week_label' => $w0Start->translatedFormat('d M Y').' – '.$w0End->translatedFormat('d M Y'),
            'weeks' => $weeks,
        ];
    }

    public function shouldEnforceForUser(User $user): bool
    {
        if (! $user->project) {
            return false;
        }

        if ($this->isExemptProject($user->project)) {
            return false;
        }

        return $user->can('akses_transaksi_cashier');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekBoundariesForWeeksAgo(int $weeksAgo, string $tz): array
    {
        $day = Carbon::now($tz)->subWeeks($weeksAgo);
        $start = $day->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $day->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return [$start, $end];
    }

    private function hasQualifyingUpload(string $project, Carbon $from, Carbon $to): bool
    {
        return Dokumen::query()
            ->where('type', 'pcbc')
            ->where('validation_status', Dokumen::VALIDATION_VALIDATED)
            ->where('project', $project)
            ->whereDate('dokumen_date', '>=', $from->toDateString())
            ->whereDate('dokumen_date', '<=', $to->toDateString())
            ->exists();
    }

    /**
     * @return array{label: string, label_id: string, range: string, has_upload: bool}
     */
    private function weekDescriptor(string $key, Carbon $from, Carbon $to, bool $hasUpload): array
    {
        $pair = $this->weekLabelPair($key);

        return [
            'label' => $pair['en'],
            'label_id' => $pair['id'],
            'range' => $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'),
            'has_upload' => $hasUpload,
        ];
    }

    /**
     * @return array{en: string, id: string}
     */
    private function weekLabelPair(string $key): array
    {
        return match ($key) {
            'this_week' => ['en' => 'This week', 'id' => 'Minggu ini'],
            'last_week' => ['en' => 'Last week', 'id' => 'Minggu lalu'],
            'two_weeks_ago' => ['en' => 'Two weeks ago', 'id' => 'Dua minggu lalu'],
            default => ['en' => $key, 'id' => $key],
        };
    }
}
