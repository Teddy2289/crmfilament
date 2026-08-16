<?php

namespace Tests\Feature;

use App\Jobs\SendDailyReportJob;
use App\Jobs\SendWeeklyReportJob;
use App\Mail\DailyReportMail;
use App\Mail\WeeklyReportMail;
use App\Models\User;
use App\Services\Crm\DailyReportService;
use App\Services\Crm\WeeklyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminReportingMailTargetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function daily_report_job_sends_to_each_user(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $this->user('tp@test.com', User::ROLE_TELEPROSPECTEUR);
        $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $this->user('superviseur@test.com', User::ROLE_SUPERVISEUR);
        $this->user('team@test.com', DailyReportService::ROLE_TEAM_LEADER);

        $envoyes = (new SendDailyReportJob())->handle(app(DailyReportService::class));

        $this->assertSame(4, $envoyes);
        Mail::assertSent(DailyReportMail::class, 4);
        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('tp@test.com'));
        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('commercial@test.com'));
        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('superviseur@test.com'));
        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('team@test.com'));
        Mail::assertNotSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('admin@ns-conseil.com'));
    }

    #[Test]
    public function weekly_report_job_sends_to_each_user(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $this->user('tp@test.com', User::ROLE_TELEPROSPECTEUR);
        $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $this->user('superviseur@test.com', User::ROLE_SUPERVISEUR);
        $this->user('team@test.com', WeeklyReportService::ROLE_TEAM_LEADER);

        $envoyes = (new SendWeeklyReportJob([
            User::ROLE_TELEPROSPECTEUR,
            User::ROLE_COMMERCIAL,
            User::ROLE_SUPERVISEUR,
            WeeklyReportService::ROLE_TEAM_LEADER,
        ]))->handle(app(WeeklyReportService::class));

        $this->assertSame(4, $envoyes);
        Mail::assertSent(WeeklyReportMail::class, 4);
        Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('tp@test.com'));
        Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('commercial@test.com'));
        Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('superviseur@test.com'));
        Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('team@test.com'));
        Mail::assertNotSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('admin@ns-conseil.com'));
    }

    private function user(string $email, string $role, bool $actif = true): User
    {
        return User::create([
            'nom' => 'Test',
            'prenom' => ucfirst(strtok($email, '@')),
            'email' => $email,
            'password' => bcrypt('password'),
            'role_cache' => $role,
            'actif' => $actif,
        ]);
    }
}
