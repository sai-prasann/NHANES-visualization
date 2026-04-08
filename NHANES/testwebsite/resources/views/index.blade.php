<x-app-layout>
    <div class="container mx-auto mt-10 p-6 bg-white shadow-lg">
        <img src="{{ asset('images/home_page.png') }}" alt="Image" class="w-full h-64 object-cover">
        <h1 class="text-3xl font-bold mt-6">Welcome to NVisualiser 2.0</h1>
        <p class="mt-4 text-gray-700">NVisualiser 2.0 is a powerful tool designed to help you uncover valuable insights and patterns within NHANES (National Health and Nutrition Examination Survey) datasets. 
            Whether you are a researcher, healthcare professional, or data enthusiast, 
            NVisualiser provides the tools you need to analyze and visualize complex data with ease. To start visualising, create an account <a href="{{ route('register') }}" style="color: blue; text-decoration: underline;">now</a>.</p>
    </div>
</x-app-layout>