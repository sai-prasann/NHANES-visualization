<div class="bg-white rounded-lg shadow p-4">
  <div class="grid grid-cols-12 gap-4">
    {{-- Var X --}}
    <div class="col-span-12 md:col-span-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Variable X</label>

      {{-- Tom Select UI (ignored by Livewire) --}}
      <div wire:ignore>
        <select id="varXSelect" class="ts w-full border-gray-300 rounded-md shadow-sm">
          <option value="">-- Select Variable --</option>
          @foreach ($variables as $v)
            <option value="{{ $v }}">{{ $v }}</option>
          @endforeach
        </select>
      </div>

      {{-- Livewire binding via hidden input (kept in sync from JS) --}}
      <input type="hidden" id="varX" wire:model.live="varX">
    </div>

    {{-- Var Y --}}
    <div class="col-span-12 md:col-span-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Variable Y</label>

      {{-- Tom Select UI (ignored by Livewire) --}}
      <div wire:ignore>
        <select id="varYSelect" class="ts w-full border-gray-300 rounded-md shadow-sm">
          <option value="">-- Select Variable --</option>
          @foreach ($variables as $v)
            <option value="{{ $v }}">{{ $v }}</option>
          @endforeach
        </select>
      </div>

      {{-- Livewire binding via hidden input (kept in sync from JS) --}}
      <input type="hidden" id="varY" wire:model.live="varY">

      <p class="text-xs text-gray-500 mt-1">Y is required for Scatter, Line (binned mean), and Box plot.</p>
    </div>

    {{-- Chart Type --}}
    <div class="col-span-12">
      <label class="block text-sm font-medium text-gray-700 mb-1">Chart Type</label>
      <select wire:model.live="chartMode" class="w-full border-gray-300 rounded-md shadow-sm">
        <option value="scatter">Scatter (X vs Y)</option>
        <option value="line-binned-mean">Line (binned mean of Y vs X)</option>
        <option value="bar-count">Bar (category counts of X)</option>
        <option value="histogram">Histogram (X only)</option>
        <option value="pie">Pie (category share of X)</option>
        <option value="box">Box plot (Y by X categories)</option>
        <option value="violin">Violin plot (Y by X categories)</option>
        <option value="stacked-bar">Stacked Bar (X by Y)</option>
      </select>
      <p class="text-xs text-gray-500 mt-1">
        Bar/Histogram/Pie use X only. Scatter/Line/Box need both X and Y (numeric Y).
      </p>
    </div>
  </div>

  @error('vars')
    <p class="text-sm text-red-600">{{ $message }}</p>
  @enderror>

  {{-- Chart + Insights side by side --}}
  <div class="flex flex-wrap md:flex-nowrap gap-4 mt-4">

    {{-- Chart container --}}
    <div class="flex-grow border rounded p-4 min-w-[60%]" wire:ignore>
      <div class="mb-2 text-sm font-semibold">{{ $chartTitle ?? 'Preview' }}</div>
      <div id="vizMount"></div>
    </div>

    {{-- Insights + Button --}}
    <div class="w-full md:w-1/4 flex flex-col gap-4">
      <button wire:click="getInsights"
        class="bg-blue-600 hover:bg-blue-700 text-black px-4 py-2 rounded w-full text-center">
          Get Insights
      </button>

      @if ($showInsights)
        <div class="p-4 border rounded bg-gray-100 overflow-auto max-h-[400px]">
          <h2 class="font-semibold mb-2">Insights</h2>
          <pre class="text-sm whitespace-pre-wrap">{{ $insightText }}</pre>
        </div>
      @endif
    </div>

  </div>

  @once
  {{-- Tom Select CSS/JS (CDN) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>

  <script>

    console.log('Script loaded...');

    document.addEventListener('livewire:init', () => {

      const params = new URLSearchParams(window.location.search);

      if (params.has('varX') || params.has('varY') || params.has('chartMode')) {
        // Use `replaceState` after hydration
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
      }


      console.log('livewire:init fired');

      let chartInstance = null;

      function getAxisTitles() {
        return {
          x: {
            title: {
              display: true,
              text: Livewire.first().get('varX') || 'X Axis'
            }
          },
          y: {
            title: {
              display: true,
              text: Livewire.first().get('varY') || 'Y Axis'
            }
          }
        };
      }

      function getChartTitle() {
        const varX = Livewire.first().get('varX') || 'X';
        const varY = Livewire.first().get('varY') || 'Y';
        return `${varX} vs ${varY}`;
      }


      /* ---------- auto colors ---------- */
      const basePalette = [
        '#2563eb','#16a34a','#f59e0b','#ef4444','#7c3aed',
        '#059669','#d946ef','#0ea5e9','#ef7d10','#22c55e',
        '#e11d48','#10b981','#a855f7','#3b82f6','#f43f5e'
      ];
      const asArray = (n) => Array.from({length:n}, (_,i)=> basePalette[i % basePalette.length]);
      const asRGBA  = (hex, a=0.25) => {
        const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        const r = parseInt(m[1],16), g = parseInt(m[2],16), b = parseInt(m[3],16);
        return `rgba(${r}, ${g}, ${b}, ${a})`;
      };

      function ensureCanvas() {
        const mount = document.getElementById('vizMount');
        if (!mount) { console.warn('#vizMount not found'); return null; }
        let canvas = mount.querySelector('canvas#vizChart');
        if (!canvas) {
          canvas = document.createElement('canvas');
          canvas.id = 'vizChart';

          const width = mount.offsetWidth;
          const height = 400;

          const dpr = window.devicePixelRatio || 1;
          canvas.width = width * dpr;
          canvas.height = height * dpr;
          canvas.style.width = width + 'px';
          canvas.style.height = height + 'px';

          mount.innerHTML = '';
          mount.appendChild(canvas);
        }

        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);
        return ctx;
      }


      function drawChart(pl) {
        const ctx = ensureCanvas();
        if (!ctx) return;
        if (!window.Chart) { console.error('Chart.js not loaded'); return; }

        if (chartInstance) { try { chartInstance.destroy(); } catch(e) {} chartInstance = null; }

        const { type, labels, series, title } = pl || {};
        let config = null;

        if (type === 'scatter') {
          const color = basePalette[0];
          config = {
            type: 'scatter',
            data: {
              datasets: [{
                label: 'Data Points',
                data: Array.isArray(series?.[0]) ? series[0] : [],
                pointRadius: 2,
                pointBackgroundColor: color,
                borderColor: color,
              }]
            },
            options: {
              responsive: true,
              animation: false,
              scales: getAxisTitles(),
              plugins: {
                title: {
                  display: true,
                  text: getChartTitle(),
                  font: { size: 16 }
                }
              }
            }
          };

        } else if (type === 'line') {
            const color = basePalette[4];
            config = {
            type: 'line',
            data: {
              labels: labels || [],
              datasets: [{
                label: 'Mean Values',
                data: series?.[0] ?? [],
                tension: 0,
                borderColor: color,
                backgroundColor: asRGBA(color, 0.15),
                pointBackgroundColor: color,
                fill: false
              }]
            },
            options: { responsive: true, animation: false, scales: getAxisTitles(), plugins: { title: { display: true, text: getChartTitle(), font: { size: 16 } } } }
          };

        } else if (type === 'bar') {
          const varXLabel = Livewire.first().get('varX') || 'X';
          config = {
            type: 'bar',
            data: {
              labels: labels || [],
              datasets: [{
                label: varXLabel,
                data: series[0],   
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              animation: false,
              scales: {
                x: {
                  title: {
                    display: true,
                    text: varXLabel
                  }
                },
                y: {
                  title: {
                    display: true,
                    text: 'Count'
                  },
                  beginAtZero: true
                }
              },
              plugins: {
                title: {
                  display: true,
                  text: getChartTitle(varXLabel),
                  font: { size: 16 }
                }
              }
            }
          };
        } else if (type === 'pie') {
            const cols = asArray((series?.[0] ?? []).length);
            config = {
            type: 'pie',
            data: {
              labels: labels || [],
              datasets: [{
                label: 'Share',
                data: series?.[0] ?? [],
                backgroundColor: cols.map(c => asRGBA(c, 0.6)),
                borderColor: '#ffffff',
                borderWidth: 1
              }]
            },
            options: { responsive: true, animation: false, plugins: { title: { display: true, text: getChartTitle(), font: { size: 16 } } } }
          };

        } else if (type === 'boxplot') {
            const color = basePalette[6];
            const ds = Array.isArray(series?.[0]) ? series[0] : [];
            config = {
            type: 'boxplot',
            data: {
              labels: labels || [],
              datasets: [{
                label: 'Box plot',
                data: ds,
                backgroundColor: asRGBA(color, 0.25),
                borderColor: color,
                outlierColor: basePalette[11]
              }]
            },
            options: { responsive: true, animation: false, scales: getAxisTitles(), plugins: { title: { display: true, text: getChartTitle(), font: { size: 16 } } } }
          };

        } else if (type === 'violin') {
            const color = basePalette[7];
            const ds = Array.isArray(series?.[0]) ? series[0] : [];
            config = {
            type: 'violin',
            data: {
              labels: labels || [],
              datasets: [{
                label: 'Violin Plot',
                data: ds,
                backgroundColor: asRGBA(color, 0.25),
                borderColor: color
              }]
            },
            options: {
              responsive: true,
              animation: false,
              scales: getAxisTitles(),
              plugins: { title: { display: true, text: getChartTitle(), font: { size: 16 } } }
            }
          };

        } else if (type === 'stacked-bar') {
            const lw = Livewire.first();
            const varXLabel = lw.get('varX') || 'X';
            const varYLabel = lw.get('varY') || 'Y';

            let datasets;

            // Special case: Smoking vs Cancer
            if (varXLabel === 'SMQ020' && varYLabel === 'MCQ220') {
              datasets = [
                {
                  label: 'Cancer',
                  data: series[0],
                  backgroundColor: 'rgba(255, 0, 0, 0.6)',
                  borderColor: '#ff0000',
                  borderWidth: 1
                },
                {
                  label: 'No Cancer',
                  data: series[1],
                  backgroundColor: 'rgba(0, 128, 0, 0.6)',
                  borderColor: '#008000',
                  borderWidth: 1
                }
              ];
            } else {
              const cols = asArray(series.length);
              datasets = series.map((s, i) => ({
                label: labels && labels[i] ? labels[i] : `Series ${i+1}`,
                data: s,
                backgroundColor: asRGBA(cols[i], 0.6),
                borderColor: cols[i],
                borderWidth: 1
              }));
            }
            config = {
              type: 'bar',
              data: {
                labels: labels || [],
                datasets: datasets
              },
              options: {
                responsive: true,
                animation: false,
                scales: {
                  x: { stacked: true, title: { display: true, text: varXLabel }},
                  y: {
                    stacked: true,
                    title: { display: true, text: varYLabel },
                    ticks: {
                      callback: (value) => value + '%',
                      beginAtZero: true,
                      max: 100
                    }
                  }
                },
                plugins: {
                  title: {
                    display: true,
                    text: getChartTitle(varXLabel, varYLabel),
                    font: { size: 16 }
                  },
                  tooltip: {
                    callbacks: {
                      label: function(context) {
                        return `${context.dataset.label}: ${context.parsed.y}%`;
                      }
                    }
                  }
                }
              }
            };
        } else {
          console.warn('Unknown chart type:', type, 'payload:', pl);
          return;
        }

        chartInstance = new Chart(ctx, config);
      }

      // Livewire event, normalize args (object or [object])
      if (!window.__vizListenerAdded) {
        window.__vizListenerAdded = true;

        Livewire.on('render-chart', function () {
          const allArgs = Array.from(arguments);
          let pl = null;

          if (allArgs.length === 1 && typeof allArgs[0] === 'object' && !Array.isArray(allArgs[0])) {
            pl = allArgs[0];
          } else if (allArgs.length === 1 && Array.isArray(allArgs[0]) && allArgs[0].length === 1 && typeof allArgs[0][0] === 'object') {
              pl = allArgs[0][0];
          } else {
              pl = allArgs[0];
          }

          requestAnimationFrame(() => drawChart(pl));
        });
      }

      /* ---------- Tom Select: init once, sync to hidden inputs ---------- */
      function updateURL() {
        const varX = Livewire.first().get('varX');
        const varY = Livewire.first().get('varY');
        const chart = Livewire.first().get('chartMode');

        const params = new URLSearchParams();
        if (varX) params.set('varX', varX);
        if (varY) params.set('varY', varY);
        if (chart) params.set('chartMode', chart);

        const newUrl = `${window.location.pathname}?${params.toString()}`;
        console.log('Updating URL from initTS:', newUrl);
        window.history.pushState({}, '', newUrl);
      }

      document.querySelector('select[wire\\:model\\.live="chartMode"]')?.addEventListener('change', () => {
        updateURL();
      });

      function initTS(selectId, hiddenId) {
        const el = document.getElementById(selectId);
        const hidden = document.getElementById(hiddenId);
        if (!el || !hidden) return;

        if (el.tomselect) {
          if (hidden.value && el.tomselect.getValue() !== hidden.value) {
            el.tomselect.setValue(hidden.value, true);
          }
          return;
        }

        const ts = new TomSelect(el, {
          allowEmptyOption: true,
          maxOptions: 10000,
          plugins: ['dropdown_input', 'clear_button'],
          // substring search
          score: function (search) {
            const s = (search || '').toLowerCase();
            return function (item) {
              const txt = (item.text || '').toLowerCase();
              return txt.includes(s) ? 1 : 0;
            };
          },
          onChange: (val) => {
            hidden.value = val ?? '';
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            updateURL();
          }
        });

        if (hidden.value) ts.setValue(hidden.value, true);
      }

      document.querySelector('select[wire\\:model\\.live="chartMode"]')?.addEventListener('change', () => {
        setTimeout(() => {
          updateURL();
        }, 50); // Delay to allow Livewire to update
      });


      function initAllSelects() {
        initTS('varXSelect', 'varX');
        initTS('varYSelect', 'varY');
      }

      initAllSelects();
    });
  </script>
  @endonce

  {{-- Debug values (remove in production) --}}
  <div class="text-sm text-gray-500 mt-4">
    Livewire Vars → X: {{ $varX }}, Y: {{ $varY }}, Chart: {{ $chartMode }}
  </div>
</div>