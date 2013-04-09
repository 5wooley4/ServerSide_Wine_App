<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search_model extends CI_Model {


    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $ctx=stream_context_create(array('http'=>array('timeout' => 2)));
    }
    function wine_checkins($user_id, $wine_id){



        $wc = $this->db->select(Array('checkins.wine_id', 'friend', 'comment', 'rating', 'date', 'picture_url as wine_pic'))
            ->distinct()
            ->from('checkins')
            ->join('friends', "friends.friend = checkins.user_id")
            ->where(Array("wine_id"=>$wine_id))
            ->where("(checkins.user_id = friend OR checkins.user_id = $user_id)")
            ->order_by('Date', 'DESC')  
            ->get();

        //$wc = $this->db->get_where('checkins', array("wine_id"=>$wine_id, "user_id"=>$user_id), 10);
        return $wc->result();

    }

    function friends_wines($user_id){
        $recent_checkins = $this->db
            ->select(Array('checkins.wine_id', 'friend', 'comment', 'rating', 'picture_url'))
            ->from('checkins')
            ->join('friends', "friends.friend = checkins.user_id")
            ->where("friends.user", $user_id)
            ->order_by('Date', 'DESC')  
            ->get();
        $res = Array();
        foreach ($recent_checkins->result() as $r) {
            $w = $this->get_wine_by_id($r->wine_id);
            $w->friend = $this->user_profile_by_id($r->friend);
            $w->friend->rating = $r->rating;
            $w->friend->comment = $r->comment;
            $w->friend->user_wine_url = $r->picture_url;
            $res[] = $w;
        }
        return  $res;
    }
    function recent_wines($user_id){
        //echo $user_id."<br >";
        $recent_checkins = $this->db
            ->select('wine_id')
            ->from('checkins')
            ->where('user_id', $user_id)
            ->order_by('Date', 'DESC')
            ->get();
        //$recent_checkins = $this->db->get_where('checkins', array('user_id'=>$user_id), 10);
        $res = Array();
        foreach ($recent_checkins->result() as $r) {
            //echo "<hr/>".$r->wine_id."<br />";
            $w = $this->get_wine_by_id($r->wine_id);
            //$w->friend = $this->user_profile_by_id($r->friend);
            //echo json_encode($w);
            $res[] = $w;
        }
        return  $res;
    }
    private function user_profile_by_id($user_id)
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
    function get_cache($query){
        $cache = $this->db->get_where('search_cache', array("query" => $query), 1);

        foreach ($cache->result() as $c) {
             return $c->result;
        }
        return false;
    }

    function get_wine_by_id($wine_id)
    {
        $wine = json_decode($this->get_wine_cache($wine_id));
        //echo "<br />--".json_encode($wine)."<br /><br />";
        if(!$wine){
            $query = "&filter=product($wine_id)&size=1";
            //echo $query;
            $wine = $this->wine_api_query($query);
           // echo json_encode($wine->Products->List[0]);
            $wine = $wine->Products->List[0];
            $this->add_wine_to_cache($wine->Id, json_encode($wine));
        }
        return $wine;
    }

    function wine_api_query($query){
        $ctx=stream_context_create(array('http'=>array('timeout' => 2)));
        try{
            $url = "http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb$query";
            $res = file_get_contents($url, false, $ctx);
            if($res)
                $res = json_decode($res);
            else
                $res = json_decode(Array("success"=>false, "error"=>"Wine.com is not available, please try again"));
            return $res;
        }catch(Exception $e)
        {
            echo json_encode(Array("success"=>false, "error"=>"Wine.com Api Error, please try again later"));
            die();
        }
        
    }


    function get_wine_cache($wine_id){
        //echo "<br />-----$wine_id------<br/>";
        $cache = $this->db->get_where('wine_cache', array("wine_id" => $wine_id), 1);
        foreach ($cache->result() as $c) {
             return $c->data;
        }
        return false;
    }

    function add_to_cache($query, $result)
    {
        try{
            $this->db->insert('search_cache', array("query"=>$query, "result"=>$result));
            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
    function add_wine_to_cache($wine_id, $data)
    {
        try{
            $this->db->insert('wine_cache', array("wine_id"=>$wine_id, "data"=>$data));
            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
}

