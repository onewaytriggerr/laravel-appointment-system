<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
<div class="max-w-lg mx-auto bg-white rounded shadow p-6 text-center">

    <div class="text-green-500 text-6xl mb-4">✓</div>

    <h1 class="text-2xl font-bold text-gray-800 mb-2">Booking Confirmed!</h1>

    <p class="text-gray-600 mb-6">
        Your appointment has been received. We will be in touch to confirm your booking.
    </p>

    @if ($appointment)
    <div class="mt-2 mb-6 text-left border-t pt-4 space-y-2 text-sm text-gray-700">
        <div><span class="font-semibold">Customer:</span> {{ $appointment->customer->name }}</div>
        <div><span class="font-semibold">Service:</span> {{ $appointment->service->name }}</div>
        <div><span class="font-semibold">Branch:</span> {{ $appointment->branch->name }}</div>
        <div><span class="font-semibold">Staff:</span> {{ $appointment->staff->name }}</div>
        <div>
            <span class="font-semibold">Date & Time:</span>
            {{ $appointment->starts_at->setTimezone($appointment->branch->timezone)->format('d M Y, h:i A') }}
        </div>
        <div><span class="font-semibold">Status:</span> {{ $appointment->status->label() }}</div>
    </div>
    @endif

    <a href="{{ route('booking.form') }}"
       class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
        Make Another Booking
    </a>

</div>
</body>
</html>