<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create branches
        $kl = Branch::create([
            'name'         => 'KL City Centre',
            'address'      => 'Lot 1, Jalan Ampang, KL',
            'phone'        => '+60312345678',
            'timezone'     => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);

        $jb = Branch::create([
            'name'         => 'JB Flagship',
            'address'      => 'No 5, Jalan Tun Razak, JB',
            'phone'        => '+6072223333',
            'timezone'     => 'Asia/Kuala_Lumpur',
            'opening_time' => '08:00:00',
            'closing_time' => '17:00:00',
        ]);

        // Create services
        $haircut = Service::factory()->shortDuration(30)->create(['name' => 'Haircut', 'price' => 50]);
        $massage = Service::factory()->create(['name' => 'Massage', 'duration_minutes' => 60, 'price' => 120]);
        $facial  = Service::factory()->create(['name' => 'Facial',  'duration_minutes' => 45, 'price' => 80]);

        // Admin user
        $admin = User::create([
            'name'      => 'Admin User',
            'email'     => 'admin@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::Admin,
            'branch_id' => null,
        ]);

        // Staff users
        $staff1 = User::create([
            'name'      => 'Alice Staff',
            'email'     => 'staff@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::Staff,
            'branch_id' => $kl->id,
        ]);

        $staff2 = User::create([
            'name'      => 'Bob Staff',
            'email'     => 'bob@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::Staff,
            'branch_id' => $jb->id,
        ]);

        // Customers
        $customer1 = Customer::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+60111111111']);
        $customer2 = Customer::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '+60122222222']);

        // Sample appointments (stored in UTC, branch is Asia/KL = UTC+8)
        Appointment::factory()->create([
            'branch_id'   => $kl->id,
            'user_id'     => $staff1->id,
            'customer_id' => $customer1->id,
            'service_id'  => $haircut->id,
            'starts_at'   => Carbon::parse('2026-06-10 01:00:00', 'UTC'), // 9:00 AM KL
            'ends_at'     => Carbon::parse('2026-06-10 01:30:00', 'UTC'), // 9:30 AM KL
            'status'      => AppointmentStatus::Pending,
        ]);

        Appointment::factory()->confirmed()->create([
            'branch_id'   => $kl->id,
            'user_id'     => $staff1->id,
            'customer_id' => $customer2->id,
            'service_id'  => $massage->id,
            'starts_at'   => Carbon::parse('2026-06-10 03:00:00', 'UTC'), // 11:00 AM KL
            'ends_at'     => Carbon::parse('2026-06-10 04:00:00', 'UTC'), // 12:00 PM KL
        ]);

        // Bulk factory data
        $extraBranches = Branch::factory()->count(3)->create();
        $allBranches   = collect([$kl, $jb])->merge($extraBranches);

        $extraStaff = User::factory()->staff()->count(5)->recycle($allBranches)->create();
        $allStaff   = collect([$staff1, $staff2])->merge($extraStaff);

        $extraCustomers = Customer::factory()->count(15)->create();
        $allCustomers   = collect([$customer1, $customer2])->merge($extraCustomers);

        $extraServices = Service::factory()->count(5)->create();
        $allServices   = collect([$haircut, $massage, $facial])->merge($extraServices);

        foreach ($allStaff as $staff) {
            for ($i = 0; $i < 3; $i++) {
                Appointment::factory()->create([
                    'branch_id'   => $staff->branch_id,
                    'user_id'     => $staff->id,
                    'customer_id' => $allCustomers->random()->id,
                    'service_id'  => $allServices->random()->id,
                ]);
            }
        }
    }
}
