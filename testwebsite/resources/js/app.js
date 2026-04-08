import './bootstrap';
import Alpine from 'alpinejs';

import {
  Chart,
  CategoryScale,
  LinearScale,
  BarController,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  ScatterController,
  PieController,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';

import {
  BoxPlotController,
  BoxAndWiskers,
  ViolinController,
  Violin
} from '@sgratzl/chartjs-chart-boxplot';

// Register everything needed for your charts
Chart.register(
  // Scales
  CategoryScale,
  LinearScale,

  // Elements & Controllers
  BarController,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  ScatterController,
  PieController,
  ArcElement,

  // BoxPlot
  BoxPlotController,
  BoxAndWiskers,

  ViolinController,
  Violin,

  // Extras
  Title,
  Tooltip,
  Legend
);

window.Chart = Chart;

window.Alpine = Alpine;
Alpine.start();