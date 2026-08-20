<?php

namespace Tests\Feature;

use App\Jobs\SendDailyReportJob;
use App\Mail\DailyReportMail;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\Crm\DailyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DailyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function daily_report_job_sends_one_mail_per_active_user(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $this->user('tp@test.com', User::ROLE_TELEPROSPECTEUR);
        $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $this->user('superviseur@test.com', User::ROLE_SUPERVISEUR);
        $this->user('team@test.com', DailyReportService::ROLE_TEAM_LEADER);
        $this->user('inactive@test.com', User::ROLE_COMMERCIAL, false);

        $envoyes = (new SendDailyReportJob())->handle(app(DailyReportService::class));

        $this->assertSame(4, $envoyes);
        Mail::assertSent(DailyReportMail::class, 4);
    }

    #[Test]
    public function daily_report_job_can_target_one_user(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $target = $this->user('target@test.com', User::ROLE_COMMERCIAL);
        $this->user('other@test.com', User::ROLE_TELEPROSPECTEUR);

        $envoyes = (new SendDailyReportJob([], $target->id, 'daily-test'))->handle(app(DailyReportService::class));

        $this->assertSame(1, $envoyes);
        Mail::assertSent(DailyReportMail::class, fn (DailyReportMail $mail): bool => $mail->hasTo('target@test.com'));
        Mail::assertNotSent(DailyReportMail::class, fn (DailyReportMail $mail): bool => $mail->hasTo('other@test.com'));
    }

    #[Test]
    public function daily_report_job_does_not_send_the_same_recipient_twice_for_the_same_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $user = $this->user('target@test.com', User::ROLE_COMMERCIAL);
        $job = new SendDailyReportJob([], $user->id, 'daily-test');

        $first = $job->handle(app(DailyReportService::class));
        $second = $job->handle(app(DailyReportService::class));

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        Mail::assertSent(DailyReportMail::class, 1);
    }

    #[Test]
    public function daily_report_job_only_sends_to_relevant_roles(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $this->user('tp@test.com', User::ROLE_TELEPROSPECTEUR);
        $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $this->user('superviseur@test.com', User::ROLE_SUPERVISEUR);
        $this->user('team@test.com', DailyReportService::ROLE_TEAM_LEADER);
        $this->user('operateur@test.com', User::ROLE_OPERATEUR);

        $envoyes = (new SendDailyReportJob())->handle(app(DailyReportService::class));

        $this->assertSame(4, $envoyes);
        Mail::assertSent(DailyReportMail::class, 4);
        Mail::assertNotSent(DailyReportMail::class, fn (DailyReportMail $mail) => $mail->hasTo('operateur@test.com'));
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
