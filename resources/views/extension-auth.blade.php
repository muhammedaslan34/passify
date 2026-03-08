<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect Extension — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center max-w-sm w-full">

        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-gray-900 mb-2">Connect Passify Extension</h1>
        <p class="text-sm text-gray-500 mb-2">Signed in as <span class="font-medium text-gray-700">{{ auth()->user()->email }}</span></p>
        <p class="text-sm text-gray-500 mb-7">Click the button below to grant the extension access to your vault. A secure token will be generated automatically.</p>

        <form method="POST" action="{{ route('extension.connect') }}">
            @csrf
            <button type="submit"
                class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold text-sm hover:bg-indigo-700 transition-colors duration-200">
                Connect Extension
            </button>
        </form>

    </div>

</body>
</html>
