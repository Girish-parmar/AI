<?php

namespace Tests\Feature\Monitoring;

use App\Enums\LegalDocumentType;
use App\Enums\Role;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_can_create_a_draft_document(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);

        $response = $this->actingAs($monitoring)->post(route('monitoring.legal-documents.store'), [
            'type' => LegalDocumentType::TermsOfService->value,
            'title' => 'Terms of Service',
            'version' => '1.0',
            'content' => 'These are the terms.',
        ]);

        $response->assertRedirect(route('monitoring.legal-documents.index'));
        $this->assertDatabaseHas('legal_documents', [
            'type' => 'terms_of_service',
            'title' => 'Terms of Service',
            'created_by' => $monitoring->id,
            'published_at' => null,
        ]);
    }

    public function test_monitoring_can_publish_and_unpublish_a_document(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->create();

        $this->actingAs($monitoring)->post(route('monitoring.legal-documents.publish', $document));
        $this->assertNotNull($document->fresh()->published_at);

        $this->actingAs($monitoring)->post(route('monitoring.legal-documents.unpublish', $document));
        $this->assertNull($document->fresh()->published_at);
    }

    public function test_a_document_cannot_be_published_twice(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->published()->create();

        $this->actingAs($monitoring)->post(route('monitoring.legal-documents.publish', $document))->assertStatus(422);
    }

    public function test_a_draft_document_cannot_be_unpublished(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->create();

        $this->actingAs($monitoring)->post(route('monitoring.legal-documents.unpublish', $document))->assertStatus(422);
    }

    public function test_a_published_document_cannot_be_deleted(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->published()->create();

        $this->actingAs($monitoring)->delete(route('monitoring.legal-documents.destroy', $document))->assertStatus(422);
        $this->assertModelExists($document);
    }

    public function test_a_draft_document_can_be_deleted(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->create();

        $this->actingAs($monitoring)->delete(route('monitoring.legal-documents.destroy', $document))
            ->assertRedirect(route('monitoring.legal-documents.index'));
        $this->assertModelMissing($document);
    }

    public function test_a_documents_type_cannot_be_changed_via_update(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $document = LegalDocument::factory()->create(['type' => LegalDocumentType::CookiePolicy]);

        $this->actingAs($monitoring)->put(route('monitoring.legal-documents.update', $document), [
            'type' => LegalDocumentType::TermsOfService->value,
            'title' => 'Renamed',
            'version' => '2.0',
            'content' => 'Changed content.',
        ]);

        $fresh = $document->fresh();
        $this->assertSame(LegalDocumentType::CookiePolicy, $fresh->type);
        $this->assertSame('Renamed', $fresh->title);
        $this->assertSame('2.0', $fresh->version);
    }

    public function test_superadmin_shares_full_access_via_the_monitoring_group(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('monitoring.legal-documents.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('monitoring.legal-documents.create'))->assertStatus(200);
    }

    public function test_admin_and_accounts_can_view_but_not_manage_documents(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $document = LegalDocument::factory()->create();

        $this->actingAs($admin)->get(route('admin.legal-documents.index'))->assertStatus(200);
        $this->actingAs($accounts)->get(route('accounts.legal-documents.index'))->assertStatus(200);

        $this->actingAs($admin)->post(route('monitoring.legal-documents.publish', $document))->assertForbidden();
        $this->actingAs($accounts)->post(route('monitoring.legal-documents.publish', $document))->assertForbidden();
        $this->actingAs($admin)->get(route('monitoring.legal-documents.index'))->assertForbidden();
    }

    public function test_creator_and_user_have_no_access_to_management_routes(): void
    {
        foreach ([Role::Creator, Role::User] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)->get(route('monitoring.legal-documents.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.legal-documents.index'))->assertForbidden();
        }
    }

    public function test_a_published_document_is_publicly_readable_without_login(): void
    {
        $document = LegalDocument::factory()->published()->create([
            'type' => LegalDocumentType::PrivacyPolicy,
            'title' => 'Our Privacy Policy',
        ]);

        $response = $this->get(route('legal.show', 'privacy_policy'));

        $response->assertStatus(200);
        $response->assertSee('Our Privacy Policy');
    }

    public function test_an_unpublished_document_type_returns_not_found(): void
    {
        LegalDocument::factory()->create(['type' => LegalDocumentType::RefundPolicy]); // draft, never published

        $this->get(route('legal.show', 'refund_policy'))->assertStatus(404);
    }

    public function test_a_bogus_type_returns_not_found(): void
    {
        $this->get(route('legal.show', 'made-up-type'))->assertStatus(404);
    }

    public function test_the_public_page_shows_the_most_recently_published_version(): void
    {
        LegalDocument::factory()->create([
            'type' => LegalDocumentType::TermsOfService,
            'title' => 'Old Terms',
            'published_at' => now()->subMonth(),
        ]);
        LegalDocument::factory()->create([
            'type' => LegalDocumentType::TermsOfService,
            'title' => 'New Terms',
            'published_at' => now(),
        ]);

        $response = $this->get(route('legal.show', 'terms_of_service'));

        $response->assertSee('New Terms');
        $response->assertDontSee('Old Terms');
    }

    public function test_the_footer_only_links_to_published_document_types(): void
    {
        LegalDocument::factory()->published()->create(['type' => LegalDocumentType::TermsOfService, 'title' => 'Live Terms']);
        LegalDocument::factory()->create(['type' => LegalDocumentType::PrivacyPolicy, 'title' => 'Draft Privacy']); // unpublished

        $response = $this->get('/');

        $response->assertSee('Live Terms');
        $response->assertDontSee('Draft Privacy');
    }
}
