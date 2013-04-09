<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @class Search
 * Search for wines
 */


class Search extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('phpass');
        $this->load->model('search_model');
        $this->load->model('user_login_model');
        
    }

    public function wine_checkins(){
      $wine_id = $this->input->post('wine_id');
      if(strlen($wine_id) < 1){
        echo "<form method=POST><input type='text' name='wine_id'/><input type='submit'/></form>";
        return;
      }
      $user_id = $this->input->post('user_id');
      //if(strlen($user_id) < 1)
        $user_id = $this->session->userdata('user_id');
      $res = $this->search_model->wine_checkins($user_id, $wine_id);

      foreach ($res as $wine) {
        $wine->friend = $this->user_login_model->user_profile_by_id($wine->friend);
        $wine->date = date("F j, Y, g:i a", strtotime($wine->date));
      }
      echo json_encode($res);

    }

    public function friends_checkins(){
      $c = (object) array("Products"=>(object) Array("List"=>Array()));
      $c->Products->List = $this->search_model->friends_wines($this->session->userdata('user_id'));
      echo json_encode($c);
    }
    /**
     * Get a wine by it's id
     * @param {post/String} wine_id
     * The wine Id you want to get the information for. Could come from cache.
     */
    public function get_wine(){
      $wine_id = $this->input->post('wine_id');
      if(strlen($wine_id) < 1)
      {
        echo "
          <form method='POST'>
            <input type='text' name='wine_id' />
            <input type='submit' />
          </form>
        ";
        return;
      }
      $cache = $this->search_model->get_wine_cache($wine_id);
      if($cache != false){
        echo $cache;
        return;
      }

      $result = file_get_contents("http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb&state=california&filter=product($wine_id)", false, $ctx);
      if($result){
        $this->search_model->add_wine_to_cache($wine_id, $result);
        echo $result;
      }
      else
        echo json_decode(Array("success"=>false, "error"=>"Wine.com is not available, please try again"));
    }
    public function recent_checkins(){
      $c = (object) array("Products"=>(object) Array("List"=>Array()));
      $c->Products->List = $this->search_model->recent_wines($this->session->userdata('user_id'));
      echo json_encode($c);

    }

    public function friend_recent_checkins($user_id){
      $c = (object) array("Products"=>(object) Array("List"=>Array()));
      $c->Products->List = $this->search_model->recent_wines($user_id);
      echo json_encode($c);
    }
    /**
     * Search for wines
     * @param  {POST/String} query
     * the search query, should be + seperated
     * 
     * @param  {POST/String} cat
     * catagory filters, should be + seperated
     * 
     * @param  {POST/String} rat
     * The ratings, should be of the form low|high
     *     @example
     *     90|100
     * @param  {POST/Integer} size
     * How many results you want.
     * 
     */
    public function Wine_Search(){
      if(!isset($_POST['query']))
        echo "
          <form method='post'>
            search <input type='text' name='query' value='goldeneye'/><br />
            cat <input type='text' name='cat' value=''/><br />
            rat <input type='text' name='rat' value=''/><br />
            size <input type='text' name='size' value='10'/><br />
            <input type='submit' />
          </form>

        ";
      $params = "";

      // Search builder
      $query = str_replace(" ", "+", $this->input->post('query'));
      if(strlen($query) > 0){
        $params .= "&search=$query";
      }
      
      // catagories builder
      $cat = str_replace(" ", "+", $this->input->post('cat'));
      if(strlen($query) > 0){
        $params .= "&filter=categories($cat)";
      }

      // ratings paramater
      $rat = str_replace(" ", "+", $this->input->post('rat'));
      if(strlen($rat) > 0){
        if(strlen($params) > 0)
          $params .= "+";
        $params .= "rating($rat)";
      }

      // size paramater
      $size = $this->input->post('size');
      if(strlen($query) > 0){
        $params .= "&size=$size";
      }
      else
        $params .= "&size=1";

      //echo $params."<hr />";
      $cache = $this->search_model->get_cache($params);
      if($cache != false){
        echo $cache;
        return;
      }
      $url = "http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb$params";
      $ctx=stream_context_create(array('http'=>
          array(
              'timeout' => 5
          )
      ));
      $result = file_get_contents($url, false, $ctx);
      if($result)
      {
        $this->search_model->add_to_cache($params, $result);
        //$result['params'] = $params;
        echo $result;
      }
      else
        echo json_decode(Array("success"=>false, "error"=>"Wine.com is not available, please try again"));
    }
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */