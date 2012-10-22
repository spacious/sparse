<?php
/**
 * Sparse REST Client for Parse.com
 * @version 0.1
 */
namespace Sparse;

class Rest {

    public static $applicationId;
    public static $restAPIKey;

    const API_URL = 'https://api.parse.com/1/';
    const USER_AGENT = 'SparseRest/0.1';
    const OBJECT_PATH_PREFIX = 'classes';
    const USER_PATH = 'users';
    const PASSWORD_RESET_PATH = 'requestPasswordReset';
    const LOGIN_PATH = 'login';
    const PUSH_PATH = 'push';

    public $timeout = 5;
    public $sessionToken;

    protected $_response;
    protected $_responseHeaders;
    protected $_statusCode;
    protected $_results;
    protected $_errorCode;
    protected $_error;
    protected $_count;

    // Convenience Methods for Objects, Users, Push Notifications

    // Objects /////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * GET Objects
     * @url https://parse.com/docs/rest#objects-retrieving
     *
     * @param $objectClass
     * @param array $params
     * @return array
     */
    public function getObjects($objectClass,$params=array()){
        $path = $this->objectPath($objectClass);
        return $this->get($path,$params);
    }

    /**
     * GET Object
     * @url https://parse.com/docs/rest#objects-retrieving
     *
     * @param $objectClass
     * @param $objectId
     * @return array
     */
    public function getObject($objectClass,$objectId){
        $path = $this->objectPath($objectClass,$objectId);
        return $this->get($path);
    }

    /**
     * POST Object
     * @url https://parse.com/docs/rest#objects-creating
     *
     * @param $objectClass
     * @param $data
     * @return array
     */
    public function createObject($objectClass,$data){
        $path = $this->objectPath($objectClass);
        return $this->post($path,$data);
    }

    /**
     * PUT Object
     * @url https://parse.com/docs/rest#objects-updating
     *
     * @param $objectClass
     * @param $objectId
     * @param $data
     * @return array
     */
    public function updateObject($objectClass,$objectId,$data){
        $path = $this->objectPath($objectClass,$objectId);
        return $this->put($path,$data);
    }

    /**
     * DELETE Object
     * @url https://parse.com/docs/rest#objects-deleting
     *
     * @param $objectClass
     * @param $objectId
     * @return array
     */
    public function deleteObject($objectClass,$objectId){
        $path = $this->objectPath($objectClass,$objectId);
        return $this->delete($path);
    }

    // Push Notifications //////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * POST a push notification
     *
     * @url https://parse.com/docs/rest#push
     *
     * @param $channels - one or more "channels" to target
     * @param array $data - Dictionary with supported keys (or any arbitrary ones)
     *  - alert : the message to display
     *  - badge : an iOS-specific value that changes the badge of the application icon (number or "Increment")
     *  - sound : an iOS-specific string representing the name of a sound file in the application bundle to play.
     *  - content-available : an iOS-specific number which should be set to 1 to signal Newsstand app
     *  - action : an Android-specific string indicating that an Intent should be fired with the given action type.
     *  - title : an Android-specific string that will be used to set a title on the Android system tray notification.
     *
     * @param array $params - Additional params to pass, supported:
     *  - type : the "type" of device to target ("ios" or "android", or omit this key to target both)
     *  - push_time : Schedule delivery up to 2 weeks in future, ISO 8601 date or UNIX epoch time in seconds (UTC)
     *  - expiration_time : Schedule expiration, ISO 8601 date or UNIX epoch time in seconds (UTC)
     *  - expiration_interval : Set interval in seconds from push_time or now to expire
     *  - where : parameter that specifies the installation objects
     *
     * @return array
     */
    public function push($channels,$data,$params=array()){

        $path = Rest::PUSH_PATH;

        $params['channels'] = $channels;
        $params['data'] = $data;

        return $this->post($path,$params);
    }

    // Parse User //////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * GET Users
     * @url https://parse.com/docs/rest#users-retrieving
     *
     * @param array $params
     * @return array
     */
    public function getUsers($params=array()){
        $path = $this->userPath();
        return $this->get($path,$params);
    }

    /**
     * GET User
     * @url https://parse.com/docs/rest#users-retrieving
     *
     * @param $objectId
     * @return array
     */
    public function getUser($objectId){
        $path = $this->userPath($objectId);
        return $this->get($path);
    }

    /**
     * POST a new User
     *
     * @url https://parse.com/docs/rest#users-signup
     *
     * @param $username
     * @param $password
     * @param array $additional
     *
     * @return array
     */
    public function createUser($username,$password,$additional=array()){

        $path = Rest::USER_PATH;

        $required = array('username'=>$username,'password'=>$password);
        $data = array_merge($required,$additional);

        return $this->post($path,$data);
    }

    /**
     * PUT updates for a user, user must be signed in with sessionToken
     * @param $objectId
     * @param $sessionToken
     * @param $data
     * @return array
     */
    public function updateUser($objectId,$sessionToken,$data){
        $this->sessionToken = $sessionToken;
        $path = $this->userPath($objectId);
        return $this->put($path,$data);
    }

    /**
     * GET User details by logging in
     *
     * @param $username
     * @param $password
     *
     * @return array
     */
    public function login($username,$password){

        $path = Rest::LOGIN_PATH;

        $data = array('username'=>$username,'password'=>$password);

        $user = $this->get($path,$data);

        if(is_object($user)){
            if(isset($user->sessionToken)){
                $this->sessionToken = $user->sessionToken;
            }
        }

        return $user;
    }

    /**
     * POST a request for password reset for given email
     * @param $email
     *
     * @return array
     */
    public function requestPasswordReset($email){

        $path = Rest::PASSWORD_RESET_PATH;

        return $this->post($path,array('email'=>$email));
    }

    // Getters /////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return string - raw response from parse
     */
    public function response(){
        return $this->_response;
    }

    /**
     * @return mixed
     */
    public function results(){
        return $this->_results;
    }

    /**
     * @return string
     */
    public function statusCode(){
        return $this->_statusCode;
    }

    /**
     * @return string
     */
    public function errorCode(){
        return $this->_errorCode;
    }

    /**
     * @return string
     */
    public function error(){
        return $this->_error;
    }

    /**
     * @return mixed
     */
    public function responseHeaders(){
        return $this->_responseHeaders;
    }

    /**
     * @return int
     */
    public function count(){
        if($this->_count){
            return $this->_count;
        }elseif(is_array($this->results())){
            $this->_count = count($this->results());
        }
        return $this->_count;
    }

    /**
     * @return array
     */
    public function details(){
        return array(
            'response'=>$this->response(),
            'statusCode'=>$this->statusCode(),
            'error'=>$this->error(),
            'errorCode'=>$this->errorCode(),
            'results'=>$this->results(),
        );
    }

    // Generic Actions /////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * POST
     *
     * @param $path
     * @param $data
     * @return array
     */
    public function post($path,$data){
        return $this->request($path,'POST',$data);
    }

    /**
     * GET
     *
     * @param $path
     * @param array $data
     * @return array
     */
    public function get($path,$data=array()){
        return $this->request($path,'GET',$data);
    }

    /**
     * PUT
     *
     * @param $path
     * @param $data
     * @return array
     */
    public function put($path,$data){
        return $this->request($path,'PUT',$data);
    }

    /**
     * DELETE
     *
     * @param $path
     * @return array
     */
    public function delete($path){
        return $this->request($path,'DELETE');
    }

    // Protected/Private ///////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Does all actual REST calls via CURL
     *
     * @param $path
     * @param $method
     * @param array $data
     * @return array
     */
    protected function request($path,$method,$data=array()){

        $c = curl_init();
        curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($c, CURLOPT_USERAGENT, Rest::USER_AGENT);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $requestHeaders = array(
            'Content-Type: application/json',
            'X-Parse-Application-Id: '.Rest::$applicationId,
            'X-Parse-REST-API-Key: '.Rest::$restAPIKey
        );

        if($this->sessionToken){
            $requestHeaders[] = 'X-Parse-Session-Token: '.$this->sessionToken;
        }

        curl_setopt($c, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);

        $url = Rest::API_URL.$path;
        curl_setopt($c, CURLOPT_URL, $url);

        if($method == 'PUT' || $method == 'POST'){
            if(!empty($data)){
                $postData = json_encode($data);
            }else{
                $postData = json_encode((object)$data);
            }
            curl_setopt($c, CURLOPT_POSTFIELDS, $postData );
            //echo($url."<br />");
        }else if(!empty($data)){
            if(isset($data['where'])){
                $data['where'] = json_encode($data['where']);
            }
            $query = http_build_query($data, '', '&');
            //echo($url.'?'.$query."<br />");
            curl_setopt($c, CURLOPT_URL, $url.'?'.$query);
        }

        curl_setopt($c, CURLOPT_HEADER, 1);

        $response = curl_exec($c);
        $statusCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if(!$response){

            $this->_results = null;
            $this->_statusCode = 500;
            $this->_errorCode = 500;
            $this->_error = "Could not connect to Parse?";

            return $this->_results;

        }else{

            list($header, $body) = explode("\r\n\r\n", $response, 2);

            $this->_responseHeaders = $this->http_parse_headers($header);

            $this->_statusCode = $statusCode;
            $this->_response = $body;
            $this->_results = null;

            $decoded = json_decode($body);

            if(is_object($decoded)){
                if(isset($decoded->results)){
                    $this->_results = $decoded->results;
                }else{
                    if(isset($decoded->error)){
                        $this->_error = $decoded->error;
                        //echo($this->_error);
                        if(isset($decoded->code)){
                            $this->_errorCode = $decoded->code;
                        }
                    }else{
                        $this->_results = $decoded;
                    }
                }
                if(isset($decoded->count)){
                    $this->_count = (int)$decoded->count;
                }
            }

            //print_r($this->details());

            return $this->_results;
        }
    }

    /**
     * Helper method to concatenate paths for objects
     * @param $objectClass
     * @param null $objectId
     * @return string
     */
    protected function objectPath($objectClass,$objectId=null){
        $pieces = array(Rest::OBJECT_PATH_PREFIX, $objectClass);
        if($objectId){
            $pieces[] = $objectId;
        }
        return implode('/',$pieces);
    }

    /**
     * @param null $objectId
     * @return string
     */
    protected function userPath($objectId=null){
        $pieces = array(Rest::USER_PATH);
        if($objectId){
            $pieces[] = $objectId;
        }
        return implode('/',$pieces);
    }

    /**
     * From User Contributed Notes: http://php.net/manual/en/function.http-parse-headers.php
     *
     * @param $header
     * @return array
     */
    protected function http_parse_headers($header) {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }
}