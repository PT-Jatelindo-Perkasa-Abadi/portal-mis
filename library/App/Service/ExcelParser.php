<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class App_Service_ExcelParser
{
    const MAX_FILE_SIZE = 1048576; // 1MB
    const MAX_ROWS = 1000;

    protected static $headerMap = [
        'account number' => 'beneficiaryAccountNumber',
        'bank code' => 'beneficiaryBankCode',
        'amount (rp)' => 'amount',
        'remarks' => 'remarks'
    ];

    public static function parseInquiryAccounts($filePath)
    {
        self::validateFile($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // if (count($rows) - 1 > self::MAX_ROWS) {
        //     throw new Exception("Jumlah row melebihi batas maksimal " . self::MAX_ROWS);
        // }

        $headerIndex = self::mapHeader($rows[1]);

        $result = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($index == 1)
                continue; // skip header

            if (self::isEmptyRow($row))
                continue;

            $mapped = self::mapRow($row, $headerIndex);
            $validation = self::validateRow($mapped, $index);

            if (!empty($validation)) {
                $errors[] = [
                    'row' => $index,
                    'errors' => $validation
                ];
                continue;
            }

            $result[] = $mapped;
        }

        return [
            'inquiryAccounts' => $result,
            'errors' => $errors,
            'meta' => [
                'total_valid' => count($result),
                'total_error' => count($errors),
            ]
        ];
    }

    protected static function validateFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan");
        }

        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new Exception("Ukuran file melebihi 1MB");
        }
    }

    protected static function mapHeader($headerRow)
    {
        $headerIndex = [];

        foreach ($headerRow as $col => $value) {
            $key = strtolower(trim(preg_replace('/\s+/', ' ', $value)));
            if (isset(self::$headerMap[$key])) {
                $headerIndex[self::$headerMap[$key]] = $col;
            }
        }

        // validasi header wajib
        $required = array_values(self::$headerMap);
        foreach ($required as $field) {
            if (!isset($headerIndex[$field])) {
                throw new Exception("Header tidak lengkap: $field tidak ditemukan");
            }
        }

        return $headerIndex;
    }

    protected static function mapRow($row, $headerIndex)
    {
        $data = [];

        foreach ($headerIndex as $key => $col) {
            $rawValue = isset($row[$col]) ? $row[$col] : null;

            if ($key === 'amount') {
                $data[$key] = self::normalizeAmount($rawValue);
            } else {
                $data[$key] = is_string($rawValue) ? trim($rawValue) : $rawValue;
            }
        }

        return $data;
    }

    protected static function validateRow($row, $rowNumber)
    {
        $errors = [];

        if (!ctype_digit($row['amount'])) {
            $errors[] = 'Amount harus numeric valid';
        }

        // account number
        if (empty($row['beneficiaryAccountNumber'])) {
            $errors[] = 'Account Number kosong';
        } elseif (!ctype_digit($row['beneficiaryAccountNumber'])) {
            $errors[] = 'Account Number harus numeric';
        }

        // bank code
        if (empty($row['beneficiaryBankCode'])) {
            $errors[] = 'Bank Code kosong';
        }

        // amount
        if (empty($row['amount'])) {
            $errors[] = 'Amount kosong';
        } elseif ((int) $row['amount'] <= 0) {
            $errors[] = 'Amount harus lebih dari 0';
        }

        return $errors;
    }

    protected static function isEmptyRow($row)
    {
        return empty(array_filter($row));
    }

    protected static function normalizeAmount($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        /**
         * Paksa selalu bersihkan format
         * karena is_numeric("10.000") = true (salah konteks Indonesia)
         */

        // hapus semua selain digit
        $value = preg_replace('/[^0-9]/', '', $value);

        return $value;
    }
}