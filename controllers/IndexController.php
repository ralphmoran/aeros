<?php

namespace Controllers;

use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        // Note: All pipelines are prepended with "env('APP_NAME') . '_'" string
        // queue()->push([
        //     \Jobs\CleanupJob::class,
        //     \Jobs\SendEmailsJob::class,
        //     \Jobs\DatabaseCleanupJob::class,
        //     \Jobs\WebhookCallsJob::class,
        //     \Jobs\ProcessImagesJob::class,
        // ]);

        // Get all job statuses
        // queue()->getJobStatus();

        // Gets only job status from failed state
        // queue()->getJobStatus(Queue::FAILED_STATE);

        // Gets only job status from completed state
        // queue()->getJobStatus(Queue::COMPLETED_STATE);

        // Clears all job statuses.
        // queue()->clearJobStatus();

        // Clears only job status in failed state
        // queue()->clearJobStatus(Queue::FAILED_STATE);

        // Clears only job status in completed state
        // queue()->clearJobStatus(Queue::COMPLETED_STATE);

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

        // queue()->processPipeline('custom_pipeline');

        // Make a GET request
        // request()->get(['https://reqres.in/api/users/2')->send();

        // app()->event->emit('email.notify', ['ralph@myaero.app']);

        // db()->exec('CREATE TABLE IF NOT EXISTS projects (
        //         project_id   INTEGER PRIMARY KEY,
        //         project_name TEXT NOT NULL)')
            db()->prepare('INSERT INTO projects (project_id, project_name) VALUES(?, ?)')
            ->execute([2, 'Rafael']);

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