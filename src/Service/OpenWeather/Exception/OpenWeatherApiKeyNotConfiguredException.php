<?php

namespace App\Service\OpenWeather\Exception;

final class OpenWeatherApiKeyNotConfiguredException extends OpenWeatherApiException
{
	public function __construct(string $message = 'OpenWeather API key is not configured', int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}