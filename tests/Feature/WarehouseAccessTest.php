<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WarehouseAccessTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/'])]
    #[TestWith(['/products'])]
    #[TestWith(['/products/create'])]
    #[TestWith(['/inventory'])]
    #[TestWith(['/movements'])]
    #[TestWith(['/movements/create'])]
    #[TestWith(['/delivery-notes'])]
    public function test_guest_cannot_open_warehouse_pages(string $path): void
    {
        $this->get($path)->assertRedirect(route('access.login'));
    }

    public function test_guest_cannot_open_or_download_ddt(): void
    {
        $note = DeliveryNote::factory()->create();

        $this->get(route('delivery-notes.show', $note))->assertRedirect(route('access.login'));
        $this->get(route('delivery-notes.download', $note))->assertRedirect(route('access.login'));
    }

    #[TestWith(['/products'])]
    #[TestWith(['/movements'])]
    #[TestWith(['/delivery-notes'])]
    public function test_guest_cannot_write_warehouse_data(string $path): void
    {
        $this->post($path, [])->assertRedirect(route('access.login'));

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('delivery_notes', 0);
    }

    public function test_login_page_is_public_and_does_not_show_password(): void
    {
        $this->get(route('access.login'))->assertOk()->assertSee('Entra nel magazzino')->assertDontSee('test-entry-password');
    }

    public function test_correct_password_grants_access_and_returns_to_requested_page(): void
    {
        config(['access.password_hash' => Hash::make('test-entry-password')]);
        $this->get(route('inventory.index'))->assertRedirect(route('access.login'));

        $this->post(route('access.unlock'), ['password' => 'test-entry-password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'))->assertSessionHas('warehouse_access');

        $this->get(route('inventory.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_incorrect_password_does_not_grant_access_or_flash_password(): void
    {
        config(['access.password_hash' => Hash::make('test-entry-password')]);

        $this->post(route('access.unlock'), ['password' => '1234'])
            ->assertSessionHasErrors(['password' => 'Password non corretta. Riprova.'])
            ->assertSessionMissing('warehouse_access')->assertSessionMissing('_old_input.password');

        $this->get(route('dashboard'))->assertRedirect(route('access.login'));
    }

    public function test_entry_stays_locked_if_password_is_not_configured(): void
    {
        config(['access.password_hash' => null]);

        $this->post(route('access.unlock'), ['password' => 'test-entry-password'])->assertSessionHasErrors('password')->assertSessionMissing('warehouse_access');

        $this->get(route('dashboard'))->assertRedirect(route('access.login'));
    }

    public function test_missing_password_is_rejected(): void
    {
        $this->post(route('access.unlock'), [])->assertSessionHasErrors(['password' => 'Inserisci la password per entrare.']);
    }

    public function test_repeated_wrong_passwords_are_rate_limited(): void
    {
        config(['access.password_hash' => Hash::make('test-entry-password')]);
        RateLimiter::hit('warehouse-login:127.0.0.1', 60);
        RateLimiter::hit('warehouse-login:127.0.0.1', 60);
        RateLimiter::hit('warehouse-login:127.0.0.1', 60);
        RateLimiter::hit('warehouse-login:127.0.0.1', 60);
        RateLimiter::hit('warehouse-login:127.0.0.1', 60);

        $this->post(route('access.unlock'), ['password' => 'test-entry-password'])->assertSessionHasErrors('password')->assertSessionMissing('warehouse_access');

        $this->assertStringContainsString('Troppi tentativi.', session('errors')->first('password'));
    }

    public function test_logout_locks_the_warehouse_again(): void
    {
        $hash = Hash::make('test-entry-password');
        config(['access.password_hash' => $hash]);
        $this->withSession(['warehouse_access' => hash('sha256', $hash)]);

        $this->post(route('access.logout'))->assertRedirect(route('access.login'))->assertSessionMissing('warehouse_access');

        $this->get(route('dashboard'))->assertRedirect(route('access.login'));
    }

    public function test_changing_the_password_revokes_existing_sessions(): void
    {
        $oldHash = Hash::make('test-entry-password');
        config(['access.password_hash' => Hash::make('new-password')]);
        $this->withSession(['warehouse_access' => hash('sha256', $oldHash)]);

        $this->get(route('dashboard'))->assertRedirect(route('access.login'))->assertSessionMissing('warehouse_access');
    }
}
