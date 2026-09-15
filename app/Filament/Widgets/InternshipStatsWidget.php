<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\Interns\InternResource;
use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\Journal;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InternshipStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $attendanceToday = AttendanceRecord::query()
            ->whereDate('date', today())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingUsers = User::query()->pending()->count();

        return [
            Stat::make('Peserta Magang', Intern::query()->count())
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('primary')
                ->url(InternResource::getUrl('index'))
                ->extraAttributes(['class' => 'stat-accent-navy']),

            Stat::make('Jurnal Hari Ini', Journal::query()->whereDate('date', today())->count())
                ->description('Total jurnal masuk sepanjang hari ini')
                ->icon(Heroicon::OutlinedBookOpen)
                ->color('success')
                ->url(JournalResource::getUrl('index'))
                ->extraAttributes(['class' => 'stat-accent-green']),

            Stat::make('Presensi Hari Ini', (int) $attendanceToday->sum())
                ->description(
                    ($attendanceToday->get('present', 0)) . ' hadir · '
                    . ($attendanceToday->get('late', 0)) . ' telat · '
                    . ($attendanceToday->get('absent', 0)) . ' absen'
                )
                ->icon(Heroicon::OutlinedFingerPrint)
                ->color('info')
                ->url(AttendanceRecordResource::getUrl('index'))
                ->extraAttributes(['class' => 'stat-accent-sky']),

            Stat::make('Menunggu Persetujuan', $pendingUsers)
                ->description($pendingUsers > 0 ? 'Perlu ditinjau admin' : 'Semua pendaftar sudah disetujui')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->icon(Heroicon::OutlinedClock)
                ->color($pendingUsers > 0 ? 'warning' : 'gray')
                ->url(UserResource::getUrl('index'))
                ->extraAttributes(['class' => 'stat-accent-amber']),
        ];
    }
}
