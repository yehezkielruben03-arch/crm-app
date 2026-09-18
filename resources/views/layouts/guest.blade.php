<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>CRM Portal - Login</title>

    <!-- Tailwind CSS (utility classes) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- Scripts (we still keep Vite for any future JS/CSS we might need) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- We inject any page-specific styles here -->
    @stack('styles')
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; overflow-x: hidden;">
    {{ $slot }}

    <!-- We inject any page-specific scripts here -->
    @stack('scripts')
</body>
</html>
