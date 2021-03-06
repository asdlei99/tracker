<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Main extends MY_Controller {

	public function __construct() 
	{
		parent::__construct();
		ini_set("max_execution_time", 0);
	}
	
	/**
	 * 显示新浪IP查询接口页面
	 *
	 * @author ijibu.com@gmail.com
	 */
	public function index()
	{
		//$this->output->enable_profiler(TRUE);
		$data = array();
		
 		$code = trim($this->input->get_post('code'));
 		if (!$code) {
 			$code = '600601';
 		}
 		
 		$this->load->model('stock_model');
 		$tmp = $this->stock_model->get(array('code' => $code));
 		$stockCode = array_shift($tmp);
 		$stockCodes = $this->getStockByTpye($stockCode['exchange']);
 		
		$this->load->model('transaction_log_model');
		$data = $this->transaction_log_model->getLogByCode($code);
		//print_r($data);exit;
		$this->load->view('k', array('data' => $data, 'stockCode' => $stockCode, 'stockCodes' => $stockCodes));
	}
	
	/**
	 * 获取上证的所有股票代码
	 */
	public function getShangTickers()
	{
		$file = APPPATH . 'cache/shang.html';
		$outPutFile = APPPATH . 'cache/shang.php';
		$outPutFileIni = APPPATH . 'cache/shang.ini';
		$conts = file_get_contents($file);
		
		//获取a标签的内容。
		$sContents = strip_tags($conts);
		$aContents = explode(')', $sContents);
		$aContents1 = array();
		foreach ($aContents as $val) {
			$row = array();
			$row = explode("(", trim($val));
			if (isset($row[1])) {
				$aContents1["sh_$row[1]"] = $row;
				error_log("$row[1]\n", 3, $outPutFileIni);
			}
		}
		//echo count($aContents1);exit;
		$data = var_export($aContents1, true);
		file_put_contents($outPutFile, "<?php \r\n return $data;?>");
	}
	
	/**
	 * 获取深证的所有股票代码
	 */
	public function getShenTickers()
	{
		$file = APPPATH . 'cache/shen.html';
		$outPutFile = APPPATH . 'cache/shen.php';
		$outPutFileIni = APPPATH . 'cache/shen.ini';
		$conts = file_get_contents($file);
	
		//获取a标签的内容。
		$sContents = strip_tags($conts);
		$aContents = explode(')', $sContents);
		$aContents1 = array();
		foreach ($aContents as $val) {
			$row = array();
			$row = explode("(", trim($val));
			if (isset($row[1])) {
				$aContents1["sz_$row[1]"] = $row;
				error_log("$row[1]\n", 3, $outPutFileIni);
			}
		}
		//echo count($aContents1);exit;
		$data = var_export($aContents1, true);
		file_put_contents($outPutFile, "<?php \r\n return $data;?>");
	}
	
	/**
	 * 获取中国在美上市公司的股票代码
	 */
	public function getAmercanTickers()
	{
		$file = APPPATH . 'cache/meigu.html';
		$outPutFile = APPPATH . 'cache/meigu.php';
		$outPutFileIni = APPPATH . 'cache/meigu.ini';
		$conts = file_get_contents($file);
	
		//获取a标签的内容。
		$sContents = strip_tags($conts);
		$aContents = explode(')', $sContents);
		$aContents1 = array();
		foreach ($aContents as $val) {
			$row = array();
			$row = explode("(", trim($val));
			if (isset($row[1])) {
				$aContents1["sz_$row[1]"] = $row;
				error_log("$row[1]\n", 3, $outPutFileIni);
			}
		}
		//echo count($aContents1);exit;
		$data = var_export($aContents1, true);
		file_put_contents($outPutFile, "<?php \r\n return $data;?>");
	}
	
	/**
	 * 获取上证的所有股票的交易数据
	 */
	public function getShangTickerTables()
	{
		//http://table.finance.yahoo.com/table.csv?s=600000.ss
		$baseUrl = "http://table.finance.yahoo.com/table.csv?s=";
		$file = APPPATH . 'cache/shang.php';
		$outPutBaseFile = APPPATH . 'cache/data/code/sh/';
		$erro_file = APPPATH . 'cache/log/erro.log';
		
		$data = include $file;
		
		foreach ($data as $row) {
			$url = $baseUrl . $row[1] . '.ss';
			$content = @file_get_contents($url);
			if (!$content) {
				error_log("ss:$row[1]	get failed;\r\n", 3, $erro_file);
			} else {
				file_put_contents($outPutBaseFile . $row[1] . '.csv', $content);
			}
		}
	}
	
	/**
	 * 初试添加股票
	 */
	public function initAddStocks()
	{
		//$exchanges = array('shang' => 1, 'shen' => 2, 'meigu' => 3);
		$exchanges = array('meigu' => 3);
		$this->load->model('stock_model');
		
		foreach ($exchanges as $type => $exchange) {
			$file = APPPATH . "cache/{$type}.php";
			$data = include $file;
				
			foreach ($data as $row) {
				$stock = array();
				$stock['code'] = "{$row[1]}";
				$stock['name'] = $row[0];
				$stock['exchange'] = $exchange;
				$this->stock_model->add($stock);
			}
		}
	}
	
	/**
	 * 初始化添加交易日志
	 */
	public function initAddTransationLog()
	{
		$dir = APPPATH . "cache/data/code/us";
		if (($dh = opendir($dir)) == true) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($dir."/".$file) && $file!="." && $file!="..") {
					$content = '';
					$fileName = explode('.', $file);
					$code = $fileName[0];
					$filePath =  $dir."/".$file;
					$content = file_get_contents($filePath);
					$data = explode("\n", $content);
					array_shift($data);
					
					$count = count($data);
					for ($i = $count - 1; $i >= 0; $i -=500) {		//将csv文件中的数据后面的先放入数据库。
						$sql = "INSERT INTO transaction_log(stockCode, dateTime, openPrice, highPrice, lowPrice, closePrice, adjClosePrice, volume) VALUES ";
						$inserts = array();
							
						for ($j = 0; $j < 500; $j++) {
							if (isset($data[$i - $j]) && $data[$i - $j]) {
								$row = $data[$i - $j];
								$row = explode(',', $row);
								if (count($row) != 7) {
									error_log("{$code}_{$data[$i - $j]};\r\n", 3, APPPATH . 'cache/sql/inserteror.sql');
									continue;
								} 
								$time = strtotime($row[0]);
								$inserts[] = "('{$code}', {$time}, {$row[1]}, {$row[2]}, {$row[3]}, {$row[4]}, {$row[6]}, {$row[5]})";
							} else {
								continue;
							}
						}
							
						if ($inserts) {
							$sql .= implode(',', $inserts) . ";\r\n";
							$this->db->query($sql);
							error_log($sql, 3, APPPATH . 'cache/sql/insertlog.sql');
						}
					}
				}
			}
			closedir($dh);
		}
		echo 'scuess!';
	}
	
	/**
	 * 初始化添加公司信息
	 */
	public function initAddCompanyInfo()
	{
		$dir = APPPATH . "cache/data/company/sz";
		$this->load->library('smiplehtml');
		
		if (($dh = opendir($dir)) == true) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($dir."/".$file) && $file!="." && $file!="..") {
					$content = '';
					$fileName = explode('.', $file);
					$code = $fileName[0];
					$filePath =  $dir."/".$file;
					
					$html = $this->smiplehtml->file_get_html($filePath);
					$item = $html->find('div.graybgH', 2);
					
					if (!$item) {
						continue;
					}
					error_log($code . "\r\n", 3, APPPATH . 'cache/log/addCompanyInfo.log');
					
					//http://www.tracker.com/main/initAddCompanyInfo
					$webSite = $item->find('strong', 0)->nextSibling()->plaintext;	//公司网址
					$email = $item->find('strong', 1)->nextSibling()->plaintext;	//电子信箱：
					$PublishDate = $item->find('strong', 3)->nextSibling()->plaintext;	//发行日期：
					$PublishPrice = $item->find('strong', 4)->nextSibling()->plaintext;	//发行价格：
					$InMarketDate = $item->find('strong', 5)->nextSibling()->plaintext;	//上市日期：
					$Dealer = $item->find('strong', 6)->nextSibling()->plaintext;	//主承销商：
					$InMarketRecommendPerson = $item->find('strong', 7)->nextSibling()->plaintext;	//上市推荐人
					if (!$PublishDate || !$PublishPrice || !$InMarketDate) {
						continue;
					}
						
					$sql = "INSERT INTO company_info(stockCode, webSite, email, publishDate, publishPrice, inMarketDate, dealer, inMarketRecommendPerson) VALUES ('$code', '$webSite', '$email', '$PublishDate', $PublishPrice, '$InMarketDate', '$Dealer', '$InMarketRecommendPerson');";
					//$this->db->query($sql);
					error_log($sql . "\r\n", 3, APPPATH . 'cache/sql/insertCompanyInfo.sql');
					unset($item);
					$html->clear();unset($html);
				}
			}
			closedir($dh);
		}
		echo 'scuess!';
	}
	
	/**
	 * 批量添加所有股票某天的交易记录
	 */
	public function addTransationLog()
	{
		$startDate = trim($this->input->get_post('date'));
		$code = trim($this->input->get_post('code'));
		if (!$startDate) {
			$startDate = date('Y-m-d', strtotime('-1 days'));
		}
		$startDate = explode('-', $startDate);
		
		//$url ="http://ichart.yahoo.com/table.csv?s=600000.SS&a=08&b=25&c=2010&d=09&e=8&f=2010&g=d";
		$startMonth = intval($startDate[1]) - 1;
		$url ="http://ichart.yahoo.com/table.csv?s=%s.%s&a={$startMonth}&b={$startDate[2]}&c={$startDate[0]}&d={$startMonth}&e={$startDate[2]}&f={$startDate[0]}&g=d";
		$opts = array(
  			'http'=>array(
    			'method'=>"GET",
  				'timeout'=>60,
  			)
		);
		$context = stream_context_create($opts);
		
		$this->load->model('stock_model');
		if (!$code) {
			$stocks = $this->stock_model->get(array('status' => 1));
		} else {
			$stocks = $this->stock_model->get(array('code' => $code));
		}
		
		foreach ($stocks as $stock) {
			if ($stock['exchange'] == 1) {
				$exchange = 'SS';
			} else if ($stock['exchange'] == 2){
				$exchange = 'SZ';
			} else {
				$startDay = intval($startDate[2]) - 1;	//美股时间和中国时间有时差
				$url ="http://ichart.yahoo.com/table.csv?s=%s%s&a={$startMonth}&b={$startDay}&c={$startDate[0]}&d={$startMonth}&e={$startDay}&f={$startDate[0]}&g=d";
				$exchange = '';
			}
			$code = $stock['code'];
			$dataUrl = sprintf($url, $code, $exchange);
			//echo $dataUrl;exit;
			$content = @file_get_contents($dataUrl, false, $context);
			//var_dump($content);exit;
			error_log($code . '	result:' . $content, 3, APPPATH . 'cache/log/getcode.log');
			if ($content) {			//没法排除404的情况
				$data = explode("\n", $content);
				array_shift($data);
				$count = count($data);
					
				for ($i = 0; $i < $count; $i++) {
					$sql = "INSERT INTO transaction_log(stockCode, dateTime, openPrice, highPrice, lowPrice, closePrice, adjClosePrice, volume) VALUES ";
					$inserts = array();
						
					$row = $data[$i];
					$row = explode(',', $row);
					if (count($row) != 7) {
						continue;
					}
					$time = strtotime($row[0]);
					$inserts[] = "('{$code}', {$time}, {$row[1]}, {$row[2]}, {$row[3]}, {$row[4]}, {$row[6]}, {$row[5]})";
						
					if ($inserts) {
						$sql .= implode(',', $inserts) . ";\r\n";
						//$this->db->query($sql);
						error_log($sql, 3, APPPATH . "cache/sql/addTransationLog_" . date('Y-m-d') . ".sql");
					}
				}
			} else {	//get错误的情况。
				$this->stock_model->update(array('id' => $stock['id'], 'status' => 2));
			}
		}
		
		echo 'sucess';
		
		//print_r($stocks);exit;
		
// 		$log = array();
// 		$log['stockCode'] = $code;
// 		$log['dateTime'] = $row[0];
// 		$log['openPrice'] = $row[1];
// 		$log['highPrice'] = $row[2];
// 		$log['lowPrice'] = $row[3];
// 		$log['closePrice'] = $row[4];
// 		$log['adjClosePrice'] = $row[6];
// 		$log['volume'] = $row[5];
	}
	
	/**
	 * 获取股票代码
	 */
	protected function getStockCodes()
	{
		$cacheFile = APPPATH . 'cache/stockCodes.php';
		if (file_exists($cacheFile)) {
			return include $cacheFile;
		} else {
			$this->load->model('stock_model');
			$stocks = $this->stock_model->get(array('status' => 1));
			$data = var_export($stocks, true);
			file_put_contents($cacheFile, "<?php  return $data;?>");
			return $stocks;
		}
	}
	
	public function getStocks() 
	{
		$type = $this->input->get_post('type');
		$stocks = $this->getStockCodes();
		
		$optionStr = '';
		
		foreach ($stocks as $row) {
			if ($row['exchange'] == $type) {
				$optionStr .= "<option value='{$row['code']}'>{$row['name']}</option>";
			}
		}
		
		echo json_encode(array('ret' => 1, 'data' => $optionStr));
		exit;
	}
	
	public function getStockByTpye($type) 
	{
		$stocks = $this->getStockCodes();
		
		$data = array();
		
		foreach ($stocks as $row) {
			if ($row['exchange'] == $type) {
				$data[] = $row;
			}
		}
		
		return $data;
	}
	
	/**
	 * 填充股票公司信息。
	 */
	public function fillCompanyInfo()
	{
		$this->load->model('company_model');
		$this->load->model('transaction_log_model');
		$startTime = strtotime('2013-1-1');
		//$startTime = time();
		
		//$companyInfos = $this->company_model->get(array('limit' => 1));
		$companyInfos = $this->company_model->get();
		foreach ($companyInfos as $company) {
			$update = array();
			$sql1 = "select * from transaction_log where stockCode='{$company['stockCode']}' and openPrice > 0 and dateTime < {$startTime} order by openPrice ASC LIMIT 1";
			$query1 = $this->db->query($sql1);
			$lowest = $query1->result_array();
			$lowest = array_shift($lowest);
			//print_r($lowest);exit;
			
			$sql2 = "select * from transaction_log where stockCode='{$company['stockCode']}' and openPrice > 0 and dateTime < {$startTime} order by openPrice DESC LIMIT 1";
			$query2 = $this->db->query($sql2);
			$highest = $query2->result_array();
			$highest = array_shift($highest);
			
			$sql3 = "select * from transaction_log where stockCode='{$company['stockCode']}' and dateTime < {$startTime} order by dateTime DESC LIMIT 1";
			$query3 = $this->db->query($sql3);
			$nowPrice = $query3->result_array();
			$nowPrice = array_shift($nowPrice);
			
			$update['lowestPrice'] = $lowest['openPrice'];
			$update['highestPrice'] = $highest['openPrice'];
			$update['lowestDate'] = date('Y-m-d', $lowest['dateTime']);
			$update['highestDate'] = date('Y-m-d', $highest['dateTime']);
			$update['diffPrice'] = $highest['openPrice'] - $lowest['openPrice'];
			$update['nowPrice'] = $nowPrice['closePrice'];
			$update['id'] = $company['id'];
			
			$this->company_model->update($update);
		}
		echo 'scuess!';
	}
	
	public function updateTransactionLog() 
	{
		
	}
	
	//分析公司股价
	public function parseCompany()
	{
		//SELECT `id` , `stockCode` , `publishPrice` , `inMarketDate` , `nowPrice` , `lowestPrice` , `highestPrice` , `lowestDate` , `highestDate` , `diffPrice` FROM `company_info` ORDER BY `company_info`.`diffPrice` DESC 
	}
	
	//获取黑马股
	public function getHeiMa()
	{
		$sql = "select * from company_info where diffPrice > 20 and lowestDate > '2012-01-01' and highestDate < '2011-01-01' and nowPrice < lowestPrice + 5 order by diffPrice DESC LIMIT 0,30";
		$query = $this->db->query($sql);
		$stocks = $query->result_array();
		$codes = array();
		foreach ($stocks as $row) {
			//$codes[] = $row['stockCode'] . ',' . $row['nowPrice'];
			$codes[] = $row['stockCode'];
		}
		
		echo implode(',', $codes);
		
	}

	//备份数据
	public function backUpData() 
	{
		$id = intval($this->input->get_post('id'));
		if (!$id) {
			$id = 7268276;
		}
		$sql = "SELECT * FROM `transaction_log` WHERE `id` >=$id ORDER BY `transaction_log`.`id` ASC";

		$query = $this->db->query($sql);
		$logs = $query->result_array();
		foreach ($logs as $row) {
			$sql1 = '';
			$sql1 = "INSERT INTO transaction_log(id, stockCode, dateTime, openPrice, highPrice, lowPrice, closePrice, adjClosePrice, volume) VALUES ";
			$sql1 .= "({$row['id']}, '{$row['stockCode']}', {$row['dateTime']}, {$row['openPrice']}, {$row['highPrice']}, {$row['lowPrice']}, {$row['closePrice']}, {$row['adjClosePrice']}, {$row['volume']});";
						
			error_log($sql1 . "\r\n", 3, APPPATH . "cache/sql/addTransationLog_" . date('Y-m-d') . ".sql");
		}
	}
	
	public function parseHtml() 
	{
		$this->load->view('html');	
	}

	/**
	 * 处理历史资金流向html，处理成csv文件格式。
	 */
	public function parseLszjlxHtml() 
	{
		$dir = APPPATH . "cache/data/163/lszjlx/sz/20131101";
		$this->load->library('smiplehtml');
		
		if (($dh = opendir($dir)) == true) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($dir."/".$file) && $file!="." && $file!="..") {
					$content = array();
					$fileName = explode('.', $file);
					$fileName = explode('_', $file);
					$code = $fileName[0];
					$filePath =  $dir."/".$file;
					
					$html = $this->smiplehtml->file_get_html($filePath);
					$items = $html->find('table.table_bg001 tr');

					//file_put_contents('ijibu.php', $items);exit;
					
					if (!$items) {
						continue;
					}

					foreach ($items as $key => $item) {
						if ($key == 0) {
							continue;
						}
						$var1 = $item->find('td', 0)->plaintext;	//日期
						$var2 = $item->find('td', 1)->firstChild()->plaintext;	//收盘价：
						$var3 = $item->find('td', 2)->firstChild()->plaintext;	//涨跌幅：需要转换百分比
						$var4 = $item->find('td', 3)->plaintext;	//换手率：需要转换百分比
						$var5 = $item->find('td', 4)->plaintext;	//资金流入（万元）：需要转换格式
						$var6 = $item->find('td', 5)->plaintext;	//资金流出（万元）
						$var7 = $item->find('td', 6)->firstChild()->plaintext;	//净流入（万元）
						$var8 = $item->find('td', 7)->plaintext;	//主力流入（万元）：
						$var9 = $item->find('td', 8)->plaintext;	//主力流出（万元）
						$var10 = $item->find('td', 9)->firstChild()->plaintext;	//主力净流入（万元）

						$var3 = str_replace('%', '', $var3);
						$var4 = str_replace('%', '', $var4);
						$var5 = str_replace(',', '', $var5);
						$var6 = str_replace(',', '', $var6);
						$var7 = str_replace(',', '', $var7);
						$var8 = str_replace(',', '', $var8);
						$var9 = str_replace(',', '', $var9);
						$var10 = str_replace(',', '', $var10);
							
						$content[$var1] = "'$code', '$var1', $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10";
						//$this->db->query($sql);
						unset($item);
					}
					
					unset($items);
					$html->clear();unset($html);
					
					$content = implode("\r\n", $content);
					//echo $content;

					error_log($content . "\r\n", 3, APPPATH . 'cache/sql/insertSzLszjlxInfo.log');
					error_log($content . "\r\n", 3, APPPATH . 'cache/sql/insertSzLszjlxInfo.sql');
				}
			}
			closedir($dh);
		}
		echo 'scuess!';
	}

	//http://stackoverflow.com/questions/10498632/converting-html-table-to-a-csv-automatically-using-php
	public function parseLszjlx() 
	{
		$dir = APPPATH . "cache/data/163/lszjlx/sz/error";
		$dsAllowStrings = array(',', '%');		//简单过滤掉单引号，双引号
		
		if (($dh = opendir($dir)) == true) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($dir."/".$file) && $file!="." && $file!="..") {
					$fileName = explode('.', $file);
					$fileName = explode('_', $file);
					$code = $fileName[0];
					$filePath =  $dir."/".$file;
					$outPutFile =  $dir."/csv/".$code.".csv";

					$table = file_get_contents($filePath);
					$csv = array();
					preg_match('/<div class="inner_box">
    <table(>| [^>]*>)(.*?)<\/table( |>)/is',$table,$b);
					if (!isset($b[2])) {
						error_log($file . "\r\n", 3, APPPATH . 'cache/sql/parseLszjlxErroe.log');
					} else {
						$table = $b[2];
						preg_match_all('/<tr(>| [^>]*>)(.*?)<\/tr( |>)/is',$table,$b);
						$rows = $b[2];
						foreach ($rows as $row) {
						    //cycle through each row
						    if(preg_match('/<th(>| [^>]*>)(.*?)<\/th( |>)/is',$row) && !file_exists($outPutFile)) {
						        //match for table headers
						        preg_match_all('/<th(>| [^>]*>)(.*?)<\/th( |>)/is',$row,$b);
						        $csv[] = strip_tags(implode(',',$b[2]));
						    } elseif(preg_match('/<td(>| [^>]*>)(.*?)<\/td( |>)/is',$row)) {
						        //match for table cells
						        preg_match_all('/<td(>| [^>]*>)(.*?)<\/td( |>)/is',$row,$b);
						        $items = $b[2];
						        foreach ($items as $key => $value) {
						        	$items[$key] = trim(str_replace($dsAllowStrings, "", strip_tags($value)));
						        }

						        //var_dump($items);exit;
								$csv[] = str_replace("\r\n", "", strip_tags(implode(',',$items)));
						    }
						}
						$csv = implode("\n", $csv);
						//var_dump($csv);
						file_put_contents($outPutFile, $csv."\n", FILE_APPEND);
					}
				}
			}
			closedir($dh);
		}
		echo 'scuess!';
	}

	/**
	 * 排序历史资金流向csv文件，按照日期排序。
	 */
	public function orderLszjlxCsvFile() 
	{
		$dir = APPPATH . "cache/data/163/lszjlx/sz/csv";
		
		if (($dh = opendir($dir)) == true) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($dir."/".$file) && $file!="." && $file!="..") {
					$filePath =  $dir."/".$file;

					$contents = file_get_contents($filePath);
					$contents = explode("\n", $contents);
					$header = array_shift($contents);
					$csv = array('3000-01-01' => $header);		//让header始终排序在前面。

					foreach ($contents as $value) {
						$row = array();
						$row = explode(',', $value);
						if ($row[0]) {
							$csv[$row['0']] = $value;
						}
					}
					krsort($csv);
					//print_r($csv);exit;
					$csv = implode("\n", $csv);
					//var_dump($csv);
					file_put_contents($filePath, $csv."\n");
				}
			}
			closedir($dh);
		}
		echo 'scuess!';
	}

	/**
	 * 批量处理文件编码
	 */
	public function iconv_files() 
	{
		iconv_file(APPPATH . 'cache/data/163/cjmx/sh/20131105');
	}
}