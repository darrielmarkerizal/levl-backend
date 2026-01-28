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
      $userAgent = self::getUserAgent();
      $ipAddress = self::getClientIp();

      // If no user agent (CLI/console), return minimal info
      if (empty($userAgent)) {
        return [
          "ip_address" => $ipAddress,
          "browser" => "CLI",
          "browser_version" => null,
          "platform" => PHP_OS,
          "device" => "Server",
          "device_type" => "desktop",
        ];
      }

      // Parse user agent
      $result = Browser::parse($userAgent);

      // Get Location
      $location = \Stevebauman\Location\Facades\Location::get($ipAddress);

      return [
        "ip_address" => $ipAddress,
        "browser" => $result->browserName() ?: "Unknown",
        "browser_version" => $result->browserVersion() ?: null,
        "platform" => $result->platformName() ?: "Unknown",
        "device" => $result->deviceModel() ?: ($result->platformName() ?: "Unknown"),
        "device_type" => self::getDeviceType($result),
        "city" => $location ? $location->cityName : null,
        "region" => $location ? $location->regionName : null,
        "country" => $location ? $location->countryName : null,
      ];
    } catch (\Exception $e) {
      // Fallback if browser-detect fails
      return [
        "ip_address" => self::getClientIp(),
        "browser" => "Unknown",
        "browser_version" => null,
        "platform" => "Unknown",
        "device" => "Unknown",
        "device_type" => "desktop",
        "city" => null,
        "region" => null,
        "country" => null,
      ];
    }
  }

  /**
   * Get User-Agent from various possible headers (handles proxy scenarios)
   */
  private static function getUserAgent(): string
  {
    $request = Request::instance();

    // Try standard User-Agent header first
    $userAgent = $request->header("User-Agent");
    if (!empty($userAgent)) {
      return $userAgent;
    }

    // Try alternative headers that proxies might use
    $alternativeHeaders = [
      "X-Original-User-Agent",
      "X-Device-User-Agent",
      "X-Operamini-Phone-Ua",
      "Device-Stock-Ua",
    ];

    foreach ($alternativeHeaders as $header) {
      $value = $request->header($header);
      if (!empty($value)) {
        return $value;
      }
    }

    // Try server variables
    $serverVars = ["HTTP_USER_AGENT", "HTTP_X_ORIGINAL_USER_AGENT"];
    foreach ($serverVars as $var) {
      $value = $request->server($var);
      if (!empty($value)) {
        return $value;
      }
    }

    return "";
  }

  /**
   * Get real client IP (handles proxy scenarios)
   */
  private static function getClientIp(): string
  {
    $request = Request::instance();

    // Check for forwarded IP headers (in order of preference)
    $forwardedHeaders = [
      "X-Forwarded-For",
      "X-Real-IP",
      "CF-Connecting-IP", // Cloudflare
      "True-Client-IP", // Akamai
      "X-Client-IP",
    ];

    foreach ($forwardedHeaders as $header) {
      $value = $request->header($header);
      if (!empty($value)) {
        // X-Forwarded-For can contain multiple IPs, get the first one (original client)
        $ips = array_map("trim", explode(",", $value));
        $clientIp = $ips[0];
        if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
          return $clientIp;
        }
      }
    }

    return $request->ip() ?? "127.0.0.1";
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
