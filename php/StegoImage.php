<?php
/**
 * StegoImage — pure-PHP steganography helper.
 *
 * Supports embedding / extracting UTF-8 text in PNG (iTXt chunk) and
 * JPEG (COM / FFFE segment), replicating the behaviour of the former
 * Python image_text_stego.py script used by this project.
 *
 * PNG strategy (mirrors Python "resize" mode):
 *   Append a new iTXt chunk with keyword "stegotext" just before IEND.
 *   On extract, scan all iTXt chunks and return the first match.
 *
 * JPEG strategy (mirrors Python "resize" mode):
 *   Prepend a COM (FF FE) segment immediately after the SOI (FF D8) marker.
 *   On extract, scan markers and return the first COM segment content.
 */
class StegoImage
{
	private const PNG_SIG     = "\x89PNG\r\n\x1a\n";
	private const PNG_KEYWORD = 'stegotext';

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Embed $text into the image file at $path (overwrites the file).
	 *
	 * @throws RuntimeException on unsupported format or I/O failure
	 */
	public static function embed(string $path, string $text): void
	{
		$data = file_get_contents($path);
		if ($data === false) {
			throw new RuntimeException("Cannot read file: $path");
		}

		if (self::isPng($data)) {
			$out = self::embedPng($data, $text);
		} elseif (self::isJpeg($data)) {
			$out = self::embedJpeg($data, $text);
		} else {
			throw new RuntimeException("Unsupported image format (only PNG/JPEG supported)");
		}

		if (file_put_contents($path, $out) === false) {
			throw new RuntimeException("Cannot write file: $path");
		}
	}

	/**
	 * Extract embedded text from the image file at $path.
	 *
	 * @return string|null  The extracted text, or null if nothing was found.
	 * @throws RuntimeException on unsupported format or I/O failure
	 */
	public static function extract(string $path): ?string
	{
		$data = file_get_contents($path);
		if ($data === false) {
			throw new RuntimeException("Cannot read file: $path");
		}

		if (self::isPng($data)) {
			return self::extractPng($data);
		} elseif (self::isJpeg($data)) {
			return self::extractJpeg($data);
		} else {
			throw new RuntimeException("Unsupported image format (only PNG/JPEG supported)");
		}
	}

	// -------------------------------------------------------------------------
	// Format detection
	// -------------------------------------------------------------------------

	private static function isPng(string $data): bool
	{
		return strncmp($data, self::PNG_SIG, 8) === 0;
	}

	private static function isJpeg(string $data): bool
	{
		return strlen($data) >= 2 && $data[0] === "\xFF" && $data[1] === "\xD8";
	}

	// -------------------------------------------------------------------------
	// PNG helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a PNG iTXt chunk carrying keyword + text (uncompressed, no lang tag).
	 * Layout:  keyword NUL 0x00 0x00 NUL NUL UTF-8-text
	 */
	private static function buildPngITXtChunk(string $text, string $keyword = self::PNG_KEYWORD): string
	{
		$chunkType = 'iTXt';
		// compression flag = 0, compression method = 0, lang tag = "", translated keyword = ""
		$chunkData = $keyword . "\x00" . "\x00\x00" . "\x00" . "\x00" . $text;
		$length    = pack('N', strlen($chunkData));
		$crc       = pack('N', crc32($chunkType . $chunkData));
		return $length . $chunkType . $chunkData . $crc;
	}

	/** Append an iTXt chunk just before IEND. */
	private static function embedPng(string $png, string $text): string
	{
		$sig = self::PNG_SIG;
		$pos = 8; // skip PNG signature
		$n   = strlen($png);
		$out = $sig;

		while ($pos + 12 <= $n) {
			$length    = unpack('N', substr($png, $pos, 4))[1];
			$chunkType = substr($png, $pos + 4, 4);
			$chunkEnd  = $pos + 12 + $length; // pos + 4(len) + 4(type) + length + 4(crc)

			if ($chunkType === 'IEND') {
				// insert new iTXt chunk before IEND
				$out .= self::buildPngITXtChunk($text);
			}
			$out .= substr($png, $pos, 12 + $length);
			$pos = $chunkEnd;
		}
		return $out;
	}

	/** Extract the first iTXt "stegotext" chunk value. */
	private static function extractPng(string $png): ?string
	{
		$pos = 8;
		$n   = strlen($png);

		while ($pos + 12 <= $n) {
			$length    = unpack('N', substr($png, $pos, 4))[1];
			$chunkType = substr($png, $pos + 4, 4);
			$dataStart = $pos + 8;
			$dataEnd   = $dataStart + $length;

			if ($dataEnd + 4 > $n) break;

			if ($chunkType === 'iTXt') {
				$raw = substr($png, $dataStart, $length);
				// keyword is everything up to the first NUL
				$nul = strpos($raw, "\x00");
				if ($nul === false) { $pos = $dataEnd + 4; continue; }
				$keyword = substr($raw, 0, $nul);
				// skip: NUL compressionFlag(1) compressionMethod(1) lang(NUL-term) translatedKeyword(NUL-term)
				$i = $nul + 1 + 2; // past compressionFlag and compressionMethod
				$nul2 = strpos($raw, "\x00", $i); // end of lang tag
				$i = ($nul2 !== false ? $nul2 : $i) + 1;
				$nul3 = strpos($raw, "\x00", $i); // end of translated keyword
				$i = ($nul3 !== false ? $nul3 : $i) + 1;
				$value = rtrim(substr($raw, $i), "\x00");
				if ($keyword === self::PNG_KEYWORD || $keyword === '') {
					return $value !== '' ? $value : null;
				}
			}

			if ($chunkType === 'IEND') break;
			$pos = $dataEnd + 4;
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// JPEG helpers
	// -------------------------------------------------------------------------

	/**
	 * Prepend a COM (FF FE) segment right after SOI.
	 * Any existing COM segment from a prior embed is replaced so we don't
	 * accumulate multiple COM segments on re-embed.
	 */
	private static function embedJpeg(string $jpg, string $text): string
	{
		$encoded = $text; // UTF-8 as-is
		if (strlen($encoded) > 65533) {
			throw new RuntimeException('Text too long for JPEG COM segment (max 65533 bytes)');
		}
		$segLen  = strlen($encoded) + 2; // segment length field includes the 2 length bytes
		$comSeg  = "\xFF\xFE" . pack('n', $segLen) . $encoded;

		// Strip any existing COM segments first so re-embeds don't stack
		$stripped = self::stripJpegCom($jpg);
		// Insert new COM right after SOI (first 2 bytes = FF D8)
		return substr($stripped, 0, 2) . $comSeg . substr($stripped, 2);
	}

	/** Remove all existing JPEG COM (FF FE) segments. */
	private static function stripJpegCom(string $jpg): string
	{
		$out = substr($jpg, 0, 2); // SOI
		$pos = 2;
		$n   = strlen($jpg);
		while ($pos + 4 <= $n) {
			if (ord($jpg[$pos]) !== 0xFF) { $out .= substr($jpg, $pos); break; }
			$marker = ord($jpg[$pos + 1]);
			if ($marker === 0xDA || $marker === 0xD9) { // SOS / EOI — copy rest verbatim
				$out .= substr($jpg, $pos);
				break;
			}
			$segLen = unpack('n', substr($jpg, $pos + 2, 2))[1];
			if ($marker === 0xFE) { // COM — skip it
				$pos += 2 + $segLen;
				continue;
			}
			$out .= substr($jpg, $pos, 2 + $segLen);
			$pos += 2 + $segLen;
		}
		return $out;
	}

	/** Return the content of the first COM (FF FE) segment, or null. */
	private static function extractJpeg(string $jpg): ?string
	{
		$pos = 2; // skip SOI
		$n   = strlen($jpg);
		while ($pos + 4 <= $n) {
			if (ord($jpg[$pos]) !== 0xFF) break;
			$marker = ord($jpg[$pos + 1]);
			if ($marker === 0xDA || $marker === 0xD9) break;
			$segLen = unpack('n', substr($jpg, $pos + 2, 2))[1];
			if ($segLen < 2) break;
			if ($marker === 0xFE) {
				$text = rtrim(substr($jpg, $pos + 4, $segLen - 2), "\x00");
				return $text !== '' ? $text : null;
			}
			$pos += 2 + $segLen;
		}
		return null;
	}
}
