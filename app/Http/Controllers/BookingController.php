<?php

namespace App\Http\Controllers;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Actions\CreateAppointmentAction;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function show()
    {
        return view('booking.form', [
            'branches' => Branch::all(),
            'services' => Service::all(),
        ]);
    }

    public function store(Request $request, CreateAppointmentAction $createAction)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string',
            'branch_id'  => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'user_id'    => 'required|exists:users,id',
            'starts_at'  => 'required|date',
        ]);

        // Find or create customer
        $customer = Customer::firstOrCreate(
            ['email' => $validated['email'] ?? null, 'phone' => $validated['phone'] ?? null],
            ['name'  => $validated['name']]
        );

        $validated['customer_id'] = $customer->id;

        try {
            $appointment = $createAction->execute($validated);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->withErrors([
                'general' => 'The selected branch, staff, or service could not be found. Please try again.',
            ]);
        } catch (\Exception $e) {
            Log::error('Booking creation failed', ['error' => $e->getMessage(), 'data' => $validated]);
            return back()->withInput()->withErrors([
                'general' => 'Something went wrong while booking your appointment. Please try again.',
            ]);
        }

        return redirect()->route('booking.success')->with('appointment_id', $appointment->id);
    }

    public function success()
    {
        $appointment = null;
        if ($id = session('appointment_id')) {
            $appointment = Appointment::with(['branch', 'staff', 'customer', 'service'])->find($id);
        }

        return view('booking.success', compact('appointment'));
    }
}
