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

    function get_facebook_users($user_id, $friends){
        echo $user_id . " - ";
        echo json_encode($friends);
        //$this->db->where('user_id', $user_id);
        $this->db->where_in('fb_id', $friends);
        $this->db->from('fb_integration');
        $res = $this->db->get();
        return $res->result();

    }
    function update_user_image($user_id, $url){
        $data = array("picture_url"=>$url);
        $q = $this->db->update('user_profile', $data, array('id'=>$user_id), 1);
    }
    function follower_count($user_id){
        $this->db->select('*')
            ->from('friends')
            ->where('friend', $user_id);
            //->get();
            //echo $this->db->last_query();
        return $this->db->count_all_results();
    }
    function following_count($user_id){
        $this->db->select('*')
            ->from('friends')
            ->where('user', $user_id);
            //->get();
        //echo $this->db->last_query();
        return $this->db->count_all_results();
    }
    function ch_count($user_id){
        $this->db->select('*')
            ->from('checkins')
            ->where('user_id', $user_id);
            //->get();
        //echo $this->db->last_query();
        return $this->db->count_all_results();
    }
    function integrate($user_id, $fb_id)
    {
        try{
                $this->db->set('user_id', $user_id);
                $this->db->set('fb_id', $fb_id);
                $this->db->insert('fb_integration');
                return array('success' => true);
        }
        catch(Exception $e){
            return array('success'=>false, "error"=>"checkin error: " . e);
        }
        
    }

    function checkin($wine_id, $user_id, $comment, $rating){
       /* $this->db->where('User_id', $user_id);
        $this->db->where('Wine_id', $wine_id);
        $this->db->from('checkins');
        $count = $this->db->count_all_results();
        if($count > 0)
            return array('success'=>false, "error"=>"You have already checked into this wine");
        */
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
        $this->db->select('fname, lname, bio, picture_url, friend as user_id');
        $this->db->join('user_profile', "friends.friend = user_profile.ID");
        $q = $this->db->get();

        $friends = $q->result();
        foreach ($friends as $f) {
            $this->db->select('*')
            ->from('checkins')
            ->where('user_id', $f->user_id);
            $f->checkin_count =  $this->db->count_all_results();

            $this->db->select('*')
            ->from('friends')
            ->where('friend', $f->user_id);
            $f->follower_count =  $this->db->count_all_results();


            $this->db->select('*')
            ->from('friends')
            ->where('user', $f->user_id);
            $f->following_count =  $this->db->count_all_results();
        }
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
    function user_profile_by_id($user_id)
    {
        $res = $this->db
            ->select('*')
            ->from('user_profile')
            ->where('id', $user_id)
            ->get();
        foreach($res->result() as $r)
        {
            return $r;
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

