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
        
        if(!$email || !$password)
            $response['error'] = "Error: email or Password not present";

        $users_password = $this->user_login_model->get_password_hash($email);
        if ($this->phpass->checkpassword($password, $users_password))
        {
            $response = $this->user_login_model->get_user_info($email);

            // Setup Logged in cookie
        }
        else
           $response['error'] = "Email/Password was incorrect";

        echo json_encode($response);
    }

    public function create_user($email, $password)
    {
        $user_data = array('email'=>$email, 'password'=>$this->phpass->createhash($password));
        $this->user_login_model->create_user($user_data);
    }
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */