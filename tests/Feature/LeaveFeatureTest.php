<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_leave_page_and_submit_leave(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('leave.index'));
        $response->assertOk();

        $response = $this->actingAs($user)->post(route('leave.store'), [
            'type' => 'cuti',
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'reason' => 'Keperluan keluarga',
        ]);

        $response->assertRedirect(route('leave.index'));
        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'type' => 'cuti',
            'status' => 'pending',
        ]);
    }

    public function test_when_leave_approved_calendar_attendance_is_cuti_and_meal_allowance_is_zero(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'meal_allowance' => 25000,
        ]);
        $owner = User::factory()->create(['role' => 'owner']);

        // Pilih hari kerja Senin yang pasti bukan Minggu
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $tuesday = $monday->copy()->addDay();

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'cuti',
            'start_date' => $monday->toDateString(),
            'end_date' => $tuesday->toDateString(),
            'reason' => 'Liburan',
            'status' => 'pending',
        ]);

        // Owner approves leave
        $response = $this->actingAs($owner)->post(route('owner.leave.status', $leave->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertEquals('approved', $leave->fresh()->status);

        // Pastikan record attendance hari tersebut otomatis berstatus 'cuti'
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $monday->toDateString(),
            'status' => 'cuti',
            'daily_work_hours' => 0,
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $tuesday->toDateString(),
            'status' => 'cuti',
            'daily_work_hours' => 0,
        ]);

        // Uang makan untuk hari cuti tidak dihitung (harus 0 karena status cuti bukan hadir)
        $mealAllowance = $user->calculateMealAllowance($monday->toDateString(), $tuesday->toDateString());
        $this->assertEquals(0, $mealAllowance);
    }

    public function test_user_can_cancel_their_leave(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $tomorrow = Carbon::tomorrow()->toDateString();

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'cuti',
            'start_date' => $tomorrow,
            'end_date' => $tomorrow,
            'reason' => 'Rencana awal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('leave.cancel', $leave->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('leaves', [
            'id' => $leave->id,
        ]);
    }
}
