<?php
header("content-Type: text/html; charset=utf-8");
defined('BASEPATH') OR exit('No direct script access allowed');

class Crawler_model extends CI_Model {

	const TBL = 'u17';

	#载入数据库操作类
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 插入数据库
	 * @access public
	 * @param array $data
	 * @return bool
	 */
	public function insert($data) {
		return $this->db->insert(self::TBL,$data);
	}

	/**
	 * 分页查询数据库
	 * @access public
	 * @param  $limit:每页显示条数  $offset:偏移量
	 * @return array
	 */
	public function query($limit,$offset) {
		$data = $this->db->get(self::TBL,$limit,$offset);
		return $data->result_array();
	}

	#查询数据总条数
	public function totalpage() {
		return $this->db->count_all(self::TBL);
	}

	#清空数据表
	public function empty_table() {
		$this->db->truncate(self::TBL);
	}
}

?>


