<x-app-layout>
<style>
    .section-title {
        font-weight: bold;
        color: #333;
    }

    .form-group label {
        font-weight: bold;
        color: #333;
    }

    .form-group h2 {
        font-weight: bold;
        color: #333;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .container {
        padding-top: 40px; /* Adjust this value as needed */
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
   
    .scrollable-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        margin-top: 10px;
    }
    .form-group label {
        margin-top: 30px; /* Adjust this value as needed */
    }
    .scrollable-container select {
        width: 100%;
    }
    
    #stats {
        margin-top: 20px;
    }
    .form-group input[type="range"] {
        -webkit-appearance: none;
        width: 100%;
        height: 8px;
        background: #ddd;
        border-radius: 5px;
        outline: none;
        margin-top: 8px;
    }

    .form-group input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        background: #007bff;
        border-radius: 50%;
        cursor: pointer;
    }

    .form-group input[type="number"] {
        width: 80px;
        font-size: 14px;
        margin-left: 8px;
        padding: 5px;
    }
</style>

<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />

<!-- Include jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

<!-- Include Plotly.js -->
<script src="https://cdn.jsdelivr.net/npm/plotly.js-dist@latest"></script>

<!-- Include Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<div class="container">
    <h3 class="section-title">Database Visualization</h3>
    <div class="row">
        <!-- Sidebar with Select Tables, Select Rows, and Table Headers -->
        <div class="col-md-6">
            <form id="visualization-form" method="GET" action="{{ route('visualization.display') }}">
                <div class="form-group">
                     <label for="table-select" class="table-select-label">Select Tables:</label>
                    <select multiple class="form-control" id="table-select" name="tables[]" size="10">
                        @foreach($matchedTables as $tableName => $tableInfo)
                            <option value="{{ $tableName }}" {{ in_array($tableName, $selectedTables) ? 'selected' : '' }}>
                                {{ $tableInfo['code'] }} - {{ $tableInfo['description'] }} ({{ $tableInfo['years'] }})
                            </option>       
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary mt-4">Show Headers</button>

                <div class="form-group mt-4">
                    <h6>Select number of rows to visualize (randomized)</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="range" min="0" max="{{ $totalRecords }}" name="rowVisualisationCount" value="{{ $rowLimit ?? 0 }}" class="form-control" id="row-limit-range">
                        <input type="number" min="0" max="{{ $totalRecords }}" name="rowVisualisationCount" value="{{ $rowLimit ?? 0 }}" class="form-control" id="row-limit-number">
                    </div>
                </div>
            </form>

            @if(!empty($headers))
                <h6 class="mt-4 section-title">Table Headers</h6>
                @foreach($headers as $table => $columns)
                    <label class="section-title">{{ $matchedTables[$table]['code'] }} - {{ $matchedTables[$table]['description'] }}</label>
                    <div class="scrollable-container">
                        <select multiple name="selected_headers[]" id="column-select-{{ $table }}" data-table="{{ $table }}">
                            @foreach($columns as $column)
                                <option value="{{ $table }}.{{ $column }}" data-type="{{ $columnTypes[$table][$column] }}">
                                @if(isset($headerDescriptions[strtoupper($column)]) && $headerDescriptions[strtoupper($column)]->doc_code === strtoupper($column))
                                    {{ $column }} - {{ $headerDescriptions[strtoupper($column)]->description }} ({{ $columnTypes[$table][$column] }})
                                @else
                                    {{ $column }} ({{ $columnTypes[$table][$column] }})
                                @endif

                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                @if(!empty($tableRows))
                    <script>
                        // Pass table rows and column types data to JavaScript
                        const tableRows = @json($tableRows);
                        const columnTypes = @json($columnTypes);
                    </script>
                @endif
            @endif
        </div>

        <!-- Chart Visualization -->
        @if(!empty($headers))
        <div class="col-md-6">
            <div class="form-group mt-4">
                <h6 class="section-title">Chart Visualization</h6>
                <div class="d-flex gap-2">
                    <select id="chart-type" class="form-control">
                        <option value="scatter">Scatter</option>
                        <option value="box">Box Plot</option>
                        <option value="bar">Bar Chart</option>
                        <option value="line">Line Chart</option>
                        <option value="pie">Pie Chart</option>
                    </select>

                    <!-- Removed the Plot button -->
                </div>
                
                <div id="chart" class="mt-4" style="height: 350px;"></div>
                
                <!-- Add the Insight Button -->
                <button id="insight-button" class="btn btn-primary mt-4">View Insights</button>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for table select
    $('#table-select').select2({
        placeholder: 'Select tables',
        allowClear: true,
        width: 'resolve'
    });

    // Initialize Select2 for each column select
    $('select[name="selected_headers[]"]').select2({
        placeholder: 'Select columns',
        allowClear: true,
        width: 'resolve'
    }).on('change', function() {
        updateURL();
        plotChart(); // Call plotChart when selection changes
    });
    
    const form = document.getElementById('visualization-form');
    const chartTypeSelect = document.getElementById('chart-type');
    const rangeInput = document.getElementById('row-limit-range');
    const numberInput = document.getElementById('row-limit-number');

    // Synchronize range and number inputs
    rangeInput.addEventListener('input', function() {
        numberInput.value = this.value;
        updateURL();
        plotChart(); // Call plotChart when input changes
    });

    numberInput.addEventListener('input', function() {
        rangeInput.value = this.value;
        updateURL();
        plotChart(); // Call plotChart when input changes
    });

    // Update URL with selected headers and chart type
    function updateURL() {
        const selectedHeaders = Array.from(document.querySelectorAll('select[name="selected_headers[]"] option:checked'))
            .map(option => option.value);
        
        const formData = new FormData(form);
        // Remove selected_headers from FormData
        formData.delete('selected_headers[]'); 
        const params = new URLSearchParams(formData).toString();
        const selectedHeadersParam = selectedHeaders.length > 0 ? `&selected_headers[]=${selectedHeaders.join('&selected_headers[]=')}` : '';
        const chartTypeParam = `&chart_type=${encodeURIComponent(chartTypeSelect.value)}`;

        history.replaceState(null, '', `${form.action}?${params}${selectedHeadersParam}${chartTypeParam}`);
    }

    // Function to set selected options based on URL parameters
    function setSelectedHeaders() {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedHeaders = urlParams.getAll('selected_headers[]');
        // Default to scatter
        const chartType = urlParams.get('chart_type') || 'scatter'; 

        document.getElementById('chart-type').value = chartType;

        selectedHeaders.forEach(header => {
            document.querySelectorAll('select[name="selected_headers[]"] option').forEach(option => {
                if (option.value === header) {
                    option.selected = true;
                }
            });
        });
        $('select[name="selected_headers[]"]').trigger('change.select2'); // Trigger Select2 to refresh
    }

    // Function to plot the chart
    function plotChart() {
        const selectedHeaders = Array.from(document.querySelectorAll('select[name="selected_headers[]"] option:checked')).map(el => el.value.split('.'));
        const selectedRows = [];

        // Gather data from the 'tableRows' variable
        selectedHeaders.forEach(([table, column]) => {
            if (tableRows[table]) {
                tableRows[table].forEach(row => {
                    const rowData = {};
                    rowData[column] = row[column]; 
                    selectedRows.push(rowData);
                });
            }
        });

        // Fetch chart data from server
        fetch('{{ route('visualization.getChartData') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                selected_headers: selectedHeaders.map(header => header.join('.')),
                selected_rows: selectedRows,
                chart_type: chartTypeSelect.value 
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Chart data received:', data);
            if (data.chart && data.layout) {
                const xAxisTitle = selectedHeaders[0] ? selectedHeaders[0][1] : 'X Axis';
                const yAxisTitle = selectedHeaders[1] ? selectedHeaders[1][1] : 'Y Axis';
                
                // Set layout with axis titles
                const layout = {
                    ...data.layout,
                    xaxis: {
                        title: {
                            text: xAxisTitle
                        }
                    },
                    yaxis: {
                        title: {
                            text: yAxisTitle
                        }
                    }
                };

                Plotly.newPlot('chart', [data.chart], layout);
            } else {
                console.error('Invalid chart data:', data);
            }
        })
        .catch(error => console.error('Error fetching chart data:', error));
    }

    // Call the function to set selected headers and chart type on page load
    setSelectedHeaders();

    // Call the function to plot the chart based on URL parameters 
    if (document.querySelector('select[name="selected_headers[]"] option:checked')) {
        plotChart();
    }

    // Update URL when selection changes
    document.querySelectorAll('select[name="selected_headers[]"]').forEach(select => {
        select.addEventListener('change', updateURL);
    });

    // Update URL when table selection changes
    document.getElementById('table-select').addEventListener('change', updateURL);

   // Update URL when chart type changes
   chartTypeSelect.addEventListener('change', function() {
       updateURL();
       plotChart(); // Call plotChart when chart type changes
   });

   // Update URL when form is submitted
   form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        // Collect selected headers
        const selectedHeaders = Array.from(document.querySelectorAll('select[name="selected_headers[]"] option:checked'))
        .map(option => option.value);

        // Collect other form data
        const formData = new FormData(form);
        // Remove selected_headers from the FormData
        formData.delete('selected_headers[]'); 

        // Construct query parameters
        const params = new URLSearchParams(formData).toString();
        const selectedHeadersParam = selectedHeaders.length > 0 ? `&selected_headers[]=${selectedHeaders.join('&selected_headers[]=')}` : '';
        const chartTypeParam = `&chart_type=${encodeURIComponent(chartTypeSelect.value)}`;

        // Redirect to the new URL with query parameters
        window.location.href = `${form.action}?${params}${selectedHeadersParam}${chartTypeParam}`;
   });

    // Add the Insight Button functionality
    const insightButton = document.getElementById('insight-button');
    
    if (insightButton) {
        insightButton.addEventListener('click', function() {
            // Collect selected headers and other data
            const selectedHeaders = Array.from(document.querySelectorAll('select[name="selected_headers[]"] option:checked')).map(el => el.value);
            const formData = new FormData(form);
            formData.delete('selected_headers[]');

            const params = new URLSearchParams(formData).toString();
            const selectedHeadersParam = selectedHeaders.length > 0 ? `&selected_headers[]=${selectedHeaders.join('&selected_headers[]=')}` : '';
            const chartTypeParam = `&chart_type=${encodeURIComponent(chartTypeSelect.value)}`;

            // Redirect to the insights page with parameters
            window.location.href = `{{ route('visualization.insights') }}?${params}${selectedHeadersParam}${chartTypeParam}`;
        });
    }

    setSelectedHeaders();
});
</script>
</x-app-layout>
