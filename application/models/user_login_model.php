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
        if($this->db->insert('users', $data))
            return true;
        return false;

    }

    function get_password_hash($email)
    {
        $this->db->select('password');
        $q = $this->db->get_where('users', array('email'=>$email), 1);
        foreach($q->result() as $r){
            print_r($r);
            return $r->password;
        }
    }

    function get_last_ten_entries()
    {
        $query = $this->db->get('entries', 10);
        return $query->result();
    }

    function insert_entry()
    {
        $this->title   = $_POST['title']; // please read the below note
        $this->content = $_POST['content'];
        $this->date    = time();

        $this->db->insert('entries', $this);
    }

    function update_entry()
    {
        $this->title   = $_POST['title'];
        $this->content = $_POST['content'];
        $this->date    = time();

        $this->db->update('entries', $this, array('id' => $_POST['id']));
    }

}

