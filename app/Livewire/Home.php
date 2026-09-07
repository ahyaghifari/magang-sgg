<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Home extends Component
{
    public bool $journalSaved = false;

    #[On('journal-saved')]
    public function onJournalSaved(): void
    {
        // Cukup menandai; render() otomatis menghitung ulang statistik & jurnal terbaru.
        $this->journalSaved = true;
    }

    public function logout()
    {
        auth()->guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $intern = $user->intern;

        $journalsQuery = $intern?->journals();

        return view('livewire.home', [
            'user' => $user,
            'intern' => $intern,
            'recentJournals' => $journalsQuery
                ? (clone $journalsQuery)->latest('date')->take(5)->get()
                : collect(),
            'totalJournals' => $journalsQuery ? (clone $journalsQuery)->count() : 0,
            'journalsThisMonth' => $journalsQuery
                ? (clone $journalsQuery)->whereBetween('date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])->count()
                : 0,
            'lastJournalDate' => $journalsQuery
                ? (clone $journalsQuery)->max('date')
                : null,
        ]);
    }
}
