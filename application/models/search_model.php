<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search_model extends CI_Model {


    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    function recent_wines($user_id){
        $this->db->select('wine_id');
        $recent_checkins = $this->db->get_where('checkins', array('user_id'=>$user_id), 10);
        $cat = "";
        $first = true;
        foreach ($recent_checkins->result() as $r) {
            if(!$first)
                $cat = $cat.'+';
            $first = false;
            $cat = $cat.$r->wine_id;
        }
        $query = "&filter=catagories($cat)";
        $res = $this->get_cache($query);
        if(!$res)
        {
            $url = "http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb$query";
            $res = file_get_contents($url);
            $this->add_to_cache($query, $res);
        }
        
        return  $res;
    }
    function get_cache($query){
        $cache = $this->db->get_where('search_cache', array("query" => $query), 1);
        foreach ($cache->result() as $c) {
             return $c->result;
        }
        return false;
    }
    function get_wine_cache($wine_id){
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

