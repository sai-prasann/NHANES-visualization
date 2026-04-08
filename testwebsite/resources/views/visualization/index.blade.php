<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            @endif

            <livewire:get-visualisation-data :datasets="$datasets" />

            <p>Select the datasets to be visualised.</p>

            <p>Select the variables to be plotted after selecting the dataset.</p>
        </div>
    </div>
</x-app-layout>