<?php

class auth_other extends CI_Controller 
{	
	function __construct()
	{
		parent::__construct();
		$this->load->model('facebook_model');
		$this->load->model('user_model');
		$this->load->model('tank_auth/users');
	}
	
	// handle when users log in using facebook account
	function fb_signin()
	{
		// get the facebook user and save in the session
		$fb_user = $this->facebook_model->getUser();
		if( isset($fb_user))
		{
			$this->session->set_userdata('facebook_id', $fb_user['id']);
			$user = $this->user_model->get_user_by_facebook_id($fb_user['id']);
			if( sizeof($user) == 0) { redirect('sn_users/fill_user_info', 'refresh'); }
			else
			{
				// simulate what happens in the tank auth
				$this->session->set_userdata(array(	'user_id' => $user[0]->id, 'username' => $user[0]->username,
													'status' => ($user[0]->activated == 1) ? STATUS_ACTIVATED : STATUS_NOT_ACTIVATED));
				//$this->tank_auth->clear_login_attempts($user[0]->email); can't run this when doing FB
				$this->users->update_login_info( $user[0]->id, $this->config->item('login_record_ip', 'tank_auth'), 
												 $this->config->item('login_record_time', 'tank_auth'));
				redirect('main', 'refresh');
			}
		}
		else { echo 'canot find the Facebook user'; }
	}
	
	// called when user logs in via facebook/twitter for the first time
	function fill_user_info()
	{
		// load validation library and rules
		$this->load->config('tank_auth', TRUE);
		$this->load->library('form_validation');
		$this->form_validation->set_rules('username', 'Username', 'trim|required|xss_clean|min_length['.$this->config->item('username_min_length', 'tank_auth').']');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email|callback_email_check');
		
		// Run the validation
		if ($this->form_validation->run() == false ) 
		{
			$fb_user = $this->facebook_model->getUser();
			if( isset($fb_user))
			{
				$this->load->view('auth_other/fill_user_info');
			} 
		}
		else
		{
			$username = mysql_real_escape_string($this->input->post('username'));
			$email = mysql_real_escape_string($this->input->post('email'));
			
			/*
			 * We now must create a new user in tank auth with a random password in order
			 * to insert this user and also into user profile table with tank auth id
			 */
			$password = $this->generate_password(9, 8);
			$this->tank_auth->create_user($username, $email, $password, false);
			$new_user = $this->user_model->get_user_by_email($email);
			$user_id = $new_user[0]->id;
			if( $this->session->userdata('facebook_id')) 
			{ 
				$facebook_id = $this->session->userdata('facebook_id');
				$this->user_model->update_facebook_user_profile($user_id, $facebook_id);
			}
									
			// let the user login via tank auth
			$this->tank_auth->login($email, $password, false, false, true);
			redirect('auth', 'refresh');
		}
	}
		
	// function to validate the email input field
	function email_check($email)
	{
		$user = $this->user_model->get_user_by_email($email);
		if ( sizeof($user) > 0) 
		{
			$this->form_validation->set_message('email_check', 'This %s is already registered.');
			return false;
		}
		else { return true; }
	}
	
	// generates a random password for the user
	function generate_password($length=9, $strength=0) 
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength & 1) { $consonants .= 'BDGHJLMNPQRSTVWXZ'; }
		if ($strength & 2) { $vowels .= "AEUY"; }
		if ($strength & 4) { $consonants .= '23456789'; }
		if ($strength & 8) { $consonants .= '@#$%'; }
	 
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) 
		{
			if ($alt == 1) 
			{
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} 
			else 
			{
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}	
}

/* End of file main.php */
/* Location: ./freally_app/controllers/main.php */