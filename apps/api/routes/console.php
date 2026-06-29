<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('jobs:close-expired')->daily();
