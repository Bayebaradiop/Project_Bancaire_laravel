<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

class CompteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client1;
    protected $client2;
    protected $clientUser1;
    protected $clientUser2;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        // Créer le premier client avec son utilisateur
        $this->clientUser1 = User::factory()->create([
            'role' => 'client',
            'email' => 'client1@test.com',
        ]);
        $this->client1 = Client::factory()->create([
            'user_id' => $this->clientUser1->id,
        ]);

        // Créer le deuxième client avec son utilisateur
        $this->clientUser2 = User::factory()->create([
            'role' => 'client',
            'email' => 'client2@test.com',
        ]);
        $this->client2 = Client::factory()->create([
            'user_id' => $this->clientUser2->id,
        ]);

        // Créer des comptes pour le client 1
        Compte::factory()->count(3)->create([
            'client_id' => $this->client1->id,
            'type' => 'epargne',
        ]);

        // Créer des comptes pour le client 2
        Compte::factory()->count(2)->create([
            'client_id' => $this->client2->id,
            'type' => 'cheque',
        ]);
    }

    /** @test */
    public function test_admin_peut_voir_tous_les_comptes()
    {
        Passport::actingAs($this->admin);

        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'numeroCompte',
                        'titulaire',
                        'type',
                        'solde',
                        'devise',
                    ]
                ],
                'pagination'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Liste des comptes récupérée avec succès',
            ]);

        // L'admin doit voir les 5 comptes (3 du client1 + 2 du client2)
        $this->assertEquals(5, $response->json('pagination.total'));
    }

    /** @test */
    public function test_client_ne_voit_que_ses_propres_comptes()
    {
        Passport::actingAs($this->clientUser1);

        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vos comptes ont été récupérés avec succès',
            ]);

        // Le client1 doit voir seulement ses 3 comptes
        $this->assertEquals(3, $response->json('pagination.total'));

        // Vérifier que tous les comptes appartiennent au client1
        $data = $response->json('data');
        foreach ($data as $compte) {
            $compteModel = Compte::find($compte['id']);
            $this->assertEquals($this->client1->id, $compteModel->client_id);
        }
    }

    /** @test */
    public function test_client_ne_peut_pas_voir_comptes_autre_client()
    {
        Passport::actingAs($this->clientUser2);

        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200);

        // Le client2 doit voir seulement ses 2 comptes
        $this->assertEquals(2, $response->json('pagination.total'));

        // Vérifier qu'aucun compte du client1 n'est visible
        $data = $response->json('data');
        foreach ($data as $compte) {
            $compteModel = Compte::find($compte['id']);
            $this->assertEquals($this->client2->id, $compteModel->client_id);
            $this->assertNotEquals($this->client1->id, $compteModel->client_id);
        }
    }

    /** @test */
    public function test_utilisateur_non_authentifie_ne_peut_pas_acceder()
    {
        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_admin_peut_filtrer_par_type()
    {
        Passport::actingAs($this->admin);

        $response = $this->getJson('/api/v1/comptes?type=epargne');

        $response->assertStatus(200);

        // Doit voir seulement les 3 comptes épargne du client1
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    /** @test */
    public function test_client_peut_filtrer_ses_comptes_par_type()
    {
        Passport::actingAs($this->clientUser1);

        $response = $this->getJson('/api/v1/comptes?type=epargne');

        $response->assertStatus(200);

        // Le client1 a 3 comptes épargne
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    /** @test */
    public function test_client_sans_profil_client_recoit_liste_vide()
    {
        // Créer un utilisateur sans profil client
        $userSansClient = User::factory()->create([
            'role' => 'client',
            'email' => 'sansclient@test.com',
        ]);

        Passport::actingAs($userSansClient);

        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vos comptes ont été récupérés avec succès',
            ]);

        $this->assertEquals(0, $response->json('pagination.total'));
        $this->assertEmpty($response->json('data'));
    }

    /** @test */
    public function test_pagination_fonctionne_pour_admin()
    {
        Passport::actingAs($this->admin);

        $response = $this->getJson('/api/v1/comptes?limit=2&page=1');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
        $this->assertEquals(5, $response->json('pagination.total'));
        $this->assertEquals(1, $response->json('pagination.current_page'));
    }

    /** @test */
    public function test_pagination_fonctionne_pour_client()
    {
        Passport::actingAs($this->clientUser1);

        $response = $this->getJson('/api/v1/comptes?limit=2&page=1');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    /** @test */
    public function test_messages_differents_selon_role()
    {
        // Test admin
        Passport::actingAs($this->admin);
        $adminResponse = $this->getJson('/api/v1/comptes');
        $this->assertEquals(
            'Liste des comptes récupérée avec succès',
            $adminResponse->json('message')
        );

        // Test client
        Passport::actingAs($this->clientUser1);
        $clientResponse = $this->getJson('/api/v1/comptes');
        $this->assertEquals(
            'Vos comptes ont été récupérés avec succès',
            $clientResponse->json('message')
        );
    }
}
