## Simpson Core ##

Simpson Core is the main web application and API. 

### Installing on OSX and Linux ###

#### Setting up the Environment ####
SIMPSON is built with the PHP framework [Laravel](http://laravel.com/). Before setting up SIMPSON, make sure your environment meets the server requirements of Laravel. Make sure you have PHP of at least version 5.5.9 and a relational database server (we recommend MySQL).

For Ubuntu 14.04, DigitalOcean has a [great article](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-14-04) on installing Apache, PHP, and MySQL.

For OSX, we recommend installing [MAMP](https://www.mamp.info/en/). This comes with Apache, PHP, and MySQL.

#### Install Composer ####
Composer, the PHP package manager, is used to automatically fetch dependencies. The full documentation is located on [Composer's website](https://getcomposer.org/). Copied from their instructions, you can simply run the following in the terminal:
```
curl -sS https://getcomposer.org/installer | php
```

This will create the composer.phar script in the current directory. The composer.phar file is the main composer script. We recommend that it is moved to /usr/local/bin so it can be run from any directory and renamed to just 'composer' for simplicity. The rest of the instructions will assume you've done this. This can be done by running:
```
sudo mv composer.phar /usr/local/bin/composer
```

Verify that composer is installed by running `composer` in the terminal.

#### Setting up SIMPSON ####
Now that your environment is ready, get a copy of the SIMPSON repository. You can download a zip file or if you have `git` installed you can run the following:

```
git clone git@github.com:InfoSeeking/Simpson.git
```

This will create the SIMPSON directory with the source code. Now, we need to install the dependencies with composer as follows:

```
cd Simpson/core
composer install
```

After this runs, all of the project dependencies should be installed.

Now we need to tell Laravel about your development environment. In the Simpson/core directory, there is a [.env.example](https://github.com/InfoSeeking/Simpson/blob/master/core/.env.example) file. Rename this file to .env (without the .example) as follows.

```
mv .env.example .env
```

The .env file ignored by git for security reasons, so it needs to be manually created on installation. Change the DB values in the .env file to match your database setup. The [Laravel environment documentation](http://laravel.com/docs/5.1#environment-configuration) has more information on configuring to match your environment.

To finalize the environment setup, you should set APP\_KEY in .env to a random 32 character string. Laravel provides a shortcut to doing this. While in the Simpson/core directory in the terminal, run the following.
```
php artisan key:generate
```
This will automatically set the APP\_KEY in your .env file.

Lastly, we need to import the database schema for SIMPSON. While in Simpson/core, run the following.

```
php artisan migrate
```
This should create all of the necessary database tables for Simpson.

Now you should be ready to go. Run
```
php artisan serve
```
To run the Laravel test server. This should say something like `Laravel development server started on http://localhost:8000/`. Go to `http://localhost:8000` in your web browser to see the SIMPSON landing page.