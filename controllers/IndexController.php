<?php

namespace Controllers;

use Roles\SuperRole;
use Models\User;
use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        //******************************************/
        // Note: All pipelines are prepended with "env('APP_NAME') . '_'" string
        // queue()->push([
        //     \Jobs\CleanupJob::class,
        //     \Jobs\SendEmailsJob::class,
        //     \Jobs\DatabaseCleanupJob::class,
        //     \Jobs\WebhookCallsJob::class,
        //     \Jobs\ProcessImagesJob::class,
        // ]);

        //******************************************/
        // Get all job statuses
        // queue()->getJobStatus();

        //******************************************/
        // Gets only job status from failed state
        // queue()->getJobStatus(Queue::FAILED_STATE);

        //******************************************/
        // Gets only job status from completed state
        // queue()->getJobStatus(Queue::COMPLETED_STATE);

        //******************************************/
        // Clears all job statuses.
        // queue()->clearJobStatus();

        //******************************************/
        // Clears only job status in failed state
        // queue()->clearJobStatus(Queue::FAILED_STATE);

        // Clears only job status in completed state
        // queue()->clearJobStatus(Queue::COMPLETED_STATE);

        //******************************************/
        // Using a specific pipeline name
        // queue()->push(
        //     [
        //         \Jobs\CleanupJob::class,
        //         \Jobs\SendEmailsJob::class,
        //         \Jobs\DatabaseCleanupJob::class,
        //         \Jobs\WebhookCallsJob::class,
        //         \Jobs\ProcessImagesJob::class,
        //     ],
        //     'custom_pipeline'
        // );

        //******************************************/
        // queue()->processPipeline('custom_pipeline');

        //******************************************/
        // Make a GET request
        // request()->get(['https://reqres.in/api/users/2')->send();

        // app()->event->emit('email.notify', ['ralph@myaero.app']);

        //******************************************/
        // Create projects table. You must use "exec" method for these type of queries
        $stm = db()->exec('CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
                username TEXT NOT NULL,
                fname TEXT NOT NULL,
                lname TEXT NOT NULL)');

        // dd(get_class($stm));

        //******************************************/
        // Inserting rows
        // $stm = db()->prepare('INSERT INTO projects (project_id, project_name) VALUES(?, ?)')
        //     ->execute([
        //         mt_rand(1, 1000), 
        //         'Rafael'
        //     ]);

        // dd($stm->rowCount()); // Returns the number of inserted records

        //******************************************/
        // Fetching data with named placeholders
        // dd(db()->prepare("SELECT * FROM projects WHERE project_id = :id")
        //     ->execute([
        //         'id' => 439
        //     ])
        //     ->fetchAll()
        // );

        //******************************************/
        // Bring multiple records with placeholders (associative array)
        // dd(db()->prepare("SELECT * FROM users WHERE id = ?")
        //     ->execute([
        //         1
        //     ])
        //     ->fetchAll()
        // );

        //******************************************/
        // NOTE: When working with models, it is IMPERATIVE that related tables have an "id" column
        //       as primary key and auto-increment properties

        // Multiple inserts
        // $users = [
        //     [
        //         'username' => 'username' . rand(1, 10),
        //         'fname' => 'fname' . rand(1, 10),
        //         'lname' => 'lname' . rand(1, 10),
        //     ],
        //     [
        //         'username' => 'username' . rand(1, 10),
        //         'fname' => 'fname' . rand(1, 10),
        //         'lname' => 'lname' . rand(1, 10),
        //     ],
        //     [
        //         'username' => 'username' . rand(1, 10),
        //         'fname' => 'fname' . rand(1, 10),
        //         'lname' => 'lname' . rand(1, 10),
        //     ],
        //     [
        //         'username' => 'username' . rand(1, 10),
        //         'fname' => 'fname' . rand(1, 10),
        //         'lname' => 'lname' . rand(1, 10),
        //     ],
        // ];

        // $stm = db()->prepare("INSERT INTO users (username, fname, lname) VALUES (:username, :fname, :lname)");

        // db()->beginTransaction();
        
        // foreach ($users as $user) {
        //     $stm->execute($user);
        //     // $stm->lastInsertId(); // It gives you the last inserted ID
        // }
        
        // db()->commit();

        //******************************************/
        // Or, use User::createMany($users) instead. 
        // It will return a list of recent inserted records as User objects
        // $newUsers = User::createMany($users);

        // dd($newUsers);

        //******************************************/
        // Create a new User model
        // $newUser = User::create([
        //     // 'id' => 12, // Primary key cannot be modified
        //     'date' => 'New user', // It does not exist. It will be ignored
        //     'username' => 'username',
        //     'fname' => 'fname',
        //     'lname' => 'lname', // If this column is guarde, it will be ignored
        // ]);

        // dd($newUser);

        //******************************************/
        // Find only one user
        $user = User::find(4);

        //******************************************/
        // Get a list of users. Pay attention to this format, it will return an array of user objects.
        // $user = User::find([
        //     ['id', '=', 1, 'OR'],
        //     // ['username', '<>', 'Rafael'],
        // ]);

        //******************************************/
        // Delete a user
        // $user->delete()->commit();

        //******************************************/
        // Update one property
        $user->username = 'Here was Natalia - ' . rand(1, 99);
        // $user->lname = 'Guarded' . rand(1, 99); // It throws an error. This column is guarded
        $user->save();

        //******************************************/
        // Update many properties at once
        // $user->update([
        //     'username' => 'Last update',
        //     'fname' => 'Last update',
        //     'lname' => 'Last update',
        // ])->commit();

        dd($user);

        dd(SuperRole::value());

        return view('index');
    }

    public function list(int $userid)
    {
        return view('index', ['userid' => $userid]);
    }

    public function showProfile()
    {
        return 'Profile';
    }

    public function anotherProfile()
    {
        return 'Another Profile';
    }
}