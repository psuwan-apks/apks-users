<?php

namespace apks\utils\date;

use DateTime;

class apksDATEBE
{

    // Static arrays for months to avoid recreating them
    private static array $shortMonthsTH = [
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];

    private static array $longMonthsTH = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    // Convert date to Thai format with short month name
    public static function dateBEShortMonthTH($date): string
    {
        $dateTime = self::parseDate($date);
        if (!$dateTime) {
            return "Wrong format [Y-m-d]";
        }

        $yearBE = (int)$dateTime->format('Y') + 543;
        return $dateTime->format('j') . " " . self::$shortMonthsTH[$dateTime->format('n')] . " " . $yearBE;
    }

    // Convert date to Thai format with long month name
    public static function dateBELongMonthTH($date = 'now'): string
    {
        $dateTime = self::parseDate($date);
        if (!$dateTime) {
            return "Wrong format [Y-m-d]";
        }

        $yearBE = (int)$dateTime->format('Y') + 543;
        return $dateTime->format('j') . " " . self::$longMonthsTH[$dateTime->format('n')] . " " . $yearBE;
    }

    // Convert Gregorian date (AD) to Buddhist Era (BE)
    public static function dateAD2BE($dateAD): string
    {
        $dateTime = self::parseDate($dateAD);
        if (!$dateTime) {
            return "Wrong format [Y-m-d]";
        }

        return ((int)$dateTime->format('Y') + 543) . '-' . $dateTime->format('m') . '-' . $dateTime->format('d');
    }

    // Convert Buddhist Era (BE) date to Gregorian date (AD)
    public static function dateBE2AD($dateBE): string
    {
        $dateTime = self::parseDate($dateBE, true);
        if (!$dateTime) {
            return "Wrong format [Y-m-d]";
        }

        return ($dateTime->format('Y') - 543) . '-' . $dateTime->format('m') . '-' . $dateTime->format('d');
    }

    // Helper function to parse date and handle 'now' or empty cases, with BE support
    private static function parseDate($date, $isBE = false)
    {
        // Use current date if input is empty or 'now'
        if (empty($date) || strtolower($date) === 'now') {
            return new DateTime();
        }

        // Try to create a DateTime object from the given date
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime) {
            return false; // Invalid date format
        }

        // Adjust year if it's a BE date
        if ($isBE) {
            $year = $dateTime->format('Y') - 543;
            $dateTime->setDate($year, $dateTime->format('m'), $dateTime->format('d'));
        }

        return $dateTime;
    }
}