<?php

class user_model extends CI_Model 
{		
	// for finding user via facebook id
	function get_user_by_facebook_id($facebook_id)
	{
		$query = $this->db->query("SELECT users.*, user_profiles.facebook_id " .
								  "FROM users " .
								  "JOIN user_profiles ON users.id=user_profiles.user_id " .
								  "WHERE facebook_id='$facebook_id'");		
		return $query->result();
	}

	// Returns user by its email
	function get_user_by_email($email)
	{
		$query = $this->db->query("SELECT * FROM users u, user_profiles up WHERE u.email='$email' and u.id = up.user_id");
		return $query->result();
	}
	
	// update user profile
	function update_facebook_user_profile($user_id, $facebook_id)
	{
		$query = $this->db->query("UPDATE user_profiles SET facebook_id='$facebook_id' WHERE user_id='$user_id'");
	}

	// return the user given the id
	function get_user($user_id)
	{
		$query = $this->db->query("SELECT users.*, user_profiles.* FROM users, user_profiles WHERE " .
								  "users.id='$user_id' AND user_profiles.user_id='$user_id'");
		return $query->result();
	}
}
?>