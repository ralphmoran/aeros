<?php

namespace Controllers;

use Classes\ControllerBase;

class AppController extends ControllerBase
{
    public function index()
    {
        // Trigger email notify event
        // app()->event->emit('email.notify', ['ralph@myaero.app']);

        // more logic...

        // Trigger email reminder event
        // app()->event->emit('email.reminder', ['adam@myaero.app']);

        // more logic...

        // Trigger email follow up event
        // app()->event->emit('email.followup', ['ben@myaero.app', 'andy@myaero.app']);

        // more logic...

        // app()->worker->create('great-worker');

        // return view('app');

        return 'Index';
    }

    public function login()
    {
        return 'Pasa';
    }
    
    public function profile(int $userid, string $profile)
    {
        return 'Profile';
    }

    public function showForm()
    {
        return 'Show form';
    }
}
