<?php

namespace App\Service\OpenWeather\Exception;

final class OpenWeatherApiUnavailableException extends OpenWeatherApiException
{
	public function __construct(string $message = 'OpenWeather API is unavailable', int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}