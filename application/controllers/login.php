<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('phpass');
        $this->load->model('user_login_model');
    }

    public function index()
    {
        $this->load->view('login_page');
    }
    public function user_login($email=False, $password=False){
        // This uses the phpass library wrapper found here:
        // http://ellislab.com/forums/viewthread/212994/#987054

        $response = array();
        if(!$email)
            $email = $this->input->post('email');

        if(!$password)
            $password = $this->input->post('password');
        
        if(!$email || !$password){
            $response['error'] = "Error: email or Password not present";
            echo json_encode($response);
            return;
        }
        

        $users_password = $this->user_login_model->get_password_hash($email);
        if ($this->phpass->checkpassword($password, $users_password))
        {
            $response['user_info'] = $this->user_login_model->get_user_info($email);

            $this->session->set_userdata(array('logged_in'=>true, 'email'=>$email));

        }
        else
           $response['error'] = "Email/Password was incorrect";

        echo json_encode($response);
    }

    public function create_user($email, $password)
    {
        $response['success'] = false;
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
            $response['user_info'] = $this->user_login_model->get_user_info($email);
        }
        echo json_encode($response);

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