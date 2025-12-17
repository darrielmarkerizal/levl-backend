<?php

use Illuminate\Support\Facades\Route;
use App\Support\BrowserLogger;

Route::get("/test-browser-detect", function () {
  $deviceInfo = BrowserLogger::getDeviceInfo();
  return response()->json($deviceInfo);
});
