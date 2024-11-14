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
	 * Splits a potentially qualified reference 'mapper:reference' into a [mapper, reference] array.
	 * @return array{?string, string}
	 */
	public static function parseReference(string $qualifiedRef): array
	{
		$parts = explode(':', $qualifiedRef, 2);
		return count($parts) === 1
			? [null, $parts[0]]
			: [$parts[0], $parts[1]];
	}


	/**
	 * Validates an array of options against allowed optional and required keys.
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
	 * Estimates the duration (in seconds) of an MP3 file, assuming constant bitrate (CBR).
	 * @throws \RuntimeException If the file cannot be opened, MP3 sync bits aren't found, or the bitrate is invalid/unsupported.
	 */
	public static function guessMP3Duration(string $path): float
	{
		if (
			($header = @file_get_contents($path, length: 10000)) === false // @ - file may not exist
			|| ($fileSize = @filesize($path)) === false
		) {
			throw new \RuntimeException(sprintf("Failed to open file '%s'. %s", $path, Nette\Utils\Helpers::getLastError()));
		}

		$frameOffset = strpos($header, "\xFF\xFB"); // 0xFB indicates MPEG Version 1, Layer III, no protection bit.
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

		return $fileSize * 8 / $bitrate / 1000;
	}
}
