#!/usr/bin/env ash
set -e

initialStuff() {
    php artisan optimize:clear
    php artisan package:discover --ansi
    php artisan config:cache
}

initialStuff

php artisan schedule:work
