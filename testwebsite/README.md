# NVisualiser 2.0
The NVisualiser 2.0 project serves as a web-based application that integrates with Python scripts and uses MySQL for database management. The project allows users to perform dataset downloads, manipulate data, and manage data availability through a web interface.

# Prerequisites
Ensure you have the following installed on your system before proceeding:
<p>Laravel Framework: https://laravel.com/</p>
<p>Composer (dependency management for PHP) https://getcomposer.org/</p>
<p>Python 3.x: https://www.python.org/downloads/</p>
<p>XAMPP or WAMP or DOCKER (for local PHP development environment)</p>

Node.js: https://nodejs.org/en/download/prebuilt-installer
Git: https://git-scm.com/downloads/

# Installation
* Clone this repository locally on your system
* Navigate to your local version of this repository
* Run ```composer install``` and ```npm install```
* Set up the environment file by copying example.env into .env and making suitable changes
* Run ```npm run dev```

# Hosting With Docker (Recommended)
* Copy the contents of ```example_Dockerfile``` into ```vendor/laravel/sail/runtimes/<latest-php-version>/Dockerfile```
* Run ```docker-compose up -d --build```
* Host the web application by running ```./vendor/bin/sail up```
* Scrape NHANES datasets by running ```./vendor/bin/sail artisan migrate --seed``` (generates database tables)
* When finished you can stop hosting by running ```./vendor/bin/sail down```

# Hosting With XAMPP (Needs Debugging)
* Create a database called testwebsite
* If its your first time running, then run php artisan key:generate
* Navigate to the scripts/ directory and install required Python packages: pip3 install -r requirements.txt
* Host the web application by running ```php artisan serve```
* Scrape NHANES datasets by running ```php artisan migrate --seed```

