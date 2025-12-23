<?php
/**
 * PHP Self-Updater - Çıxarma İstisnası
 * 
 * ZIP faylını çıxarma xətaları üçün.
 * Path traversal hücumları, yazma icazəsi problemləri və s.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Exceptions;

/**
 * ExtractionException - Çıxarma xətaları
 */
class ExtractionException extends UpdaterException
{
    // ZIP çıxarma ilə bağlı xətalar
}
