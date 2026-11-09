<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $hash = Hash::make('test-password');
        config(['access.password_hash' => $hash]);
        $this->withSession(['warehouse_access' => hash('sha256', $hash)]);
    }

    public function test_archive_shows_upload_form_and_empty_state(): void
    {
        $this->get(route('delivery-notes.index'))
            ->assertOk()->assertSee('Carica un DDT')->assertSee('Un posto per ogni consegna');
    }

    public function test_pdf_is_stored_privately_with_metadata_without_changing_stock(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->createWithContent('DDT produttore.pdf', "%PDF-1.4\nDocumento di prova\n%%EOF");

        $this->post(route('delivery-notes.store'), [
            'document_date' => '2026-11-09', 'producer' => 'Produttore Cheflow',
            'document_number' => 'DDT-123', 'pdf' => $pdf, 'file_path' => 'forged.pdf',
        ])->assertSessionHasNoErrors()->assertRedirect(route('delivery-notes.index'));

        $note = DeliveryNote::query()->sole();
        $this->assertDatabaseHas('delivery_notes', [
            'id' => $note->id, 'producer' => 'Produttore Cheflow', 'document_number' => 'DDT-123',
            'original_name' => 'DDT produttore.pdf', 'file_size' => $pdf->getSize(),
        ]);
        $this->assertSame('2026-11-09', $note->document_date->format('Y-m-d'));
        $this->assertNotSame('forged.pdf', $note->file_path);
        Storage::disk('local')->assertExists($note->file_path);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('variants', 0);
    }

    public function test_same_filename_and_date_can_be_uploaded_without_overwriting_documents(): void
    {
        Storage::fake('local');
        $first = UploadedFile::fake()->createWithContent('DDT.pdf', "%PDF-1.4\nPrimo documento\n%%EOF");
        $second = UploadedFile::fake()->createWithContent('DDT.pdf', "%PDF-1.4\nSecondo documento\n%%EOF");

        $this->post(route('delivery-notes.store'), ['document_date' => '2026-11-09', 'pdf' => $first])->assertSessionHasNoErrors();
        $this->post(route('delivery-notes.store'), ['document_date' => '2026-11-09', 'pdf' => $second])->assertSessionHasNoErrors();

        $notes = DeliveryNote::query()->orderBy('id')->get();
        $this->assertCount(2, $notes);
        $this->assertNotSame($notes[0]->file_path, $notes[1]->file_path);
        Storage::disk('local')->assertExists($notes->pluck('file_path')->all());
        $this->assertSame("%PDF-1.4\nPrimo documento\n%%EOF", Storage::disk('local')->get($notes[0]->file_path));
    }

    public function test_missing_fields_are_rejected_with_italian_messages(): void
    {
        Storage::fake('local');

        $this->post(route('delivery-notes.store'), [])
            ->assertSessionHasErrors(['document_date' => 'Indica la data del DDT.', 'pdf' => 'Seleziona il PDF del DDT da caricare.']);

        $this->assertDatabaseCount('delivery_notes', 0);
        Storage::disk('local')->assertDirectoryEmpty('delivery-notes');
    }

    public function test_text_file_renamed_to_pdf_is_rejected(): void
    {
        Storage::fake('local');
        $fake = UploadedFile::fake()->createWithContent('falso.pdf', 'Questo è un file di testo, non un PDF.');

        $this->post(route('delivery-notes.store'), [
            'document_date' => '2026-11-09',
            'pdf' => new UploadedFile($fake->getPathname(), 'falso.pdf', 'text/plain', null, true),
        ])->assertSessionHasErrors(['pdf' => 'Il DDT deve essere un file PDF.']);

        $this->assertDatabaseCount('delivery_notes', 0);
        Storage::disk('local')->assertDirectoryEmpty('delivery-notes');
    }

    public function test_pdf_larger_than_ten_megabytes_is_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('delivery-notes.store'), [
            'document_date' => '2026-11-09', 'pdf' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors(['pdf' => 'Il PDF non può superare 10 MB.']);

        $this->assertDatabaseCount('delivery_notes', 0);
        Storage::disk('local')->assertDirectoryEmpty('delivery-notes');
    }

    public function test_invalid_date_is_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('delivery-notes.store'), [
            'document_date' => '2026-02-30', 'pdf' => UploadedFile::fake()->create('ddt.pdf', 1, 'application/pdf'),
        ])->assertSessionHasErrors(['document_date' => 'Inserisci una data valida per il DDT.']);

        $this->assertDatabaseCount('delivery_notes', 0);
        Storage::disk('local')->assertDirectoryEmpty('delivery-notes');
    }

    #[TestWith(['delivery-notes.show', 'inline'])]
    #[TestWith(['delivery-notes.download', 'attachment'])]
    public function test_stored_pdf_can_be_opened_and_downloaded(string $route, string $disposition): void
    {
        Storage::fake('local');
        $note = DeliveryNote::factory()->create();
        $content = "%PDF-1.4\nTest documento\n%%EOF";
        Storage::disk('local')->put($note->file_path, $content);

        $response = $this->get(route($route, $note));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertStreamedContent($content);
        $this->assertStringContainsString($disposition.';', $response->headers->get('Content-Disposition'));
        Storage::disk('local')->assertExists($note->file_path);
    }

    #[TestWith(['delivery-notes.show'])]
    #[TestWith(['delivery-notes.download'])]
    public function test_missing_pdf_returns_not_found(string $route): void
    {
        Storage::fake('local');
        $note = DeliveryNote::factory()->create();

        $this->get(route($route, $note))->assertNotFound();

        Storage::disk('local')->assertMissing($note->file_path);
    }

    public function test_archive_filters_by_date_and_producer_and_sorts_by_document_date(): void
    {
        DeliveryNote::factory()->create(['document_date' => '2026-11-08', 'producer' => 'Produttore Alfa', 'document_number' => 'ALFA-08']);
        DeliveryNote::factory()->create(['document_date' => '2026-11-09', 'producer' => 'Produttore Alfa', 'document_number' => 'ALFA-09']);
        DeliveryNote::factory()->create(['document_date' => '2026-11-09', 'producer' => 'Produttore Beta', 'document_number' => 'BETA-09']);

        $this->get(route('delivery-notes.index', ['date' => '2026-11-09', 'q' => 'Alfa']))
            ->assertOk()->assertSee('ALFA-09')->assertDontSee('ALFA-08')->assertDontSee('BETA-09');
        $this->get(route('delivery-notes.index'))->assertOk()->assertSeeInOrder(['ALFA-09', 'ALFA-08']);
    }

    public function test_archive_escapes_document_metadata(): void
    {
        $unsafe = '<script>alert(1)</script>';
        DeliveryNote::factory()->create(['producer' => $unsafe, 'document_number' => $unsafe, 'original_name' => $unsafe.'.pdf']);

        $this->get(route('delivery-notes.index'))->assertOk()
            ->assertSee($unsafe)->assertDontSee($unsafe, false);
    }
}
