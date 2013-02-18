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

      $result = file_get_contents("http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb&state=california&filter=product($wine_id)");
      $result = json_decode($result);
      $result = json_encode($result->Products->List[0]);


      $this->search_model->add_wine_to_cache($wine_id, $result);

      echo $result;

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
      $query = $this->input->post('query');
      if(strlen($query) > 0){
        $params .= "&search=$query";
      }
      
      // catagories builder
      $cat = $this->input->post('cat');
      if(strlen($query) > 0){
        $filter = "&filter=categories($cat)";
      }

      // ratings paramater
      $rat = $this->input->post('rat');
      if(strlen($query) > 0){
        if(strlen($filter) > 0)
          $filter .= "+";
        $filter .= "rating($rat)";
      }

      // size paramater
      $size = $this->input->post('size');
      if(strlen($query) > 0){
        $params .= "&size=$size";
      }
      else
        $params .= "&size=10";


      $cache = $this->search_model->get_cache($params);
      if($cache != false){
        echo $cache;
        return;
      }
      $url = "http://services.wine.com/api/beta2/service.svc/json/catalog?apikey=5e8a37f198ead9d9d7ea5521a2e6bdeb&state=california$params";

      $result = file_get_contents($url);

      $this->search_model->add_to_cache($params, $result);

      echo $result;
    }
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */