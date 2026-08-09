<?php

class Reports_Model_Aps
{
    protected $_api;

    public function __construct()
    {
        $this->_api = new App_Service_Api();
    }

    public function getTransactionMitra($params)
    {
        $response = [];

        try {
            $this->_api->authorization();

            $draw           = $params['draw'];
            $start          = $params['start'];
            $length         = $params['length'];
            $tanggal        = $params['tanggal'];
            $itProvider     = $params['it_provider'];
            $layanan        = $params['layanan'];
            $keyword        = $params['keyword'];
            $mitra          = $params['mitra'];

            if ($mitra == "") {
                $columns = array(
                    0 => 'no',
                    1 => 'it-provider',
                    2 => 'layanan',
                    3 => 'lembar',
                    4 => 'tagihan',
                    5 => 'admin',
                    6 => 'total'
                );
            } else {
                $columns = array(
                    0 => 'no',
                    1 => 'mitra',
                    2 => 'it-provider',
                    3 => 'layanan',
                    4 => 'lembar',
                    5 => 'tagihan',
                    6 => 'admin',
                    7 => 'total'
                );
            }

            $orderIndex = $params['order'][0]['column'];
            $orderDir   = strtoupper($params['order'][0]['dir']);

            $orderBy = isset($columns[$orderIndex])
                ? $columns[$orderIndex]
                : 'technical_provider_code';

            if (!in_array($orderDir, array('ASC', 'DESC'))) {
                $orderDir = 'ASC';
            }


            $whereTanggal  = $tanggal;
            $whereProvider = "";
            $whereLayanan  = "";
            $whereSearch   = "";

            if ($itProvider != "") {
                $whereProvider = " and technical_provider_code = '{$itProvider}'";
            }

            if ($layanan != "ALL") {
                $whereLayanan = "";
            }

            if ($keyword != "") {
                $keyword = strtolower($keyword);
                $keywords = "%{$keyword}%";

                $whereSearch = "
                (
                    lower(nama_technical_provider) LIKE '%{$keyword}%'
                    OR lower(product) LIKE '%{$keyword}%'
                )";
            }

            $apiUrltotalnowITP  	= '/service/proxy/service/alias/total-trx-now-itp';
            $payloadtotalnowITP  	= [$whereTanggal,$whereLayanan,$itProvider,$mitra,$keyword,'nama',$length,$start];
            $responsetotalnowITP 	= $this->_api->request('POST', $apiUrltotalnowITP, $payloadtotalnowITP);

            $response = [
                "draw" => $draw,
                "recordsTotal" => count($responsetotalnowITP['msg']),
                "recordsFiltered" => count($responsetotalnowITP['msg']),
                "data" => $responsetotalnowITP['msg'],
                "filterParams" => [
                    "tanggal" => $tanggal,
                    "itProvider" => !empty($itProvider) ? $itProvider : "ALL",
                    "layanan" => !empty($layanan) ? $layanan : "ALL"
                ]
            ];
        } catch (Exception $e) {
            $response = [
                "draw" => 1,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => array(),
                "filterParams" => [
                    "tanggal" => $tanggal,
                    "itProvider" => !empty($itProvider) ? $itProvider : "ALL",
                    "layanan" => !empty($layanan) ? $layanan : "ALL"
                ],
                "message" => $e->getMessage()
            ];
        }

        return $response;
    }

    public function generateExcelMitra($params, $data)
    {
        require_once '../library/Spreadsheet/Excel/Writer.php';

        $workbook = new Spreadsheet_Excel_Writer();
        $filename = 'Laporan_Transaksi_Mitra_Acquirer_' . $params['tanggal'] . '.xls';
        $workbook->send($filename);

        /*
        * Format
        */
        $titleFormat = $workbook->addFormat();
        $titleFormat->setBold();
        $titleFormat->setSize(10);

        $sectionFormat = $workbook->addFormat();
        $sectionFormat->setBold();
        $sectionFormat->setFgColor('silver');
        $sectionFormat->setBorder(1);

        $headerFormat = $workbook->addFormat();
        $headerFormat->setBold();
        $headerFormat->setAlign('center');
        $headerFormat->setBorder(1);

        $textFormat = $workbook->addFormat();
        $textFormat->setBorder(1);

        $centerFormat = $workbook->addFormat();
        $centerFormat->setAlign('center');
        $centerFormat->setBorder(1);

        $numberFormat = $workbook->addFormat();
        $numberFormat->setBorder(1);
        $numberFormat->setNumFormat('#,##0');

        $currencyFormat = $workbook->addFormat();
        $currencyFormat->setBorder(1);
        $currencyFormat->setNumFormat('#,##0');

        /*
        * Sheet
        */
        $worksheet = $workbook->addWorksheet('Report');

        /*
        * Column width
        */
        $worksheet->setColumn(0, 0, 8);
        $worksheet->setColumn(1, 1, 25);
        $worksheet->setColumn(2, 2, 30);
        $worksheet->setColumn(3, 3, 15);
        $worksheet->setColumn(4, 4, 12);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 20);
        $worksheet->setColumn(7, 7, 20);

        // Title
        $worksheet->write(0, 0, "LAPORAN TRANSAKSI", $titleFormat);
        $worksheet->write(1, 0, "PERIODE TANGGAL : " . $params['tanggal'], $titleFormat);
        $worksheet->write(2, 0, "MITRA ACQUIRER : " . strtoupper($params['it_provider']), $titleFormat);
        $worksheet->write(3, 0, "LAYANAN : " . strtoupper($params['layanan']), $titleFormat);

        /*
        * Summary
        */
        $worksheet->write(6, 0, 'Summary', $sectionFormat);
        $worksheet->write(7, 0, 'Total Lembar', $headerFormat);
        $worksheet->write(7, 1, 'Total Tagihan', $headerFormat);
        $worksheet->write(7, 2, 'Total Admin Mitra Acquirer', $headerFormat);
        $worksheet->write(7, 3, 'Total Nominal', $headerFormat);
        $worksheet->writeNumber(8, 0, $summary['total_lembar'], $numberFormat);
        $worksheet->writeNumber(8, 1, $summary['total_tagihan'], $currencyFormat);
        $worksheet->writeNumber(8, 2, $summary['total_fee'], $currencyFormat);
        $worksheet->writeNumber(8, 3, $summary['total_nominal'], $currencyFormat);

        /*
        * Table Header
        */
        $startRow = 12;
        $headers = [
            'No',
            'Mitra',
            'Mitra Acquirer',
            'Layanan',
            'Lembar',
            'Tagihan (Rp)',
            'Admin Mitra Acquirer (Rp)',
            'Total (Rp)'
        ];

        foreach ($headers as $column => $header) {
            $worksheet->write(
                $startRow,
                $column,
                $header,
                $headerFormat
            );
        }

        /*
        * Data
        */
        $rowNumber = $startRow + 1;
        foreach ($data as $index => $row) {
            $worksheet->write($rowNumber, 0, $index + 1, $centerFormat);
            $worksheet->write($rowNumber, 1, $row['nama_mitra'], $textFormat);
            $worksheet->write($rowNumber, 2, $row['nama_technical_provider'], $textFormat);
            $worksheet->write($rowNumber, 3, $row['product'], $textFormat);
            $worksheet->writeNumber($rowNumber, 4, (float) $row['lembar'], $numberFormat);
            $worksheet->writeNumber($rowNumber, 5, (float) $row['sum_total_tagihan'], $currencyFormat);
            $worksheet->writeNumber($rowNumber, 6, (float) $row['sum_total_fee'], $currencyFormat);
            $worksheet->writeNumber($rowNumber, 7, (float) $row['sum_total_nomial'], $currencyFormat);

            $rowNumber++;
        }

        $workbook->close();
    }
}