<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_reports_page_with_different_categories(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'position' => 'Developer',
        ]);

        // 1. Kategori Per Orang
        $res = $this->actingAs($owner)->get(route('owner.reports', [
            'period' => 'monthly',
            'category' => 'person',
        ]));
        $res->assertOk();
        $res->assertSee($employee->name);

        // 2. Kategori Per Jabatan
        $res = $this->actingAs($owner)->get(route('owner.reports', [
            'period' => 'weekly',
            'category' => 'position',
        ]));
        $res->assertOk();
        $res->assertSee('Developer');

        // 3. Kategori Sering Terlambat
        $res = $this->actingAs($owner)->get(route('owner.reports', [
            'period' => 'monthly',
            'category' => 'late',
        ]));
        $res->assertOk();

        // 4. Kategori Jam Lembur
        $res = $this->actingAs($owner)->get(route('owner.reports', [
            'period' => 'monthly',
            'category' => 'overtime',
        ]));
        $res->assertOk();
    }

    public function test_owner_can_export_excel_with_categories(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $res = $this->actingAs($owner)->get(route('owner.attendance.export', [
            'period' => 'monthly',
            'category' => 'person',
        ]));

        $res->assertOk();
        $this->assertEquals('application/vnd.ms-excel; charset=UTF-8', $res->headers->get('content-type'));
    }
}
