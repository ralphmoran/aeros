<?php

namespace Controllers;

use Classes\ControllerBase;

class AppController extends ControllerBase
{
    public function index()
    {
        // Trigger email notify event
        app()->event->emit('email.notify', ['ralph@myaero.app']);

        // more logic...

        // Trigger email reminder event
        app()->event->emit('email.reminder', ['adam@myaero.app']);

        // more logic...

        // Trigger email follow up event
        app()->event->emit('email.followup', ['ben@myaero.app', 'andy@myaero.app']);

        // more logic...

        return view('app');
    }
}
