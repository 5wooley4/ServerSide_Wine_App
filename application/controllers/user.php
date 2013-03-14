<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('phpass');
        $this->load->model('user_login_model');
        $this->load->model('search_model');
        $response['error'] = false;
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
      echo json_encode($profile);
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