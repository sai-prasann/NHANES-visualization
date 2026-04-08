<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie-edge">
    <title>NVisulaiser 2.0</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @vite('resources/css/app.css')
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .nav-link:hover {
            color: green;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <header class="fixed bg-gray-700 top-0 left-0 right-0 z-50">
        <div class="container mx-auto flex justify-between p-4">
            <h1 class="text-xl font-black text-white">NVisualiser</h1>
            <nav class="-mx-2">
                <a href="{{ route('home') }}" class="text-lg mx-2 text-white hover:text-green-500 transition">Home</a>
                @if (Route::has('login'))
                    <!-- <nav class="-mx-3 flex flex-1 justify-end"> -->
                        @auth
                            <a
                                href="{{ url('/dashboard') }}"
                                class="text-lg mx-2 text-white hover:text-green-500 transition"
                            >
                                Dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                    class="text-lg mx-2 text-white hover:text-green-500 transition"
                            >
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="text-lg mx-2 text-white hover:text-green-500 transition"
                                >
                                    Register
                                </a>
                            @endif
                        @endauth
                    <!-- </nav> -->
                @endif
            </nav>
        </div>
    </header>
    <main class="flex-grow mt-16">
        @yield('page-content')
        @yield('content')
        @yield('scripts')
        @stack('scripts')
    </main>
    <footer class="bg-gray-700 text-white text-center p-4 mt-10">
        <p>NVisualiser 2.0 &copy; 2024. In progress. </p>
    </footer>
    <!-- Include your JavaScript here if needed -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>