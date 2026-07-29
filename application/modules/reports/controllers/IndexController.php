<?php

class Reports_IndexController extends App_Controller_Base
{
	 public function init()
    {
      	//ini_set('display_errors', 1);
      	//ini_set('display_startup_errors', 1);
      	//error_reporting(E_ALL);
        
    }

	public function indexAction()
	{

	    $api = new App_Service_Api();
        $_ = $api->authorization();


        $sess = App_Service_Session::get('user');
        
        $payloadUser = [
            $sess['id'],
            $sess['session_token']
        ];

        $responseUser = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payloadUser);
        $listDataUser = isset($responseUser["msg"]) ? $responseUser["msg"] : [];

        /*** Khusus Level ITP ****/
        if($listDataUser[0]["level_id"] == '2')
        {
        	$this->_redirect('/reports/index/itpreport');
			exit;
        } 
        

		$layanan = $this->_getParam('layanan', 'ALL');
    	$this->view->selectedLayanan = $layanan;


		$activeTab = $this->_getParam('type', 'it-provider');

		$this->view->layanan = array(
	        'ALL'            => 'Semua Layanan',
	        'POSTPAID'    => 'Postpaid',
	        'PREPAID'     => 'Prepaid',
	        'NON_TAGLIST' => 'Non-Taglist'
	    );

	    $apiUrl   = '/service/proxy/service/alias/get-all-itp';
	    $payload  = [];

	    $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];


	}

	public function listtransaksiAction()
	{

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try{
        		$api = new App_Service_Api();
        		$_ = $api->authorization();


	        	$draw   = (int)$this->_getParam('draw', 1);
	            $start  = (int)$this->_getParam('start', 0);
	            $length = (int)$this->_getParam('length', 10);


	            $tanggal     = $this->_getParam('tanggal', date('Y-m-d'));
	            $itProvider  = trim($this->_getParam('it_provider', ''));
	            $layanan     = trim($this->_getParam('layanan', ''));
	            $keyword     = trim($this->_getParam('keyword', ''));
	            $mitra       = trim($this->_getParam('mitra', ''));

	            if($mitra == "")
	            {
	            	$columns = array(
		                0 => 'no',
		                1 => 'it-provider',
		                2 => 'layanan',
		                3 => 'lembar',
		                4 => 'tagihan',
		                5 => 'admin',
		                6 => 'total'
	            	);
	            }
	            else{
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

	            

            	$orderIndex = (int)$this->_getParam('order')[0]['column'];
	            $orderDir   = strtoupper($this->_getParam('order')[0]['dir']);

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
    			$responsetotalnowITP 	= $api->request('POST', $apiUrltotalnowITP, $payloadtotalnowITP);


	    		echo Zend_Json::encode(array(
	                "draw" => $draw,
	                "recordsTotal" => count($responsetotalnowITP['msg']),
	                "recordsFiltered" => count($responsetotalnowITP['msg']),
	                "data" => $responsetotalnowITP['msg']
	            ));



        }catch (Exception $e) {
        	
        	echo Zend_Json::encode(array(
                "draw" => 1,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => array(),
                "message" => $e->getMessage()
            ));

        }

        exit;
	}

	public function downloadAction()
	{
		$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

       $tanggal     = $this->_getParam('tanggal');
       $itProvider  = trim($this->_getParam('it_provider', ''));
       $layanan     = trim($this->_getParam('layanan', ''));
       $keyword     = trim($this->_getParam('keyword', ''));
       $mitra       = trim($this->_getParam('mitra', ''));
       $length      = 100;
       $start       = 0;


	   if ($layanan === '502') 
	   {
			$layanantext = 'Prepaid';
	   }
	   elseif ($layanan === '501') 
	   {
			$layanantext = 'Postpaid';
	   } 
	   elseif ($layanan === '504') 
	   {
			$layanantext = 'Non-Taglist';
	   }
	   elseif($layanan === 'ALL')
	   {
			$layanan = "";
			$layanantext = 'Semua Layanan';
	    }

       $api = new App_Service_Api();
       $_ = $api->authorization();


       $apiUrltotalnowITP  		= '/service/proxy/service/alias/total-trx-now-itp';
	   $payloadtotalnowITP  	= [$tanggal,$layanan,$itProvider,$mitra,$keyword,'nama',$length,$start];
	   $responsetotalnowITP 	= $api->request('POST', $apiUrltotalnowITP, $payloadtotalnowITP);
	   $listData 				= isset($responsetotalnowITP["msg"]) ? $responsetotalnowITP["msg"] : [];

	   if(!empty($itProvider))
	   {
	   	   $apiUrlGetNamaITP 		= '/service/proxy/service/alias/get-itp-name';
		   $payloadGetNamaITP       = [$itProvider];
		   $responseGetNamaITP 		= $api->request('POST', $apiUrlGetNamaITP, $payloadGetNamaITP);
		   $namaitprovider         = $responseGetNamaITP['msg'][0]['nama_technical_provider'];

	   }
	   else{
	   			$namaitprovider         = "";
	   }

	   



        set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
        require_once '../library/Spreadsheet/Excel/Writer.php';

        $filename = "Laporan_Transaksi_iT-Provider_".$namaitprovider. "_Layanan_".$layanantext."_Tanggal_".$tanggal. ".xls";


        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->send($filename);

        $worksheet = &$workbook->addWorksheet('Transaksi');

        $format_bold = &$workbook->addFormat();
        $format_bold->setBold();

        $format_center_data = &$workbook->addFormat();
        $format_center_data->setAlign('center');
        $format_center_data->setBorder(1);

        $format_lembar_data = &$workbook->addFormat();
        $format_lembar_data->setAlign('right');
        $format_lembar_data->setBorder(1);

        $format_uang = &$workbook->addFormat();
        $format_uang->setAlign('right');
        $format_uang->setBorder(1);
        $format_uang->setNumFormat('#,##0');

        $format_header = &$workbook->addFormat();
        $format_header->setBold();
        $format_header->setAlign('center');
        $format_header->setBorder(1);

        $format_total_uang = &$workbook->addFormat();
        $format_total_uang->setBold();
        $format_total_uang->setAlign('right');
        $format_total_uang->setBorder(1);
        $format_total_uang->setNumFormat('#,##0');

        $format_total_lembar = &$workbook->addFormat();
        $format_total_lembar->setBold();
        $format_total_lembar->setAlign('right');
        $format_total_lembar->setBorder(1);

        $format_total_text = &$workbook->addFormat();
        $format_total_text->setBold();
        $format_total_text->setAlign('center');
        $format_total_text->setBorder(1);

        
    	$worksheet->setColumn(0, 0, 8);
        $worksheet->setColumn(1, 1, 18);
        $worksheet->setColumn(2, 2, 32);
        $worksheet->setColumn(3, 3, 16);
        $worksheet->setColumn(4, 4, 14);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 20);


        $worksheet->write(0, 0, "LAPORAN TRANSAKSI", $format_bold);
        $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $tanggal, $format_bold);
        $worksheet->write(2, 0, "IT PROVIDER   : " . strtoupper($namaitprovider), $format_bold);
        $worksheet->write(3, 0, "LAYANAN   : " . strtoupper($layanantext), $format_bold);

    	$worksheet->write(5, 0, 'No', $format_header);
        $worksheet->write(5, 1, 'IT Provider', $format_header);
        $worksheet->write(5, 2, 'Layanan', $format_header);
        $worksheet->write(5, 3, 'Lembar', $format_header);
        $worksheet->write(5, 4, 'Tagihan (Rp)', $format_header);
        $worksheet->write(5, 5, 'Admin ITP (Rp)', $format_header);
        $worksheet->write(5, 6, 'Total (Rp)', $format_header);


        $total_lembar  = 0;
        $total_amount  = 0;
        $total_fee     = 0;
        $total_bill    = 0;

            $i = 1;
            if (is_array($listData) && !empty($listData)) {
                foreach ($listData as $lap) {
        
                    if (isset($lap['lembar'])) {
                        $nilaiLembar = $lap['lembar'];
                    }  else {
                        $nilaiLembar = 0;
                    }

                    if (isset($lap['sum_total_tagihan'])) {
                        $nilaiTagihan = $lap['sum_total_tagihan'];
                    }  else {
                        $nilaiTagihan = 0;
                    }

                    if (isset($lap['sum_total_fee'])) {
                        $nilaiAdmin = $lap['sum_total_fee'];
                    } else {
                        $nilaiAdmin = 0;
                    }

                    if (isset($lap['sum_total_nomial'])) {
                        $nilaiTotal = $lap['sum_total_nomial'];
                    } else {
                        $nilaiTotal = 0;
                    }



                    $worksheet->write($i + 5, 0, $i, $format_center_data);
                    $worksheet->write($i + 5, 1, $lap['nama_technical_provider'], $format_center_data);
                    $worksheet->write($i + 5, 2, $lap['product'], $format_center_data);

                    $worksheet->writeNumber($i + 5, 3, intval($nilaiLembar), $format_lembar_data);
                    $worksheet->writeNumber($i + 5, 4, floatval($nilaiTagihan), $format_uang);
                    $worksheet->writeNumber($i + 5, 5, floatval($nilaiAdmin), $format_uang);
                    $worksheet->writeNumber($i + 5, 6, floatval($nilaiTotal), $format_uang);


                    $total_lembar  = $total_lembar + intval($nilaiLembar);
                    $total_amount  = $total_amount + floatval($nilaiTagihan);
                    $total_fee     = $total_fee + floatval($nilaiAdmin);
                    $total_bill    = $total_bill + floatval($nilaiTotal);

                    $i++;
                }
            }

            
            $row_total = count($listData) + 6;

            $worksheet->write($row_total, 0, '', $format_total_text);
            $worksheet->write($row_total, 1, 'TOTAL', $format_total_text);
            $worksheet->write($row_total, 2, '', $format_total_text);

            $worksheet->writeNumber($row_total, 3, $total_lembar, $format_total_lembar);
            $worksheet->writeNumber($row_total, 4, $total_amount, $format_total_uang);
            $worksheet->writeNumber($row_total, 5, $total_fee, $format_total_uang);
            $worksheet->writeNumber($row_total, 6, $total_bill, $format_total_uang);


			
            $workbook->close();
            exit;
            
        
	}

	public function downloadmitraAction()
	{
		$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);


       $tanggal     = $this->_getParam('tanggal');
       $itProvider  = trim($this->_getParam('it_provider', ''));
       $layanan     = trim($this->_getParam('layanan', ''));
       $keyword     = trim($this->_getParam('keyword', ''));
       $mitra       = trim($this->_getParam('mitra', ''));
       $length      = 100;
       $start       = 0;


	   if ($layanan === '502') 
	   {
			$layanantext = 'Prepaid';
	   }
	   elseif ($layanan === '501') 
	   {
			$layanantext = 'Postpaid';
	   } 
	   elseif ($layanan === '504') 
	   {
			$layanantext = 'Non-Taglist';
	   }
	   elseif($layanan === 'ALL')
	   {
			$layanan = "";
			$layanantext = 'Semua Layanan';
	    }

       $api = new App_Service_Api();
       $_ = $api->authorization();


       $apiUrltotalnowITP  		= '/service/proxy/service/alias/total-trx-now-itp';
	   $payloadtotalnowITP  	= [$tanggal,$layanan,$itProvider,$mitra,$keyword,'nama',$length,$start];
	   $responsetotalnowITP 	= $api->request('POST', $apiUrltotalnowITP, $payloadtotalnowITP);
	   $listData 				= isset($responsetotalnowITP["msg"]) ? $responsetotalnowITP["msg"] : [];

	   if(!empty($itProvider))
	   {
	   	   $apiUrlGetNamaITP 		= '/service/proxy/service/alias/get-itp-name';
		   $payloadGetNamaITP       = [$itProvider];
		   $responseGetNamaITP 		= $api->request('POST', $apiUrlGetNamaITP, $payloadGetNamaITP);
		   $namaitprovider         = $responseGetNamaITP['msg'][0]['nama_technical_provider'];

	   }
	   else{
	   			$namaitprovider         = "";
	   }

	   if(!empty($mitra))
	   {
	   	   $apiUrlGetNamaMitra 		= '/service/proxy/service/alias/get-partner-name';
		   $payloadGetNamaMitra     = [$mitra];
		   $responseGetNamaMitra 	= $api->request('POST', $apiUrlGetNamaMitra, $payloadGetNamaMitra);
		   $namamitra         		= $responseGetNamaMitra['msg'][0]['partner_name'];

	   }
	   else{
	   			$namamitra         = "";
	   }


        set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
        require_once '../library/Spreadsheet/Excel/Writer.php';

       	
        $filename = "Laporan_Transaksi_iT-Provider_".$namaitprovider. "_Mitra_" .$namamitra."_Layanan_".$layanantext."_Tanggal_".$tanggal. ".xls";

        
        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->send($filename);

        $worksheet = &$workbook->addWorksheet('Transaksi');

        $format_bold = &$workbook->addFormat();
        $format_bold->setBold();

        $format_center_data = &$workbook->addFormat();
        $format_center_data->setAlign('center');
        $format_center_data->setBorder(1);

        $format_lembar_data = &$workbook->addFormat();
        $format_lembar_data->setAlign('right');
        $format_lembar_data->setBorder(1);

        $format_uang = &$workbook->addFormat();
        $format_uang->setAlign('right');
        $format_uang->setBorder(1);
        $format_uang->setNumFormat('#,##0');

        $format_header = &$workbook->addFormat();
        $format_header->setBold();
        $format_header->setAlign('center');
        $format_header->setBorder(1);

        $format_total_uang = &$workbook->addFormat();
        $format_total_uang->setBold();
        $format_total_uang->setAlign('right');
        $format_total_uang->setBorder(1);
        $format_total_uang->setNumFormat('#,##0');

        $format_total_lembar = &$workbook->addFormat();
        $format_total_lembar->setBold();
        $format_total_lembar->setAlign('right');
        $format_total_lembar->setBorder(1);

        $format_total_text = &$workbook->addFormat();
        $format_total_text->setBold();
        $format_total_text->setAlign('center');
        $format_total_text->setBorder(1);

        
     
		$worksheet->setColumn(0, 0, 8);
        $worksheet->setColumn(1, 1, 18);
        $worksheet->setColumn(2, 2, 32);
        $worksheet->setColumn(3, 3, 16);
        $worksheet->setColumn(4, 4, 14);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 20);
        $worksheet->setColumn(6, 7, 20);


        $worksheet->write(0, 0, "LAPORAN TRANSAKSI", $format_bold);
        $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $tanggal, $format_bold);
        $worksheet->write(2, 0, "IT PROVIDER   : " . strtoupper($namaitprovider), $format_bold);
        $worksheet->write(3, 0, "MITRA   : " . strtoupper($namamitra), $format_bold);
        $worksheet->write(4, 0, "LAYANAN   : " . strtoupper($layanantext), $format_bold);

		$worksheet->write(6, 0, 'No', $format_header);
        $worksheet->write(6, 1, 'IT Provider', $format_header);
        $worksheet->write(6, 2, 'Mitra', $format_header);
        $worksheet->write(6, 3, 'Layanan', $format_header);
        $worksheet->write(6, 4, 'Lembar', $format_header);
        $worksheet->write(6, 5, 'Tagihan (Rp)', $format_header);
        $worksheet->write(6, 6, 'Admin ITP (Rp)', $format_header);
        $worksheet->write(6, 7, 'Total (Rp)', $format_header);
        

       
        
        $total_lembar  = 0;
        $total_amount  = 0;
        $total_fee     = 0;
        $total_bill    = 0;

            $i = 1;
            if (is_array($listData) && !empty($listData)) {
                foreach ($listData as $lap) {
        
                    if (isset($lap['lembar'])) {
                        $nilaiLembar = $lap['lembar'];
                    }  else {
                        $nilaiLembar = 0;
                    }

                    if (isset($lap['sum_total_tagihan'])) {
                        $nilaiTagihan = $lap['sum_total_tagihan'];
                    }  else {
                        $nilaiTagihan = 0;
                    }

                    if (isset($lap['sum_total_fee'])) {
                        $nilaiAdmin = $lap['sum_total_fee'];
                    } else {
                        $nilaiAdmin = 0;
                    }

                    if (isset($lap['sum_total_nomial'])) {
                        $nilaiTotal = $lap['sum_total_nomial'];
                    } else {
                        $nilaiTotal = 0;
                    }

              

            		$worksheet->write($i + 6, 0, $i, $format_center_data);
                    $worksheet->write($i + 6, 1, $lap['nama_technical_provider'], $format_center_data);
                    $worksheet->write($i + 6, 2, $lap['nama_mitra'], $format_center_data);
                    $worksheet->write($i + 6, 3, $lap['product'], $format_center_data);

                    $worksheet->writeNumber($i + 6, 4, intval($nilaiLembar), $format_lembar_data);
                    $worksheet->writeNumber($i + 6, 5, floatval($nilaiTagihan), $format_uang);
                    $worksheet->writeNumber($i + 6, 6, floatval($nilaiAdmin), $format_uang);
                    $worksheet->writeNumber($i + 6, 7, floatval($nilaiTotal), $format_uang);


                    $total_lembar  = $total_lembar + intval($nilaiLembar);
                    $total_amount  = $total_amount + floatval($nilaiTagihan);
                    $total_fee     = $total_fee + floatval($nilaiAdmin);
                    $total_bill    = $total_bill + floatval($nilaiTotal);

                    $i++;
                }
            }

            
            $row_total = count($listData) + 7;

    		$worksheet->write($row_total, 0, '', $format_total_text);
            $worksheet->write($row_total, 1, 'TOTAL', $format_total_text);
            $worksheet->write($row_total, 2, '', $format_total_text);
            $worksheet->write($row_total, 3, '', $format_total_text);
            $worksheet->writeNumber($row_total, 4, $total_lembar, $format_total_lembar);
            $worksheet->writeNumber($row_total, 5, $total_amount, $format_total_uang);
            $worksheet->writeNumber($row_total, 6, $total_fee, $format_total_uang);
            $worksheet->writeNumber($row_total, 7, $total_bill, $format_total_uang);
			
            $workbook->close();
            exit;
    }



	public function downloaditpmitraAction()
	{
		$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);


       $tanggal     = $this->_getParam('tanggal');
       $itProvider  = trim($this->_getParam('it_provider', ''));
       $layanan     = trim($this->_getParam('layanan', ''));
       $keyword     = trim($this->_getParam('keyword', ''));
       $mitra       = trim($this->_getParam('mitra', ''));
       $length      = 100;
       $start       = 0;


	   if ($layanan === '502') 
	   {
			$layanantext = 'Prepaid';
	   }
	   elseif ($layanan === '501') 
	   {
			$layanantext = 'Postpaid';
	   } 
	   elseif ($layanan === '504') 
	   {
			$layanantext = 'Non-Taglist';
	   }
	   elseif($layanan === 'ALL')
	   {
			$layanan = "";
			$layanantext = 'Semua Layanan';
	    }

       $api = new App_Service_Api();
       $_ = $api->authorization();


       $apiUrltotalnowITP  		= '/service/proxy/service/alias/total-trx-now-itp';
	   $payloadtotalnowITP  	= [$tanggal,$layanan,$itProvider,$mitra,$keyword,'nama',$length,$start];
	   $responsetotalnowITP 	= $api->request('POST', $apiUrltotalnowITP, $payloadtotalnowITP);
	   $listData 				= isset($responsetotalnowITP["msg"]) ? $responsetotalnowITP["msg"] : [];

	   if(!empty($itProvider))
	   {
	   	   $apiUrlGetNamaITP 		= '/service/proxy/service/alias/get-itp-name';
		   $payloadGetNamaITP       = [$itProvider];
		   $responseGetNamaITP 		= $api->request('POST', $apiUrlGetNamaITP, $payloadGetNamaITP);
		   $namaitprovider         = $responseGetNamaITP['msg'][0]['nama_technical_provider'];

	   }
	   else{
	   			$namaitprovider         = "";
	   }

	   if(!empty($mitra))
	   {
	   	   $apiUrlGetNamaMitra 		= '/service/proxy/service/alias/get-partner-name';
		   $payloadGetNamaMitra     = [$mitra];
		   $responseGetNamaMitra 	= $api->request('POST', $apiUrlGetNamaMitra, $payloadGetNamaMitra);
		   $namamitra         		= $responseGetNamaMitra['msg'][0]['partner_name'];

	   }
	   else{
	   			$namamitra         = "";
	   }


        set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
        require_once '../library/Spreadsheet/Excel/Writer.php';

       	
        $filename = "Laporan_Transaksi_iT-Provider_".$namaitprovider. "_Mitra_" .$namamitra."_Layanan_".$layanantext."_Tanggal_".$tanggal. ".xls";

        
        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->send($filename);

        $worksheet = &$workbook->addWorksheet('Transaksi');

        $format_bold = &$workbook->addFormat();
        $format_bold->setBold();

        $format_center_data = &$workbook->addFormat();
        $format_center_data->setAlign('center');
        $format_center_data->setBorder(1);

        $format_lembar_data = &$workbook->addFormat();
        $format_lembar_data->setAlign('right');
        $format_lembar_data->setBorder(1);

        $format_uang = &$workbook->addFormat();
        $format_uang->setAlign('right');
        $format_uang->setBorder(1);
        $format_uang->setNumFormat('#,##0');

        $format_header = &$workbook->addFormat();
        $format_header->setBold();
        $format_header->setAlign('center');
        $format_header->setBorder(1);

        $format_total_uang = &$workbook->addFormat();
        $format_total_uang->setBold();
        $format_total_uang->setAlign('right');
        $format_total_uang->setBorder(1);
        $format_total_uang->setNumFormat('#,##0');

        $format_total_lembar = &$workbook->addFormat();
        $format_total_lembar->setBold();
        $format_total_lembar->setAlign('right');
        $format_total_lembar->setBorder(1);

        $format_total_text = &$workbook->addFormat();
        $format_total_text->setBold();
        $format_total_text->setAlign('center');
        $format_total_text->setBorder(1);

        
     
		$worksheet->setColumn(0, 0, 8);
        $worksheet->setColumn(1, 1, 18);
        $worksheet->setColumn(2, 2, 32);
        $worksheet->setColumn(3, 3, 16);
        $worksheet->setColumn(4, 4, 14);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 20);
        $worksheet->setColumn(6, 7, 20);


        $worksheet->write(0, 0, "LAPORAN TRANSAKSI", $format_bold);
        $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $tanggal, $format_bold);
        $worksheet->write(2, 0, "IT PROVIDER   : " . strtoupper($namaitprovider), $format_bold);
        $worksheet->write(3, 0, "MITRA   : " . strtoupper($namamitra), $format_bold);
        $worksheet->write(4, 0, "LAYANAN   : " . strtoupper($layanantext), $format_bold);

		$worksheet->write(6, 0, 'No', $format_header);
        $worksheet->write(6, 1, 'Mitra', $format_header);
        $worksheet->write(6, 2, 'Layanan', $format_header);
        $worksheet->write(6, 3, 'Lembar', $format_header);
        $worksheet->write(6, 4, 'Tagihan (Rp)', $format_header);
        $worksheet->write(6, 5, 'Admin ITP (Rp)', $format_header);
        $worksheet->write(6, 6, 'Total (Rp)', $format_header);
        

       
        
        $total_lembar  = 0;
        $total_amount  = 0;
        $total_fee     = 0;
        $total_bill    = 0;

            $i = 1;
            if (is_array($listData) && !empty($listData)) {
                foreach ($listData as $lap) {
        
                    if (isset($lap['lembar'])) {
                        $nilaiLembar = $lap['lembar'];
                    }  else {
                        $nilaiLembar = 0;
                    }

                    if (isset($lap['sum_total_tagihan'])) {
                        $nilaiTagihan = $lap['sum_total_tagihan'];
                    }  else {
                        $nilaiTagihan = 0;
                    }

                    if (isset($lap['sum_total_fee'])) {
                        $nilaiAdmin = $lap['sum_total_fee'];
                    } else {
                        $nilaiAdmin = 0;
                    }

                    if (isset($lap['sum_total_nomial'])) {
                        $nilaiTotal = $lap['sum_total_nomial'];
                    } else {
                        $nilaiTotal = 0;
                    }

              

            		$worksheet->write($i + 6, 0, $i, $format_center_data);
                    $worksheet->write($i + 6, 1, $lap['nama_mitra'], $format_center_data);
                    $worksheet->write($i + 6, 2, $lap['product'], $format_center_data);

                    $worksheet->writeNumber($i + 6, 3, intval($nilaiLembar), $format_lembar_data);
                    $worksheet->writeNumber($i + 6, 4, floatval($nilaiTagihan), $format_uang);
                    $worksheet->writeNumber($i + 6, 5, floatval($nilaiAdmin), $format_uang);
                    $worksheet->writeNumber($i + 6, 6, floatval($nilaiTotal), $format_uang);


                    $total_lembar  = $total_lembar + intval($nilaiLembar);
                    $total_amount  = $total_amount + floatval($nilaiTagihan);
                    $total_fee     = $total_fee + floatval($nilaiAdmin);
                    $total_bill    = $total_bill + floatval($nilaiTotal);

                    $i++;
                }
            }

            
            $row_total = count($listData) + 7;

    		$worksheet->write($row_total, 0, '', $format_total_text);
            $worksheet->write($row_total, 1, 'TOTAL', $format_total_text);
            $worksheet->write($row_total, 2, '', $format_total_text);
            $worksheet->writeNumber($row_total, 3, $total_lembar, $format_total_lembar);
            $worksheet->writeNumber($row_total, 4, $total_amount, $format_total_uang);
            $worksheet->writeNumber($row_total, 5, $total_fee, $format_total_uang);
            $worksheet->writeNumber($row_total, 6, $total_bill, $format_total_uang);
			
            $workbook->close();
            exit;
    }

	public function mitraAction()
	{
		$api = new App_Service_Api();
        $_ = $api->authorization();

		$layanan = $this->_getParam('layanan', 'ALL');
    	$this->view->selectedLayanan = $layanan;


		$activeTab = $this->_getParam('type', 'it-provider');

		$this->view->layanan = array(
	        'ALL'            => 'Semua Layanan',
	        'POSTPAID'    => 'Postpaid',
	        'PREPAID'     => 'Prepaid',
	        'NON_TAGLIST' => 'Non-Taglist'
	    );

	    $apiUrl   = '/service/proxy/service/alias/get-all-itp';
	    $payload  = [];

	    $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];

        $apiUrlPartner   = '/service/proxy/service/alias/get-all-partner';
	    $payloadPartner   = [];

	    $responsePartner  = $api->request('POST', $apiUrlPartner , $payloadPartner );
        $this->view->listDataPartner  = isset($responsePartner ["msg"]) ? $responsePartner ["msg"] : [];

	}

	public function itpreportAction()
	{

		$api = new App_Service_Api();
        $_ = $api->authorization();

		$layanan = $this->_getParam('layanan', 'ALL');
    	$this->view->selectedLayanan = $layanan;


		$activeTab = $this->_getParam('type', 'it-provider');

		$this->view->layanan = array(
	        'ALL'         => 'Semua Layanan',
	        'POSTPAID'    => 'Postpaid',
	        'PREPAID'     => 'Prepaid',
	        'NON_TAGLIST' => 'Non-Taglist'
	    );

	    
	    $sess = App_Service_Session::get('user');


	     $payloadUser = [
            $sess['id'],
            $sess['session_token']
        ];

        $responseUser = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payloadUser);
        $listDataUser = isset($responseUser["msg"]) ? $responseUser["msg"] : [];

        $resjson = json_decode($listDataUser[0]["additional_info"],true);
		$itpcode = strtolower($resjson["tp_code"]);

		$this->view->itpcode = strtoupper($itpcode);

		
	    $apiUrl   = '/service/proxy/service/alias/get-partner-itp';
	    $payload  = [$itpcode];

	    $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];
	

        
	}


}
