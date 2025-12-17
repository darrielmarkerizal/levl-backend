<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;
use hisorange\BrowserDetect\Facade as Browser;

class BrowserLogger
{
  /**
   * Get browser and device information from request
   */
  public static function getDeviceInfo(): array
  {
    try {
      $userAgent = Request::header("User-Agent") ?? (Request::userAgent() ?? "");

      // If no user agent (CLI/console), return minimal info
      if (empty($userAgent)) {
        return [
          "ip_address" => Request::ip() ?? "127.0.0.1",
          "browser" => "CLI",
          "browser_version" => null,
          "platform" => PHP_OS,
          "device" => "Server",
          "device_type" => "desktop",
        ];
      }

      // Parse user agent
      $result = Browser::parse($userAgent);

      return [
        "ip_address" => Request::ip() ?? "127.0.0.1",
        "browser" => $result->browserName() ?: "Unknown",
        "browser_version" => $result->browserVersion() ?: null,
        "platform" => $result->platformName() ?: "Unknown",
        "device" => $result->deviceModel() ?: ($result->platformName() ?: "Unknown"),
        "device_type" => self::getDeviceType($result),
      ];
    } catch (\Exception $e) {
      // Fallback if browser-detect fails
      return [
        "ip_address" => Request::ip() ?? "127.0.0.1",
        "browser" => "Unknown",
        "browser_version" => null,
        "platform" => "Unknown",
        "device" => "Unknown",
        "device_type" => "desktop",
      ];
    }
  }

  /**
   * Determine device type
   */
  private static function getDeviceType($result): string
  {
    if ($result->isMobile()) {
      return "mobile";
    }

    if ($result->isTablet()) {
      return "tablet";
    }

    if ($result->isDesktop()) {
      return "desktop";
    }

    return "unknown";
  }
}
