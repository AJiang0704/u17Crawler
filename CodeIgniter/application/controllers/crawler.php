<?php
header("content-Type: text/html; charset=utf-8");
defined('BASEPATH') OR exit('No direct script access allowed');

class Crawler extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('crawler_model');
		$this->load->library('pagination');			
	}

	#模拟登陆
	public function login() {

		$login_url = 'http://passport.u17.com/member_v2/login.php?url=http://www.u17.com/';  
		$cookie_file = dirname(__FILE__)."/u17.cookie"; 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $login_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_exec($ch);
		curl_close($ch);

		#get方式
		$get_url = 'http://www.u17.com/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $get_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		$result = curl_exec($ch);
		curl_close($ch);

		/*
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
		*/
		
	}

	/**
	 * 通过curl下载页面
	 * @access public
	 * @param string $url
	 * @return string
	 */
	public function get_Content($url) {

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$content = curl_exec($ch);
		if(curl_errno($ch)) {
			return curl_error($ch);
		}
		curl_close($ch);
		return $content;
	}

	/**
	 * 正则表达式解析页面
	 * @access public
	 * @param string $content
	 * @return array
	 */
	public function extract($content) {
		$results = array();

		/*
		 Array[1][0...] & Array[4][0...]                     ---阅读链接
		 Array[2][0...]                                      ---图片地址
		 Array[3][0...] & Array[5][0...] & Array[6][0...]    ---漫画名字
		 Array[7][0...]                                      ---漫画简介
		*/
		 $preg = "#<li> <!--  --> <!--  --> <a href=\"([^\s]+)\" class=\"comic_pic\" target=\"_blank\"> <img xsrc=\"([^\s]+)\" alt=\"\" title=\"([^\s]+)\" \/> <\/a> <a href=\"([^\s]+)\" class=\"comic_tit\" target=\"_blank\" title=\"([^\s]+)\"><i class=\"ico_rec\"><\/i>([^\s]+)<\/a> <p class=\"comic_type\">([^\s]+)<\/p> <\/li>#isU";

		preg_match_all($preg, $content, $results);
		return $results;
	}

	#获取数据并插入数据库
	public function insert() {

		$url = "http://www.u17.com/";

		$crawler = new Crawler();
		$crawler->login();
		$content = $crawler->get_Content($url);
		$results = $crawler->extract($content);

		$this->crawler_model->empty_table();

		for($i=0;;$i++) {
			if(!@$results[3][$i]) break;
			$data['name'] = $results[3][$i];
	    	$data['brief'] = $results[7][$i];
			$data['imgpath'] = $results[2][$i];
			$data['readpath'] = $results[1][$i];
			$this->crawler_model->insert($data);
		}
	}

	#分页展示
	public function index($offset = 0) {

		#配置分页信息
		$config['base_url'] = 'http://localhost/CodeIgniter/index.php/Crawler/index/';
		$config['total_rows'] = $this->crawler_model->totalpage();
		$config['per_page'] = 5;   //每页显示5条
		$config['uri_segment'] = 3;
		$config['first_link'] = '首页';
		$config['last_link'] = '尾页';
		$config['prev_link'] = '上一页';
		$config['next_link'] = '下一页';
		$config['prev_tag_open'] = '&nbsp';
		$config['prev_tag_close'] = '&nbsp';
		$config['next_tag_open'] = '&nbsp';
		$config['next_tag_close'] = '&nbsp';
		$config['num_tag_open'] = '&nbsp';
		$config['num_tag_close'] = '&nbsp';

		#初始化分页类
		$this->pagination->initialize($config);
		
		#生成分页链接
		$data['pageinfo'] = $this->pagination->create_links();

		#分页查询数据
		$limit = $config['per_page'];
		$data['display'] = $this->crawler_model->query($limit,$offset);

		$this->load->view('display.html',$data);
	}

}

?>