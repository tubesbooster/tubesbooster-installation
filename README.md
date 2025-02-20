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
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=your-user-name
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-your-project/worker.log
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
</pre>

<b>Install chromium</b>
<pre>
sudo dnf install chromium -y
</pre>

<b>Setup php settings - timeout, execution time, memory limit</b>
<pre>
sudo find / -type d -name "php-fpm.d"
</pre>
