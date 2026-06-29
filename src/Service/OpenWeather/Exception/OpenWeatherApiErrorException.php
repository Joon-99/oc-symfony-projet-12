<?php

namespace App\Service\OpenWeather\Exception;

final class OpenWeatherApiErrorException extends OpenWeatherApiException
{
	public function __construct(string $message = 'OpenWeather API returned an error response', int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}