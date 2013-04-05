<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('phpass');
        $this->load->model('user_login_model');
        $this->load->model('search_model');
        $response['error'] = false;
    }


    public function test(){
      $res = '{"Status":{"Messages":[],"ReturnCode":0},"Products":{"List":[{"Id":120588,"Name":"Mount Veeder Winery Cabernet Sauvignon 2010","Url":"http:\/\/www.wine.com\/V6\/Mount-Veeder-Winery-Cabernet-Sauvignon-2010\/wine\/120588\/detail.aspx","Appellation":{"Id":2398,"Name":"Napa Valley","Url":"http:\/\/www.wine.com\/v6\/Napa-Valley\/wine\/list.aspx?N=7155+101+2398","Region":{"Id":101,"Name":"California","Url":"http:\/\/www.wine.com\/v6\/California\/wine\/list.aspx?N=7155+101","Area":null}},"Labels":[{"Id":"120588m","Name":"thumbnail","Url":"http:\/\/cache.wine.com\/labels\/120588m.jpg"}],"Type":"Wine","Varietal":{"Id":139,"Name":"Cabernet Sauvignon","Url":"http:\/\/www.wine.com\/v6\/Cabernet-Sauvignon\/wine\/list.aspx?N=7155+124+139","WineType":{"Id":124,"Name":"Red Wines","Url":"http:\/\/www.wine.com\/v6\/Red-Wines\/wine\/list.aspx?N=7155+124"}},"Vineyard":{"Id":999999037,"Name":"Mount Veeder Winery","Url":"http:\/\/www.wine.com\/v6\/Mount-Veeder-Winery\/learnabout.aspx?winery=157","ImageUrl":"http:\/\/cache.wine.com\/aboutwine\/basics\/images\/winerypics\/157.jpg","GeoLocation":{"Latitude":-360,"Longitude":-360,"Url":"http:\/\/www.wine.com\/v6\/aboutwine\/mapof.aspx?winery=157"}},"Vintage":"","Community":{"Reviews":{"HighestScore":5,"List":[],"Url":"http:\/\/www.wine.com\/V6\/Mount-Veeder-Winery-Cabernet-Sauvignon-2010\/wine\/120588\/detail.aspx?pageType=reviews"},"Url":"http:\/\/www.wine.com\/V6\/Mount-Veeder-Winery-Cabernet-Sauvignon-2010\/wine\/120588\/detail.aspx"},"Description":"","GeoLocation":{"Latitude":-360,"Longitude":-360,"Url":"http:\/\/www.wine.com\/v6\/aboutwine\/mapof.aspx?winery=157"},"PriceMax":39.9900,"PriceMin":24.9900,"PriceRetail":40.0000,"ProductAttributes":[{"Id":613,"Name":"Big &amp; Bold","Url":"http:\/\/www.wine.com\/v6\/Big-andamp-Bold\/wine\/list.aspx?N=7155+613","ImageUrl":""},{"Id":15419,"Name":"Great Bottles to Give","Url":"http:\/\/www.wine.com\/v6\/Great-Bottles-to-Give\/gift\/list.aspx?N=7151+15419","ImageUrl":"http:\/\/cache.wine.com\/images\/glo_icon_gift_big.gif"}],"Ratings":{"HighestScore":0,"List":[]},"Retail":null,"Vintages":{"List":[]}}],"Offset":0,"Total":63002,"Url":""}}';
      echo "<textarea>$res</textarea><hr />";
      $res = json_decode($res);

      echo "<textarea>".json_encode($res->Products)."</textarea><hr />";


    }
    public function index()
    {
      $this->check_login();
      // This is temporary until friends are setup.
      $result = $this->search_model->get_cache("&search=merlot&size=10");
      echo $result;
    }
    public function recent_checkins(){
      echo $this->user_login_model->recent_wines($this->session->userdata('user_id'));
    }
    public function fb_friends(){
      $friends = $this->input->post('friends');
      

      if(strlen($friends) > 0){ 
        $f = array();
        $friends = json_decode($friends);
        foreach ($friends as $friend) {
          $f[] = $friend->id;
        }
        $friends = $this->user_login_model->get_facebook_users($this->session->userdata('user_id'), $f);
        foreach ($friends as $f) {
          $this->user_login_model->befriend($this->session->userdata('user_id'), $f->user_id);
        }
        echo json_encode($friends);
      }
      else
        echo json_encode(array('error'=>"Facebook friends are required"));
    }

    public function fb_integrate(){
      $this->check_login();
      $fb_id = $this->input->post('fb_id');

      if(strlen($fb_id) > 0)
        echo json_encode($this->user_login_model->integrate($this->session->userdata('user_id'), $fb_id));
      else
        //echo json_encode(array('error'=>"Facebook id is required"));
        echo "<form method='post' ><input type='text' name='fb_id' /><input type='submit' /></form>";
    }

    function chlogin(){
        echo json_encode(
            array('succes'=>true, 'logged_in'=>$this->check_login(false))
        ); 
    }

    function logout(){
      $this->execute_logout();
      return array('success'=>true);
      //redirect('/user/login/');
    }

    function checkin(){
      $this->check_login();
      $wine_id = $this->input->post('wine_id');
      if(strlen($wine_id) < 1)
      {
        ?>
          <form method='POST'>
            Wine Id: <input type='text' name='wine_id' /><br />
            Comment: <input type='text' name='comment' /><br />
            rating: <input type='text' name='rating' /><br />
            <input type='submit' />
          </form>
        <?php
        return;
      }
      $user_id = $this->session->userdata('user_id');
      $comment = $this->input->post('comment');
      $rating = $this->input->post('rating');
      echo json_encode($this->user_login_model->checkin($wine_id, $user_id, $comment, $rating));
    }

    public function befriend(){
      $fr_email = $this->input->post('fr_email');
      if(strlen($fr_email) < 1){
        echo "<form method='POST'><input type='text' name='fr_email' /><br /> <input type='submit' /></form>";
        return;
      }
      $fr_id = $this->user_login_model->id_from_email($fr_email);
      echo json_encode($this->user_login_model->befriend($this->session->userdata('user_id'), $fr_id));
    }

    public function friendlist(){
      $friends = $this->user_login_model->all_my_friends($this->session->userdata('user_id'));
      echo json_encode($friends);
    }

    public function login($email=False, $password=False){
      // Make sure the user is not already logged in.
      if($this->check_login(false))
      {
        $this->execute_logout();
      }
      
      // This uses the phpass library wrapper found here:
      // http://ellislab.com/forums/viewthread/212994/#987054
      if(!isset($_POST['email']))
      {
        $this->load->view('login_form_view');
        return;
      }
      if(!$email)
          $email = $this->input->post('email');

      if(!$password)
          $password = $this->input->post('password');
      
      if(!$email || !$password){
          $response['error'] = "Error: email or Password not present";
          echo json_encode($response);
      }
      else
      {
        $users_password = $this->user_login_model->get_password_hash($email);
        if ($this->phpass->checkpassword($password, $users_password))
        {
            $response['user_info'] = $this->user_login_model->get_profile($email);
            $id = $this->user_login_model->id_from_email($email);
            $this->session->set_userdata(array('logged_in'=>true, 'email'=>$email, 'user_id' => $id));

            $response['success'] = true;

        }
        else
           $response['error'] = "Email/Password was incorrect";

        echo json_encode($response);
      }
    }

    public function register($email=false, $password=false)
    {
      if(!isset($_POST['email']))
      {
        $this->load->view('login_form_view');
        return;
      }
      $response['success'] = false;
      if(!$email)
          $email = $this->input->post('email');

      if(!$password)
          $password = $this->input->post('password');

      if(!$this->validateEmail($email))
      {
          $response['error'] = "Error: Invalid Email Address";
          echo json_encode($response);
          return;
      }
      $user_data = array('email'=>$email, 'password'=>$this->phpass->createhash($password));
      // returns true if user created, a message if it could not be.
      $user_created = $this->user_login_model->create_user($user_data);
      if($user_created !== true)
          $response['error'] = $user_created;
      else
      {
          $response['success'] = true;
          $response['user_info'] = $this->user_login_model->get_profile($email);
      }
      echo json_encode($response);

    }

    public function update_profile(){

      $this->check_login();
      if(!isset($_POST['fname']))
      {
        $this->load->view('update_profile');
        return;
      }

      $profile['fname'] = $this->input->post('fname');
      $profile['lname'] = $this->input->post('lname');
      $profile['bio'] = $this->input->post('bio');
      $this->user_login_model->update_profile($this->session->userdata('email'), $profile);
      $response['success'] = true;
      $response['user_info'] = $this->user_login_model->get_profile($this->session->userdata('email'));
      echo json_encode($response);

    }
    public function profile(){
      // This kills the page if user is not logged in.
      $this->check_login();
      $this->output->set_content_type('application/json');
      $profile = $this->user_login_model->get_profile($this->session->userdata('email'));
      $profile["following"] = $this->user_login_model->following_count($this->session->userdata('user_id'));
      $profile["follower"] = $this->user_login_model->follower_count($this->session->userdata('user_id'));
      $profile["chcount"] = $this->user_login_model->ch_count($this->session->userdata('user_id'));

      echo json_encode($profile);
    }


    function upload($type="profile"){
      $p = 'uploads/'.$this->session->userdata('user_id')."/";
      $upath = base_url().$p;
      $path = "./".$p;

      if(!is_dir($path))
        mkdir($path, 0777);

      $path = $path.$type."/";
      $upath = $upath.$type."/";
      if(!is_dir($path))
        mkdir($path, 0777);
      $config['upload_path'] = $path;
      $config['allowed_types'] = 'gif|jpg|png|jpeg';
      $config['max_size'] = '50000';
      //$config['encrypt_name'] = true;
      $config['remove_spaces'] = true;
      $config['max_width']  = 0;
      $config['max_height']  = 0;

      $this->load->library('upload', $config);



      if ( ! $this->upload->do_upload('profile_image'))
      {
        $error = $this->upload->display_errors();

        //$this->load->view('upload_form', $error);
        echo json_encode(array('success' =>  false, 'error'=>strip_tags($error)));
      }
      else
      {
        $data = $this->upload->data();
        $thumb = $this->create_thumbnail($path.$data['file_name']);
        if($type == 'profile')
          $this->user_login_model->update_user_image($this->session->userdata('user_id'), $upath.$thumb);
        echo json_encode(array('success' =>  true));
      }
      

    }


    private function create_thumbnail($image){
      $config['image_library'] = 'gd2';
      $config['source_image'] = $image;
      $config['create_thumb'] = TRUE;
      $config['maintain_ratio'] = TRUE;
      $config['width']   = 150;
      $config['height'] = 100;

      $this->load->library('image_lib', $config); 

      $this->image_lib->resize();
      $img = explode(".", $image);
      $n = count ($img);

      $image = str_replace(".".$img[$n-1], "_thumb.".$img[$n-1], $image);

      $img = explode("/", $image);
      return $img[count($img)-1];
    }


    /**
     * The following are private functions. Not to be used from the web
     */
    private function execute_logout(){
      $this->session->unset_userdata('email');
      $this->session->unset_userdata('logged_in');
    }
    private function check_login($kill=true){
      // This returns weather or not the user is logged in
      if($kill == false)
        return $this->session->userdata('logged_in');

      // if kill is true, this will return if the user is logged in
      // and kill the page if they are not.
      if($this->session->userdata('logged_in'))
        return;

      $response['error'] = "user is not logged in";
      $response['success'] = false;
      json_encode($response);
      die();
    }
    private function validateEmail($email)
      {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex)
        {
          $isValid = false;
        }
        else
        {
          $domain    = substr($email, $atIndex + 1);
          $local     = substr($email, 0, $atIndex);
          $localLen  = strlen($local);
          $domainLen = strlen($domain);
          if ($localLen < 1 || $localLen > 64)
            {
              $isValid = false;
            }
          else if ($domainLen < 1 || $domainLen > 255)
            {
              $isValid = false;
            }
          else if ($local[0] == '.' || $local[$localLen - 1] == '.')
            {
              $isValid = false;
            }
          else if (preg_match('/\\.\\./', $local))
            {
              $isValid = false;
            }
          else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
            {
              $isValid = false;
            }
          else if (preg_match('/\\.\\./', $domain))
            {
              $isValid = false;
            }
          else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local)))
            {
              if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local)))
                {
                  $isValid = false;
                }
            }
          if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A")))
            {
              $isValid = false;
            }
        }
        return $isValid;
      }
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */