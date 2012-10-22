<?php
/**
 * User
 */
namespace Sparse;

class User extends Object {

    // Class ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @var User
     */
    private static $_current;

    /**
     * @return User
     */
    public static function current(){

        if(User::$_current){
            return User::$_current;
        }else{
            session_start();
            if(!empty($_SESSION['parse-session-token']) && !empty($_SESSION['sparse-user'])){
                $attributes = json_decode($_SESSION['sparse-user']);
                User::$_current = new User($attributes);
                return User::$_current;
            }else{
                session_destroy();
            }
        }

        return null;
    }

    /**
     * Requests a password reset email to be sent to the specified email address associated with the user account.
     */
    public static function requestPasswordReset($email){

        Object::$_restClient->requestPasswordReset($email);
    }

    /**
     * @param $username
     * @param $password
     * @param array $attributes
     * @return null|\Sparse\User
     */
    public static function signUpUser($username, $password, $attributes=array()){

        $user = null;
        $created = Object::$_restClient->createUser($username,$password,$attributes);

        if(Object::$_restClient->statusCode() == 200){

            $user = new User((array)$created);
        }

        return $user;
    }

    /**
     * Logs out the currently logged in user session.
     */
    public static function logOut(){
        // current network no-op, no docs on rest API logging out
        if(User::$_current){
            User::$_current->unsetAttr('sessionToken');
            User::$_current = null;
            session_destroy();
        }
    }

    // Instance ////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Required User parameters
     * @var array
     */
    protected $parseRequiredAttributes = array('username','password');

    /**
     * Creates a new User model
     * Note: Example of an Parse.Object subclass Constructor
     * @param array $attributes
     */
    public function __construct($attributes=array()){

        parent::__construct('User',$attributes);
    }

    // API /////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks whether this user is the current user and has been authenticated.
     */
    public function authenticated(){
        $sessionToken = $this->get('sessionToken');
        return ($this->isCurrent() && $sessionToken);
    }

    /**
     * Returns get("email").
     */
    public function getEmail(){
        return $this->get("email");
    }

    /**
     * Returns get("username").
     */
    public function getUsername(){
        return $this->get("username");
    }

    /**
     * Returns true if current would return this user.
     * @return boolean
     */
    public function isCurrent(){
        return ($this == User::current());
    }

    /**
     * Logs in a Parse.User.
     */
    public function logIn(){

        $username = $this->getUsername();
        $password = $this->get('password');

        if($username && $password){

            $loggedIn = Object::$_restClient->login($username,$password);

            if(Object::$_restClient->statusCode() == 200){
                $this->clearDirtyKeys();
                $this->updateAttributes((array)$loggedIn);
                $this->unsetAttr('password');

                session_start();
                $_SESSION['parse-session-token'] = $this->sessionToken;
                $_SESSION['sparse-user'] = $this->toJSON();

                User::$_current = $this;
            }
        }
    }

    /**
     * Calls set("email", $email)
     * @param $email
     */
    public function setEmail($email){
        $this->set("email", $email);
    }

    /**
     * Calls set("password", $password)
     * @param $password
     */
    public function setPassword($password){
        $this->set("password", $password);
    }

    /**
     * Calls set("username", $username)
     * @param $username
     */
    public function setUsername($username){
        $this->set("username", $username);
    }

    /**
     * Signs up a new user.
     * @param array $attributes
     */
    public function signUp($attributes=array()){

        $this->attributes($this->mergeAttributes($attributes));

        $username = $this->getUsername();
        $password = $this->get('password');
        $additional = $this->additionalAttributes();

        if($username && $password){

            $created = Object::$_restClient->createUser($username,$password,$additional);

            if(Object::$_restClient->statusCode() == 201){
                $this->clearDirtyKeys();
                $this->updateAttributes((array)$created);
            }
        }
    }

    /**
     * Filter out required fields
     * @return array
     */
    protected function additionalAttributes(){
        $attributes = array();
        foreach($this->_attributes as $k=>$v){
            if(!in_array($k,$this->parseRequiredAttributes)){
                $attributes[$k] = $v;
            }
        }
        return $attributes;
    }
}