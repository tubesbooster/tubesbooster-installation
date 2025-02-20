git clone https://github.com/tubesbooster/tubesbooster-installation.git temp-repo
mv temp-repo/* temp-repo/.* . 2>/dev/null
rm -rf temp-repo

composer update 

Install supervisor and set up the worker

sudo yum install supervisor -y
sudo systemctl enable supervisord
sudo systemctl start supervisord
sudo find /etc -name supervisord.conf

/etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=your-user-name
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-your-project/worker.log

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
composer update
php artisan db:seed --class=CountrySeeder

sudo dnf install chromium -y

php settings - sudo find / -type d -name "php-fpm.d" 2>/dev/null
