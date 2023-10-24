<?php

namespace Controllers;

use Classes\ControllerBase;

class IndexController extends ControllerBase
{
    public function index()
    {
        app()->queue->push([
            'CleanupJob'         => new \Jobs\CleanupJob(),
            'SendEmailsJob'      => new \Jobs\SendEmailsJob(),
            'DatabaseCleanupJob' => new \Jobs\DatabaseCleanupJob(),
            'WebhookCallsJob'    => new \Jobs\WebhookCallsJob(),
            'ProcessImagesJob'   => new \Jobs\ProcessImagesJob(),
        ]);

        // It processes the defualt pipeline
        app()->queue->processPipeline();

        

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