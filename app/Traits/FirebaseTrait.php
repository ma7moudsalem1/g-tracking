<?php

namespace App\Traits;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase;

trait FirebaseTrait{

    public function initDatabase()
    {
        $firebase = (new Factory)
        ->withServiceAccount(base_path('config') . '/gtracking-be02c-firebase-adminsdk-1f6i9-6aa811c257.json')
        ->create();
        $db = $firebase->getDatabase();
        return $db;
    }

    public function firebaseUpdate($inputs)
    {
        $this->initDatabase()->getReference()->update($inputs);
    }

    public function firebaseCreate($collection)
    {
        return $this->initDatabase()->getReference($collection)->push()->getKey();
    }
}