<!DOCTYPE html>
<html>
<head>
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
<div class="max-w-lg mx-auto bg-white rounded shadow p-6">
    <h1 class="text-2xl font-bold mb-6">Book an Appointment</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.store') }}">
        @csrf

        <div class="mb-4">
            <label class="block font-medium mb-1">Your Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                   class="w-full border rounded p-2" placeholder="+60123456789">
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Branch</label>
            <select name="branch_id" class="w-full border rounded p-2" required
                    onchange="filterStaff(this.value)">
                <option value="">Select branch...</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} ({{ $branch->timezone }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Staff Member</label>
            <select name="user_id" id="staff-select" class="w-full border rounded p-2" required>
                <option value="">Select staff...</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Service</label>
            <select name="service_id" class="w-full border rounded p-2" required>
                <option value="">Select service...</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}">
                        {{ $service->name }} ({{ $service->duration_minutes }} min)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-medium mb-1">Preferred Date & Time</label>
            <input type="text" id="datetime-picker" name="starts_at" value="{{ old('starts_at') }}"
                class="w-full border rounded p-2 bg-white" required placeholder="Select Date & Time...">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Book Appointment
        </button>
    </form>
</div>

<div class="fixed bottom-4 right-4 text-right">
    @auth
        <a href="{{ auth()->user()->isAdmin() ? '/admin' : '/staff' }}"
           class="text-xs text-gray-400 hover:text-gray-500">
            Go to Dashboard
        </a>
    @else
        <a href="/admin/login" class="text-xs text-gray-400 hover:text-gray-500">Admin Login</a>
        <span class="text-xs text-gray-300"> · </span>
        <a href="/staff/login" class="text-xs text-gray-400 hover:text-gray-500">Staff Login</a>
    @endauth
</div>
</body>

<script>

function filterStaff(branchId) {
    const select = document.getElementById('staff-select');

    // Reset the dropdown first
    select.innerHTML = '<option value="">Loading staff...</option>';

    if (!branchId) {
        select.innerHTML = '<option value="">Select a branch first</option>';
        return;
    }

    fetch(`/branches/${branchId}/staff`)
        .then(response => {
            if (!response.ok) throw new Error('Failed to load staff');
            return response.json();
        })
        .then(staff => {
            if (staff.length === 0) {
                select.innerHTML = '<option value="">No staff available</option>';
                return;
            }

            select.innerHTML = '<option value="">Select staff...</option>';
            staff.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = s.name;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error(error);
            select.innerHTML = '<option value="">Error loading staff</option>';
        });
}

// On page load, if a branch was previously selected (after validation failure),
// re-load the staff list and restore the selected staff member
document.addEventListener('DOMContentLoaded', function () {
    const savedBranchId = "{{ old('branch_id') }}";
    const savedStaffId  = "{{ old('user_id') }}";

    if (savedBranchId) {
        fetch(`/branches/${savedBranchId}/staff`)
            .then(r => r.json())
            .then(staff => {
                const select = document.getElementById('staff-select');
                select.innerHTML = '<option value="">Select staff...</option>';
                staff.forEach(s => {
                    const option = document.createElement('option');
                    option.value = s.id;
                    option.textContent = s.name;
                    // Restore previously selected staff
                    if (String(s.id) === String(savedStaffId)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            });
    }
});

flatpickr("#datetime-picker", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today",
    time_24hr: true
});
</script>

</html>