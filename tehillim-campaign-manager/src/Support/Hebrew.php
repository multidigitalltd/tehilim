<?php
/**
 * Hebrew helpers.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure helpers for Hebrew display (chapter numbering as Hebrew letters).
 */
final class Hebrew {

    /**
     * Convert a Psalms chapter number (1-150) to its Hebrew-letter label.
     *
     * @param int $number Chapter number.
     * @return string Hebrew label, or the number as a string if out of range.
     */
    public static function chapter_label($number) {
        $number = (int) $number;
        if ($number < 1 || $number > 400) {
            return (string) $number;
        }

        $units = array('', 'א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ז', 'ח', 'ט');
        $tens  = array('', 'י', 'כ', 'ל', 'מ', 'נ', 'ס', 'ע', 'פ', 'צ');
        $huns  = array('', 'ק', 'ר', 'ש', 'ת');

        $h = (int) floor($number / 100);
        $t = (int) floor(($number % 100) / 10);
        $u = $number % 10;

        // Special cases: 15 = טו, 16 = טז (avoid spelling a Divine name).
        if (1 === $t && (5 === $u || 6 === $u)) {
            return ($huns[$h] ?? '') . 'ט' . (5 === $u ? 'ו' : 'ז');
        }

        return ($huns[$h] ?? '') . $tens[$t] . $units[$u];
    }
}
