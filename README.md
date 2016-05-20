SIMPSON
=======

A social networking study platform.

Installation Instructions
-------------------------

SIMPSON is split into two components. Both of these must be installed to run your own instance of SIMPSON.

- The <b>Core</b> component, written in PHP, consists of the main web application. The installation instructions are [here](/core)
- The <b>Realtime</b> component, written in NodeJS, enables realtime notifications. The installation instructions are [here](/realtime)

Running a Study
---------------

Once you have your own development server up and running, you can easily run your own SIMPSON study. The artisan command line tool has been augmented with four custom commands to set up both a demo study and custom study.

To see all available artisan commands run the following

```
cd Simpson/core
php artisan list
```

Laravel provides many commands, but only four are relevant to SIMPSON. Those are: `demo:create`, `demo:destroy`, `study:create`, and `study:destroy`.

To create a demo study, run:

```
cd Simpson/core
php artisan demo:create
```

This will create a study with twenty users, forty questions, and random connections and requests. To see this live, ensure both the core server and realtime server are running (see installation instructions). I.e. make sure you run
```
cd Simpson/core
php artisan serve
```
Which will start the core server, and then run
```
cd Simpson/realtime
npm start
```
Which will start the realtime server.

Then, going to http://localhost:8000 should bring up the SIMPSON login screen. Log in as the demo user to see the demo project. Note, this begins the countdown timer for the demo user, which expires in 60 minutes. If you want to recreate the demo study, first destroy it with:

```
php artisan demo:destroy
php artisan demo:create
```

### Creating a Custom Study ###
A custom study is created using a configuration JSON file. See [this example configuration file](example-input.json) to reference. Some clarifications and remarks are as follows:

- <b>projectName</b> must be a unique name. This is used to identify the study in case you wish to delete it.
- <b>timeout<b> is the number of seconds a user has to complete the task
- <b>user.name</b> and <b>user.password</b> can both be optionally specified with a "?", meaning it will be randomly generated
- <b>user.identifier</b> is not stored internally, but is used to reference users for specifiying <b>connections</b>

To create the study using this configuration file, run the following command:

```
php artisan study:create --infile example-input.json
```

After a study is created, it will generate an output configuration file with the missing fields filled. Using this, you can retrieve the generated emails, passwords, and names of all users created for the study. In the example above, the file `example-input.json.out` will be created. Using this information, you can log in as a user. Note, once you do this, the timer for this user will start.

In the event of a mistake, you can destroy the study <i>with its data</i> using
```
php artisan study:destroy --name "StudyName"
```

Only run this in the event that you are certain you wish to delete all project data. It is unlikely you will need to do this unless you made an initial configuration mistake. Having extra data is always better than losing it!

