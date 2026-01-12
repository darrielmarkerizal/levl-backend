# CI Testing Only - No Web Hosting
# This Procfile is used by Heroku CI for running tests
# Actual hosting is on VPS

release: php artisan key:generate --force --no-interaction && php artisan migrate --force

