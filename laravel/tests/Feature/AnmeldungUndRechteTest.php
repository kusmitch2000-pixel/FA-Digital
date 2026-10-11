<?php

namespace Tests\Feature;

use App\Livewire\AuftragDetail;
use App\Livewire\Benutzerverwaltung;
use App\Models\Fertigungsauftrag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnmeldungUndRechteTest extends TestCase
{
    use RefreshDatabase;

    /** Passwort aller Test-Benutzer aus datenbank/02_testdaten.sql (nur Entwicklung) */
    private const TESTPASSWORT = 'fa-digital';

    public function test_gaeste_muessen_sich_anmelden(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/halle')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Anmelden');
    }

    public static function startseiten(): array
    {
        return [
            'Werker' => ['jonas.wolff@fa-digital.de', '/werker'],
            'Meister' => ['lena.hoffmann@fa-digital.de', '/halle'],
            'Arbeitsvorbereitung' => ['anna.becker@fa-digital.de', '/auftraege'],
            'Controlling' => ['tobias.krueger@fa-digital.de', '/nachkalkulation'],
            'Admin' => ['sven.petersen@fa-digital.de', '/halle'],
        ];
    }

    #[DataProvider('startseiten')]
    public function test_anmeldung_fuehrt_zur_startseite_der_rolle(string $email, string $startseite): void
    {
        $this->post('/login', ['email' => $email, 'password' => self::TESTPASSWORT])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->get('/')->assertRedirect($startseite);
        $this->get($startseite)->assertOk();
    }

    public function test_falsches_passwort_und_gesperrtes_konto_kommen_nicht_hinein(): void
    {
        $this->post('/login', ['email' => 'jonas.wolff@fa-digital.de', 'password' => 'falsch'])
            ->assertSessionHasErrors(['email' => 'E-Mail oder Passwort stimmen nicht, oder das Konto ist gesperrt.']);

        // Katrin Wagner ist in den Testdaten gesperrt (aktiv = 0)
        $this->post('/login', ['email' => 'katrin.wagner@fa-digital.de', 'password' => self::TESTPASSWORT])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public static function rollen(): array
    {
        return [
            'Werker' => ['1042'],
            'Meister' => ['1002'],
            'Arbeitsvorbereitung' => ['1001'],
            'Controlling' => ['1010'],
            'Admin' => ['1000'],
        ];
    }

    /** Jede Seite aus dem Menü öffnet sich genau für die Rollen aus config/fa.php */
    #[DataProvider('rollen')]
    public function test_jede_rolle_sieht_nur_ihre_seiten(string $personalnummer): void
    {
        $benutzer = $this->alsBenutzer($personalnummer);

        foreach (array_keys(config('fa.menue')) as $seite) {
            $antwort = $this->get(route($seite));
            $benutzer->darf($seite) ? $antwort->assertOk() : $antwort->assertForbidden();
        }
    }

    public function test_beispiele_fuer_die_rechte(): void
    {
        $this->alsBenutzer('1042');
        $this->get('/werker')->assertOk()->assertSee('Werker-Terminal')->assertDontSee('Hallenansicht');
        $this->get('/halle')->assertForbidden();
        $this->get('/auftraege/FA-1002')->assertOk()->assertDontSee('Buchung nachtragen');
        $this->get('/auswertung/auftraege.csv')->assertForbidden();

        $this->alsBenutzer('1002');
        $this->get('/auftraege/FA-1002')->assertOk()->assertSee('Buchung nachtragen');
        $this->get('/benutzer')->assertForbidden();
    }

    /** Klicks laufen über /livewire/update. Auch dort prüft die Seite das Recht. */
    public function test_aktionen_sind_auch_ohne_menuepunkt_geschuetzt(): void
    {
        $this->alsBenutzer('1042');
        Livewire::test(Benutzerverwaltung::class)->assertForbidden();

        $this->alsBenutzer('1001');
        Livewire::test(AuftragDetail::class, ['auftrag' => Fertigungsauftrag::where('auftragsnummer', 'FA-1002')->first()])
            ->call('buchungSpeichern')
            ->assertForbidden();
    }
}
