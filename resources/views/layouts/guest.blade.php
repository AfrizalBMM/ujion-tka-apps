<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ujion - Registrasi Guru/Operator')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center">
    <div class="w-full max-w-xl mx-auto my-10">
        @yield('content')
    </div>
    <footer class="text-center text-gray-400 text-xs py-4">
        &copy; {{ date('Y') }} Ujion. All rights reserved.
    </footer>
</body>
</html>
