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
    
    function befriend($user_id, $fr_id){
        if(!$fr_id)
            return array('error'=>"Friend does not exist", 'success'=>false);
        $this->db->where('user', $user_id);
        $this->db->where('friend', $fr_id);
        $this->db->from('friends');
        $count = $this->db->count_all_results();
        if($count < 1)
        {
            $this->db->set('user', $user_id);
            $this->db->set('friend', $fr_id);
            $this->db->insert('friends');
            return array('success'=>true);
        }
        return array('error'=>"You are already friends", 'success'=>false);
    }

    function checkin($wine_id, $user_id, $comment, $rating){
        $this->db->where('User_id', $user_id);
        $this->db->where('Wine_id', $wine_id);
        $this->db->from('checkins');
        $count = $this->db->count_all_results();
        if($count > 0)
            return array('success'=>false, "error"=>"You have already checked into this wine");

        if(!$wine_id || !$user_id)
            return array('success'=>false, "error"=>"User id and Wine id are required");

        $this->db->set('User_id', $user_id);
        $this->db->set('Wine_id', $wine_id);
        $this->db->set('comment', $comment);
        $this->db->set('rating', $rating);
        if($rating > 5)
            $rating = 5;
        try{
            $this->db->insert('checkins');
            return array('success'=>true);
        }
        catch(Exception $e){
            return array('success'=>false, "error"=>"checkin error: " . e);
        }
    }

    function all_my_friends($user_id){
        $this->db->where('user', $user_id);
        $this->db->from('friends');
        $this->db->select('fname, lname, bio, picture_url');
        $this->db->join('user_profile', "friends.friend = user_profile.ID");
        $q = $this->db->get();
        //$i = 0;
        //foreach ($q->result() as $v) {
        //    echo $ret[$i] = (array) $v;
        //}
        return $q->result();
    }

    function id_from_email($email)
    {
        $this->db->select('ID');
        $id = $this->db->get_where('users', array('email' => $email), 1);
        foreach ($id->result() as $ret) {
            return $ret->ID;
        }
    }
    function create_user($data){
        if(!$this->check_if_user_exists($data['email']))
        {   
            $this->db->insert('users', $data);
            $id = $this->id_from_email($data['email']);
            $this->db->insert('user_profile', array('ID'=>$id, 'email'=>$data['email']));

            return true;
        }
        return "Email is already in use.";

    }

    function check_if_user_exists($email){
        $this->db->where('email', $email);
        $this->db->from('users');
        if($this->db->count_all_results() > 0)
            return true;
        return false;
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

