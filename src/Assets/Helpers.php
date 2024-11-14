<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette;


/**
 * Static helper class providing utility functions for working with assets.
 */
final class Helpers
{
	use Nette\StaticClass;

	/**
	 * Validates array of options against allowed optional and required keys.
	 * @throws \InvalidArgumentException if there are unsupported or missing options
	 */
	public static function checkOptions(array $array, array $optional = [], array $required = []): void
	{
		if ($keys = array_diff(array_keys($array), $optional, $required)) {
			throw new \InvalidArgumentException('Unsupported asset options: ' . implode(', ', $keys));
		}
		if ($keys = array_diff($required, array_keys($array))) {
			throw new \InvalidArgumentException('Missing asset options: ' . implode(', ', $keys));
		}
	}


	/**
	 * Returns the duration of an MP3 file (in seconds) with constant bitrate.
	 * @throws \RuntimeException if file cannot be opened or is not a valid MP3
	 */
	public static function getMP3Duration(string $path): int
	{
		if (
			($header = @file_get_contents($path, length: 10000)) === false // @ - file may not exist
			|| ($fileSize = @filesize($path)) === false
		) {
			throw new \RuntimeException(sprintf("Failed to open file '%s'. %s", $path, Nette\Utils\Helpers::getLastError()));
		}

		$frameOffset = strpos($header, "\xFF\xFB"); // sync bits
		if ($frameOffset === false) {
			throw new \RuntimeException('Failed to find MP3 frame sync bits.');
		}

		$frameHeader = substr($header, $frameOffset, 4);
		$headerBits = unpack('N', $frameHeader)[1];
		$bitrateIndex = ($headerBits >> 12) & 0xF;
		$bitrate = [null, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320][$bitrateIndex] ?? null;
		if ($bitrate === null) {
			throw new \RuntimeException('Invalid or unsupported bitrate index.');
		}

		return (int) round($fileSize * 8 / $bitrate / 1000);
	}
}
