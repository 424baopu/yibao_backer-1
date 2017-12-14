<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
* 
*/
class Books_model extends CI_Model
{
	
	function __construct()
	{
		# code...
	}

	function donate_books($data)
	{
		$query= $this->db->insert('books',$data);
		return $query? $this->db->insert_id():flase;
	}

	function donate_books_image($data)
	{
		$query= $this->db->insert('books_image',$data);
		return $query? $this->db->insert_id():flase;
	}

	function show_books_by_bid($bid)
	{
		/*获取图书信息*/
		$query = $this->db->get_where('books',array('bid' => $bid));
		$query = $query->result_array()[0];//只有一行数据

		/*处理数据*/
		//echo $query['deadline'];
		if($query['deadline'] == 0)
		{
			$query['deadline'] = "--:--";
		}
		else
		{
			$query['deadline'] = date("Y-m-d ", $query['deadline']);
		}
		
		/*根据用户名获取昵称，头像路径*/
		$res = $this->db->get_where('user',array('sno' => $query['sno']));
		// print_r($res->result_array());//test
		$res = $res->result_array()[0];
		// print_r($res);

		$query['nickname'] = $res['nickname'];
		$query['avatar_path'] = $res['avatar_path'];
		
		/*获取图书图片*/
		$photo = $this->db->get_where('books_image',array('bid' => $bid));
		$photo = $photo->result_array();
		$photo = array_column($photo, 'path');//仅需要提取路径一列
		$query['photo'] = $photo;
		/*如果没有图片，使用默认图片*/
		if(count($photo) == 0)
		{
			$query['photo'][0] = "public/goods/default.jpg";
		}
		
		//print_r($query);
		return $query;
	}

	/*暂时不做分页，$page虚置*/
	function show_books_by_type($type,$page)
	{

		/*先得到类型范围，如12300，可选范围为12300到12399*/
		$string = (string)$type;
		$range = 1;
		for ($i=strlen($string) - 1; $i > 0 ; $i--) 
		{ 
			if($string[$i] != 0)
			{
				$range--;
				break;
			}
			$range *= 10;
		}
		// echo $range;
		$limit = 10;//每次取10条数据
		$this->db->order_by('status', 'DESC');
		$this->db->where('type >=', $type);
		//$this->db->where('type <=', 21998);//?蜜汁bug $type《= type《=21999就没有查询语句，21998还有(因为自己cache)
		$this->db->where('type <=', $type + $range);
		$query = $this->db->get('books', $limit, $page*$limit)->result_array();

        // $query = $this->db->get('books')->result_array();
		// print_r($query);

		$bids = array_column($query, 'bid');

		if(count($bids) == 0) 
		{
			return null;
		}
		// $res[0] = "暂无信息";
		for ($i=0; $i < count($bids); $i++) 
		{ 
			$res[$i] = $this->Books_model->show_books_by_bid($bids[$i]);
		}
		return $res;

	}

	public function rent_books($bid,$days,$sno)
	{
		$res = $this->db->get_where('books', array('bid' => $bid));
		$res = $res->result_array()[0];
		//print_r($res);

		if($res['status'] == 0)
		{
			return 0;
		}
		/*修改租借次数，到期时间，租借状态*/
		$rent_times = $res['rent_times']+1;
		$deadline = time() + $days*86400;
		
		$this->db->where('bid', $bid);
		$this->db->set('rent_sno',$sno);//暂时无法测试，注释
		$this->db->set('status', 0);
		$this->db->set('rent_times', $rent_times);
		$this->db->set('deadline', $deadline);

		$query = $this->db->update('books');
		if($query == false)
		{
			return 1;
		}

		return 2;
	}


	public function return_books($bid, $sno)
	{
		$res = $this->db->get_where('books', array('bid' => $bid));
		$res = $res->result_array()[0];

		//非借书人操作
		if($res['rent_sno'] != $sno) {
			return 0;
		}

		//如果当前有人预约，修改当前预约人为借书人，预约状态为可预约
		$this->db->where('bid', $bid);
		$this->db->where('rent_sno', $sno);
		if($res['wait_status'] == 0) {
			$this->db->set('rent_sno', $res['wait_sno']);
			$this->db->set('wait_sno',"");
			$this->db->set('wait_status',1);
			$deadline = time() + 30*86400;//?
			
		}
		else {
			$this->db->set('rent_sno',"");
			$this->db->set('status', 1);
			$deadline = 0;
		}
		$this->db->set('deadline', $deadline);
		
		
		$query = $this->db->update('books');
		if($query == false)
		{
			return 1;
		}

		return 2;
	}

	public function order_books($bid,$sno)
	{
		$res = $this->db->get_where('books', array('bid' => $bid));
		$res = $res->result_array()[0];
		//print_r($res);

		if($res['wait_status'] == 0)
		{
			return 0;
		}
		/*修改预约状态，预约人*/		
		$this->db->where('bid', $bid);
		$this->db->set('wait_sno',$sno);
		$this->db->set('wait_status', 0);

		$query = $this->db->update('books');
		if($query == false)
		{
			return 1;
		}

		return 2;
	}



	public function bid_is_exist($bid)
	{
		$query = $this->db->get_where('books',array('bid' => $bid));
		$res = $query->num_rows();
		return $res;
	}
}
?>