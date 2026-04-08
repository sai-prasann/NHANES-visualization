# NVisualiser 2.0 - NHANES Data Visualization Web Application

## Description
NVisualiser 2.0 is a web-based applications developed to analyze and visualize data from the NHANES dataset. It allows user to explore blood-related health indicators such as glucose, cholesterol and haemoglobin using interactive charts and statistical insights.

The system integrates data extraction, processing, visualization and statistical analysis into a single platform, making complex healthcare data easy to understand.

---

## Features
- Interactive data visualizations (Scatter, Bar, Line, Histogram, Box, Violin, Stacked Bar)
- Dashboard for dataset management
- Automated data scraping and processing
- Statistical insights (mean, median, correlation, odds ration)
- Dynamic variables selection
- Shareable visualization links

---

## Tech Stack
- Backend: Laravel (PHP)
- Frontend: HTML, CSS, JavaScript, Chart.js
- Database: MySQL
- Data Processing: Python (Pandas, SQLAlchemy)
- Tools: XAMP, Composer, Node.js

---

## Prerequirments
- PHP (XAMPP recommended)
- Composer
- Node.js
- MySQL
- Laravel

---

## Setup Instructions

1. Clone or download the project
2. create a `.env` file in the project root and add:
       DB_CONNECTION=mysql
       DB_HOST=127.0.0.1
       DB_PORT=3306
       DB_DATABASE=your_database_name
       DB_USERNAME=root
       DB_PASSWORD=
3. Install dependencies:
       composer install
       npm instal
4. Build frontend:
       npm run build
5. Generate application key:
       php artisan key: generate
6. Run the project:
       php artisan serve
7. open in browser:
        http://127.0.0.1:8000
---

## Important Note
After downloading from GitHub, always run:
       composer install
       npm install
       npm run build
---

## Functionality Overview
- Web Scraping: Extracts NHANES datasets automatically
- Dashboard: Manage and download datasets
- Visualization: Generate charts dynamically
- Insights: Provides statistical interpretation

---

## Author
Sai Prasanna Cheedi

---
