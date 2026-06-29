<?php

namespace App\Service\OpenWeather\Exception;

final class OpenWeatherApiZipNotFoundException extends OpenWeatherApiException
{
	public function __construct(string $message = 'No city found in OpenWeather for the provided zip code', int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}