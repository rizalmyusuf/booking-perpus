<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {
	public function __construct() {
			parent:: __construct();
			$this->load->model('BP_model');
			$this->updateBookPerDay();
	}

	public function index(){
		if ($this->session->userdata("logged_in") == true){
			if($_SESSION['rl']=='administrator'){
				$dataLO['dataUserLO'] = $this->BP_model->queryRunning("SELECT * FROM library_officer");
				$dataCS['dataUserCS'] = $this->BP_model->queryRunning("SELECT * FROM college_student");
				$this->load->view('admin/va_users',$dataLO+$dataCS);
			}elseif ($_SESSION['rl']=='lo') {
				$this->session->set_flashdata('err','Anda bukan administrator.');
				redirect('petugas');
			}elseif ($_SESSION['rl']=='cs') {
				$this->session->set_flashdata('err','Anda bukan administrator.');
				redirect('mahasiswa');
			}else {
				$this->session->set_flashdata('err','Role tidak diketahui, coba lagi!');
				redirect('login/logout');
			}
		} else {
			$this->sessionTimedOut();
		}
	}

	public function sessionTimedOut(){
		if ($this->session->userdata("logged_in") == false){
			$this->session->set_flashdata('err','Masuk terlebih dahulu!');
			redirect('login');
		}
	}

	public function updateBookPerDay(){
		date_default_timezone_set('Asia/Jakarta');
		$day=date('Y-m-d',strtotime('yesterday'));
		$books=$this->BP_model->queryRunning("SELECT * FROM reservation WHERE check_in<='$day' AND status!='OUT'");
		foreach ($books as $row) {
			$this->BP_model->update('books',array('borrowed_by' => NULL),$row->book_id);
		}
		$this->BP_model->queryRunning("UPDATE reservation SET status='OUT' WHERE check_in<='$day' AND status!='OUT'",1,1);
	}

	public function createUserConfirm(){
		$this->sessionTimedOut();
		if ($this->input->post('role')=='lo') {
			$table='library_officer';
			$hash_addition='BookingPerpusLibraryOfficer';
		}elseif ($this->input->post('role')=='cs') {
			$table='college_student';
			$hash_addition='BookingPerpusCollegeStudent';
		}

		$data = array(
			'username' => $this->input->post('un'),
			'password' => md5($hash_addition.$this->input->post('pwd')),
			'id_number' => $this->input->post('idn'),
			'fullname' => $this->input->post('fn')
		);

		if ($this->BP_model->read($table,array('username' => $this->input->post('un')))) {
			$this->session->set_flashdata('err','Username sudah dipakai.');
			redirect("admin");
		}else{
			$this->BP_model->create($table,$data);
			$this->session->set_flashdata('succ','User telah dibuat.');
			redirect("admin");
		}
	}

	public function editUserConfirm($UserId){
		$this->sessionTimedOut();
		if ($this->input->post('role')=='lo') {
			$table='library_officer';
			$hash_addition='BookingPerpusLibraryOfficer';
		}elseif ($this->input->post('role')=='cs') {
			$table='college_student';
			$hash_addition='BookingPerpusCollegeStudent';
		}

		if ($this->input->post('unOld')==$this->input->post('un')) {
			$unNew=$this->input->post('unOld');
		}else{
			$unNew=$this->input->post('un');
			$cekUN=$this->BP_model->read($table,array('username' => $this->input->post('un')));
			if ($cekUN) {
				$this->session->set_flashdata('err','Username sudah dipakai.');
				redirect("admin");
			}
		}

		if($this->input->post('pwd')!=NULL){
			$pwd=md5($hash_addition.$this->input->post('pwd'));
		}else{
			$pwd=md5($this->input->post('oldPwd'));
		}

		$data = array(
			'username' => $unNew,
			'password' => $pwd,
			'id_number' => $this->input->post('idn'),
			'fullname' => $this->input->post('fn')
		);
		$this->BP_model->update($table,$data,$UserId);
		$this->session->set_flashdata('succ','User telah disunting.');
		redirect("admin");
	}

	public function deleteUserConfirm($role,$DeleteId){
		$this->sessionTimedOut();
		if ($role=='lo') { $table='library_officer';}
		elseif ($role=='cs') { $table='college_student';}
		$this->BP_model->delete($table,$DeleteId);
		$this->session->set_flashdata('succ','User telah dihapus.');
		redirect("admin");
	}
}
