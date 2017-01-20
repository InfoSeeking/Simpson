SIMPSON
=======

A social networking study platform.

Running a Study
---------------
### Quickstart ###
In this repo is an example study named represented by 'easy-input.json'. This has all available options for a SIMPSON study filled in. It should be possible to model real studies from the format of this file.

To create this example study, run
```
cd core
php artisan study:create --infile easy-input.json
```

This creates a study named "StudyName" with four scenarios. Check `easy-input.json.out` for the generated user emails and log in as a user. Notice, the study has not yet started, so the user will not be shown the button to continue until the first scenario is started.

To see the status of all scenarios, run:
```
php artisan study:info --name "StudyName"
```

This lists all of the scenarios of the project and their respective states. The first scenario is in\_queue. To start it, run:
```
php artisan study:advance --name "StudyName"
```

Note, advancing the scenarios is not currently possible to undo. If you run `study:info` again, you'll see that the first scenario has started while the second is now in\_queue.

Running `study:advance` again will end the first scenario. The second scenario will stay in queue, this time can be used to give the users a short break in between scenarios. To start the next scenario, use the `study:advance` command again. Continue in fashion until the study is over.

To delete this study, run:
```
php artisan study:destroy --name "StudyName"
```

### Command Reference ###
To see all available artisan commands run the following

```
cd Simpson/core
php artisan list
```

Laravel provides many commands, but only commands starting with "study" are relevant to SIMPSON. Specifically those commands are:

`study:create --infile <filename.json>` - Create a study from a provided JSON configuration file

`study:destroy --name "<study name>"` - Delete a study by referring to the name

`study:info --name "<study name>"` - Get information about the state of the scenarios in the study

`study:advance --name "<study name>"` - Advance the scenarios of a specific study

`study:activate --name "<study name>"` - Activate a study

`study:deactivate --name "<study name>"` - Deactivate a study


### JSON Configuration Options ###
A custom study is created using a configuration JSON file. See [this example configuration file](core/easy-input.json) to reference. Some clarifications and remarks are as follows:

- <b>projectName</b> must be a unique name. This is used to identify the study in case you wish to delete it.
- <b>timeout</b> is the number of seconds a user has to complete the task
- <b>user.name</b> and <b>user.password</b> can both be optionally specified with a "?", meaning it will be randomly generated
- <b>user.identifier</b> is not stored internally, but is used to reference users for specifiying <b>connections</b>
- <b>active</b> is true/false indicating whether or not the study will be accessible by users. This can be changed with the study:activate and study:deactivate commands
- <b>description</b> is the study description. This appears on the instructions page below the scoring chart. This is a good spot to indicate when the study opens up for reference.

To create the study using this configuration file, run the following command:

```
php artisan study:create --infile easy-input.json
```

After a study is created, it will generate an output configuration file with the missing fields filled. Using this, you can retrieve the generated emails, passwords, and names of all users created for the study. In the example above, the file `example-input.json.out` will be created. Using this information, you can log in as a user. Note, once you do this, the timer for this user will start.

In the event of a mistake, you can destroy the study <i>with its data</i> using
```
php artisan study:destroy --name "StudyName"
```

Only run this in the event that you are certain you wish to delete all project data. It is unlikely you will need to do this unless you made an initial configuration mistake. Having extra data is always better than losing it!

### Activating and Deactivating Studies ###
If a study is <i>active</i> then users can log in and take the study. If a study is <i>inactive</i> the instructions page will say the project is currently inactive and will not show links to take the study. You can specify in the json configuration whether the study is active or not and you can modify this with the following commands:


```
php artisan study:activate --name "StudyName"
php artisan study:deactivate --name "StudyName"
```

Database
--------
It is worth noting that because of older terminology, a "project" in the database really means a scenario.

All tables have a `created_at` and `updated_at` field. The `created_at` field is particularly useful as it is the time the record was inserted.

The following is a list of tables with a description.
- <b>answers</b> has an entry for each user and each answer regardless of whether the answer was obtained. The boolean `answered` field indicates if the user obtained that answer. There is a many-to-one relationship with the <b>answers</b> table and the <b>questions</b> table.
- <b>connections</b> records who initiated the connection, if there was an intermediary, and who was the recipient. `intermediary_id` is null if no intermediary was used. The `created_at` field can be used to determine when the connection was made.
- <b>logs</b> is a dumping ground for log data, including score changes, when answers were recieved, and if/when the user finished answering all questions. It simply stores a key/value pair and other context data. Here is a list of all possible values of 'key':
    - <b>finished</b> indicates the user obtained the last answer (will not necessarily happen)
    - <b>score_change</b> indicates the amount when a score of the user is changed in any way
    - <b>answer_get</b> indicates the user obtained an answer from another user
    - <b>answer_unfulfilled</b> indicates the user asked but did not get an answer from another user
    - <b>login</b> indicates when the user logged in
- <b>memberships</b> maps users to scenarios (note, scenario == project)
- <b>migrations</b> and <b>password_resets</b> are Laravel specific tables
- <b>projects</b> contains all scenarios. The `nextProject` and `prevProject` fields indicate the ordering.
- <b>requests</b> contains rows for each time a user sends a request for a connection or answer.
- <b>scores</b> contains scores for each user <i>for each scenario</i>. To get the accumulated score of a user over all of the scenarios, you need to sum all their scores for each scenario.
- <b>users</b> contains a record for each user


To view the exact schema, use the `describe` command in MySQL, e.g. `describe answers` should give
```
+-------------+------------------+------+-----+---------------------+----------------+
| Field       | Type             | Null | Key | Default             | Extra          |
+-------------+------------------+------+-----+---------------------+----------------+
| id          | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| user_id     | int(10) unsigned | NO   |     | NULL                |                |
| name        | varchar(255)     | NO   |     | NULL                |                |
| created_at  | timestamp        | NO   |     | 0000-00-00 00:00:00 |                |
| updated_at  | timestamp        | NO   |     | 0000-00-00 00:00:00 |                |
| deleted_at  | timestamp        | YES  |     | NULL                |                |
| project_id  | int(10) unsigned | NO   |     | NULL                |                |
| answered    | int(10) unsigned | NO   |     | 0                   |                |
| question_id | int(10) unsigned | NO   |     | NULL                |                |
| position    | int(10) unsigned | NO   |     | NULL                |                |
+-------------+------------------+------+-----+---------------------+----------------+
```

To get a CSV backup, use the `mysqldump` command as follows:

```
mysqldump -u<username> -p --tab=<dump directory> simpson
```

This will output two files per table, one .sql file and one .txt tab separated file.

Tips
----

- Do not destroy projects if they have been used. This removes all data associated with them.
- Only advance scenarios at the scheduled time. If a scenario has been finished before the scenario timer is up, users will not be aware of this until the timer expires.
- Database oddities:
	- "Scenarios" are in the projects table
	- There is a copy of the score for each scenario in the scores table. The score associated with the last scenario is the final score of the user.