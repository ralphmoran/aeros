<?php

namespace Controllers;

use Classes\ControllerBase;
use Models\CreateLocalDBModel as CreateLocalDB;

class CreateLocalDBController extends ControllerBase
{
    public function index()
    {
        if (env('APP_ENV') === 'development') {
            ob_start();
            
            (CreateLocalDB::getInstance())
                ->create_local_db()
                ->build_tables()
                // ->refresh() # TODO: DummyData.sql contains character that break the import action
                ->add_test_user()
                ->close();

            $details = ob_get_clean();

            return view('common.errors.codes', [
                'code'    => strpos($details, 'ERROR') ? '417 - Expectation failed' : '200 - All good',
                'message' => 'Next, you can see the status of creating "aero_local_db" and importing tables:',
                'details' => $details,
            ]);
        }
    }
}
