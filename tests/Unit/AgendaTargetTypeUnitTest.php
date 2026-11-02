<?php

namespace Tests\Unit;

use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Models\Agenda;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class AgendaTargetTypeUnitTest extends TestCase
{
    public function test_target_label_accessor_returns_appropriate_text(): void
    {
        $allUnitsAgenda = new Agenda(['target_type' => 'all_units']);
        $this->assertEquals('Seluruh Unit LLDIKTI (Pleno)', $allUnitsAgenda->target_label);

        $selectedUsersAgenda = new Agenda(['target_type' => 'selected_users']);
        $this->assertEquals('Khusus ASN Tertentu', $selectedUsersAgenda->target_label);

        $selectedUnitsAgenda = new Agenda(['target_type' => 'selected_units']);
        $this->assertEquals('Unit Kerja Tertentu', $selectedUnitsAgenda->target_label);
    }

    public function test_user_eligibility_for_all_units(): void
    {
        $agenda = new Agenda(['target_type' => 'all_units']);

        $user = new User();
        $user->id = 10;
        $user->role = 'staff';

        $this->assertTrue($agenda->isUserEligible($user));
    }

    public function test_user_eligibility_for_selected_users(): void
    {
        $agenda = new Agenda([
            'target_type' => 'selected_users',
            'pimpinan_id' => 100,
            'notulis_id' => 200,
        ]);

        $invitedUser = new User();
        $invitedUser->id = 1;
        $invitedUser->is_active = true;

        $uninvitedUser = new User();
        $uninvitedUser->id = 2;
        $uninvitedUser->is_active = true;

        $pimpinanUser = new User();
        $pimpinanUser->id = 100;
        $pimpinanUser->is_active = true;

        $notulisUser = new User();
        $notulisUser->id = 200;
        $notulisUser->is_active = true;

        // Set loaded relation without needing a DB connection
        $agenda->setRelation('invitedUsers', collect([$invitedUser]));

        // 1. ASN yang ada di relasi invitedUsers harus eligible
        $this->assertTrue($agenda->isUserEligible($invitedUser));

        // 2. ASN yang tidak diundang harus ditolak (tidak eligible)
        $this->assertFalse($agenda->isUserEligible($uninvitedUser));

        // 3. Pimpinan dan Notulis rapat selalu eligible
        $this->assertTrue($agenda->isUserEligible($pimpinanUser));
        $this->assertTrue($agenda->isUserEligible($notulisUser));
    }

    public function test_form_requests_contain_target_type_rules(): void
    {
        $storeRequest = new StoreAgendaRequest();
        $storeRules = $storeRequest->rules();

        $this->assertArrayHasKey('target_type', $storeRules);
        $this->assertArrayHasKey('unit_ids', $storeRules);
        $this->assertArrayHasKey('user_ids', $storeRules);

        $updateRequest = new UpdateAgendaRequest();
        $updateRules = $updateRequest->rules();

        $this->assertArrayHasKey('target_type', $updateRules);
        $this->assertArrayHasKey('unit_ids', $updateRules);
        $this->assertArrayHasKey('user_ids', $updateRules);
    }
}
