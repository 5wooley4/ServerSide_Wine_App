<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search_model extends CI_Model {


    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
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

