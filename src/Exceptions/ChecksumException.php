<?php
/**
 * PHP Self-Updater - Checksum İstisnası
 * 
 * SHA256 checksum uyğunsuzluğu xətaları üçün.
 * Endirilmiş faylın checksum-ı serverdəki ilə uyğun gəlmədikdə atılır.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Exceptions;

/**
 * ChecksumException - Checksum xətaları
 */
class ChecksumException extends UpdaterException
{
    // Checksum uyğunsuzluğu xətaları
}
