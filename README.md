<b>System files import</b>
<pre>
git clone https://github.com/tubesbooster/tubesbooster-installation.git temp-repo
rsync -a temp-repo/ . 
rm -rf temp-repo
</pre>

<b>Install dependencies</b>
<pre>
cd api
composer update
</pre>

<b>Edit files</b>
<li>input domain name to /.htaccess</li>
<li>create .env from .env.example</li>
<li>create config.js from config.js.example</li>

<b>Install supervisor and set up the worker</b>

<pre>
sudo yum install supervisor -y
sudo systemctl enable supervisord
sudo systemctl start supervisord
sudo find /etc -name supervisord.conf
</pre>

Insert this to
<pre>
/etc/supervisor/conf.d/laravel-worker.conf
</pre>
<pre>
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php {PATH}/api/artisan queue:work --sleep=10 --tries=10
autostart=true
autorestart=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile={PATH}/api/worker.log
stderr_logfile={PATH}/api/worker_error.log
stopasgroup=true
killasgroup=true
startretries=3
startsecs=30
</pre>

Restart supervisor
<pre>
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
</pre>

<b>Install composer</b>
<pre>
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
</pre>

<b>Migrate the database</b>
<pre>
php artisan migrate
php artisan db:seed
php artisan db:seed --class=SettingsTableSeeder
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
</pre>

<b>Install chromium</b>
<pre>
sudo dnf install chromium -y
</pre>

<b>Setup php settings - timeout, execution time, memory limit</b>
<pre>
sudo find / -type d -name "php-fpm.d"
</pre>
