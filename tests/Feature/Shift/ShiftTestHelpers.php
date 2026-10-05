<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyFixedSchedule;
use App\Models\Intern;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendanceRecordWriter;
use App\Services\Attendance\FixedScheduleCalculator;
use App\Services\Shift\ScheduleResolver;
use Database\Factories\InternFactory;
use Database\Factories\UnitFactory;
use Illuminate\Support\Carbon;

/** Data & langkah bersama untuk tes jadwal shift. */
trait ShiftTestHelpers
{
    protected Company $company;

    protected Shift $pagi;

    protected Shift $siang;

    protected function setUpShiftWorld(): void
    {
        // "Hari ini" dibuat tetap supaya aturan kunci tanggal bisa dites pasti.
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00'));

        $this->company = Company::factory()->create();

        // Jadwal tetap perusahaan (perilaku lama): Senin–Jumat 08:00–16:00, Sabtu–Minggu libur.
        foreach (range(0, 6) as $dow) {
            CompanyFixedSchedule::create([
                'company_id' => $this->company->id,
                'day_of_week' => $dow,
                'is_off_day' => in_array($dow, [0, 6], true),
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'break_minutes' => 60,
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'checkin_buffer_minutes' => 120,
                'checkout_buffer_minutes' => 240,
            ]);
        }

        $this->pagi = Shift::factory()->create(['company_id' => $this->company->id]); // 08:30–16:30, toleransi 10
        $this->siang = Shift::factory()->siang()->create(['company_id' => $this->company->id]); // 12:00–21:00
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makeIntern(array $attrs = []): Intern
    {
        return InternFactory::new()->create($attrs + [
            'unit_id' => UnitFactory::new()->create(['company_id' => $this->company->id])->id,
        ]);
    }

    protected function makeUser(UserRole $role): User
    {
        return User::factory()->role($role)->create();
    }

    /**
     * Simulasi satu batch sync: tap (jam) untuk intern pada tanggal itu dihitung oleh calculator
     * lalu disimpan writer — jalur yang sama dengan attendance:sync / sync ulang per jam.
     */
    protected function syncTaps(Intern $intern, string $date, array $times): void
    {
        sort($times);
        $calculator = new FixedScheduleCalculator(new ScheduleResolver);
        $writer = new AttendanceRecordWriter($calculator);

        $calc = $calculator->calculate([
            'nip' => $intern->nip,
            'company_id' => $intern->unit?->company_id,
            'date' => $date,
            'check_in' => $times[0] ?? null,
            'check_out' => count($times) > 1 ? end($times) : null,
            'single_scan' => count($times) === 1,
        ]);

        if ($calc) {
            $writer->save($calc);
        }
    }
}
