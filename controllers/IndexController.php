<?php

namespace Controllers;

use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        // Note: All pipelines are prepended with "env('APP_NAME') . '_'" string

        queue()->push([
            \Jobs\CleanupJob::class,
            \Jobs\SendEmailsJob::class,
            \Jobs\DatabaseCleanupJob::class,
            \Jobs\WebhookCallsJob::class,
            \Jobs\ProcessImagesJob::class,
        ]);

        // Using a specific pipeline name
        queue()->push(
            [
                \Jobs\CleanupJob::class,
                \Jobs\SendEmailsJob::class,
                \Jobs\DatabaseCleanupJob::class,
                \Jobs\WebhookCallsJob::class,
                \Jobs\ProcessImagesJob::class,
            ],
            'custom_pipeline'
        );

        queue()->processPipeline('custom_pipeline');

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