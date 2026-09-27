// routes/console.php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:reconcile')->hourly();

