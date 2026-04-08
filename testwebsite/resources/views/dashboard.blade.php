<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <p>Download the datasets that you wish to work with.</p>
                <p>Then head over to the <a href="{{ route('visualization.showChart') }}" style="color: blue; text-decoration: underline;">Visualization</a> page to create and discover.</p>
                <br>
                <h1 class="text-xl font-bold mb-6">Datasets</h1>
                <div class="mb-6">
                    <form action="{{ route('dashboard') }}" method="get" class="flex items-center space-x-4">
                        <div class="flex-grow"> 
                            <input type="text" name="search" id="search" class="form-input rounded-md shadow-sm block w-full text-sm" placeholder="Search datasets..." value="{{ request('search') }}">
                        </div>
                        <div class="w-1/4"> 
                            <select name="component" id="component" class="form-select rounded-md shadow-sm block w-full text-sm">
                                <option value="">Apply component filter...</option>
                                <option value="demographics" {{ request('component') == 'demographics' ? 'selected' : '' }}>Demographics</option>
                                <option value="dietary" {{ request('component') == 'dietary' ? 'selected' : '' }}>Dietary</option>
                                <option value="examination" {{ request('component') == 'examination' ? 'selected' : '' }}>Examination</option>
                                <option value="laboratory" {{ request('component') == 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                                <option value="questionnaire" {{ request('component') == 'questionnaire' ? 'selected' : '' }}>Questionnaire</option>
                            </select>
                        </div>
                        <div class="w-1/4"> 
                            <select name="availability" id="availability" class="form-select rounded-md shadow-sm block w-full text-sm">
                                <option value="">Apply availablility filter...</option>
                                <option value="available" {{ request('availability') == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="not-available" {{ request('availability') == 'not-available' ? 'selected' : '' }}>Not Available</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="font-bold py-2 px-4 rounded text-sm" style="background-color: #3b82f6; color: white;">
                                Search
                            </button>
                        </div>
                    </form>
                </div>


                <div class="bg-white rounded-lg shadow relative">
                    <table class="border-collapse table-auto w-full whitespace-no-wrap bg-white table-striped relative">
                        <thead>
                            <tr class="text-left">
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">ID</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Year</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Component</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Description</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Docs URL</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Availability</th>
                                <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-3 text-gray-600 font-bold tracking-wider uppercase text-xs">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($datasets as $dataset)
                                <tr>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">{{ $dataset->id }}</td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">{{ $dataset->years ?? 'N/A' }}</td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">{{ $dataset->component ?? 'N/A' }}</td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">{{ $dataset->description ?? 'N/A' }}</td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">
                                        <a href="{{ $dataset->docs_url ?? '#' }}" class="text-blue-500 hover:text-blue-600" target="_blank">View Docs</a>
                                    </td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">
                                        @if ($dataset->is_available)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" style="background-color: #d1fae5; color: #065f46;">
                                                Available
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Not Available
                                            </span>
                                        @endif
                                    </td>
                                    <td class="border-dashed border-t border-gray-200 px-6 py-4 text-sm">
                                        @if (!$dataset->is_available)
                                        <form action="{{ route('dataset.download', $dataset->id) }}" method="POST">
                                        @csrf
                                        <button onclick="downloadDataset({{ $dataset->id }})" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm" style="background-color: #3b82f6; color: white;">
                                            Download
                                        </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border-dashed border-t border-gray-200 px-6 py-4 text-center text-gray-500 text-sm">No datasets found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">
                    {{ $datasets->links() }}
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function downloadDataset(id) {
        fetch(`/dataset/download/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('An error occurred while processing the dataset.');
            } else {
                alert('Dataset processed successfully!');
                location.reload();
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('An error occurred while processing the dataset.');
        });
    }
    </script>
</x-app-layout>