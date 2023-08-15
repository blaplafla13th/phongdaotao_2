composer install --no-cache
composer dump-autoload
if [ ! -f ".env" ]
then
    cp .env.example .env
    php artisan config:clear
    php artisan config:cache
    php artisan optimize
    php artisan key:generate
    php artisan jwt:secret
    php artisan migrate
    php artisan db:seed
    php artisan l5-swagger:generate
fi
php artisan serve --host=0.0.0.0 --port=80
