<x-app-layout>
<div class="container">
    <h1 class="text-2xl font-bold">Insights</h1>
    <div class="mt-4">
        <table summary="Field properties" border="" class="wrapper" cellspacing="0">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-blue-500 uppercase tracking-wider"><strong>Description</strong></th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-blue-500 uppercase tracking-wider"><strong>Value</strong></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <!-- Selected Tables -->
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Selected Tables</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <ul style="margin: 0; padding: 0; list-style-type: none;">
                            @foreach($selectedTables as $table)
                                <li>{{ $table }}</li>
                            @endforeach
                        </ul>
                    </td> 
                </tr>

                <!-- Selected Headers -->
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Selected Headers</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <ul style="margin: 0; padding: 0; list-style-type: none;">
                            @foreach($selectedHeaders as $header)
                                <li>{{ $header }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>

                <!-- Number of Rows Selected -->
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Number of Rows Selected</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rowLimit }}</td>
                </tr>
                <tr>       
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Mean</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['mean'] }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Median</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['median'] }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Standard Deviation</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['stdDev'] }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><strong>Correlation</strong></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['correlation'] }}</td>
                </tr>
            </tbody>
        </table>

        
    </div>
</div>
</x-app-layout>
