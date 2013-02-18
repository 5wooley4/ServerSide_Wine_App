<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class user_login_model extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    

    function create_user($data){
        if($this->check_if_user_exists($data['email']))
        {   
            $this->db->insert('users', $data);
            $this->db->insert('user_profile', array('email'=>$data['email']));
            return true;
        }
        return "Email is already in use.";

    }

    function check_if_user_exists($email){
        $this->db->where('email', $email);
        $this->db->from('users');
        if($this->db->count_all_results() > 0)
            return false;
        return true;
    }

    function get_password_hash($email)
    {
        $this->db->select('password');
        $q = $this->db->get_where('users', array('email'=>$email), 1);
        foreach($q->result() as $r)
        {
            return $r->password;
        }
    }

    function get_profile($email)
    {
        $q = $this->db->get_where('user_profile', array('email'=>$email), 1);
        foreach($q->result() as $r){
            return (array) $r;
        }
    }

    function update_profile($email, $data){
        $q = $this->db->update('user_profile', $data, array('email'=>$email), 1);
        //print_r($q);
    }
}

