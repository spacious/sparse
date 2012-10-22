<?php
/**
 * Push
 */
namespace Sparse;

class Push {

    /**
     * @var Rest
     */
    protected static $_restClient;

    /**
     * @param $data - The data of the push notification. Valid fields are:
     * channels - An Array of channels to push to.
     * push_time - A Date object for when to send the push.
     * expiration_time - A Date object for when to expire the push.
     * expiration_interval - The seconds from now to expire the push.
     * where - A Parse.Query over Parse.Installation that is used to match a set of installations to push to.
     * data - The data to send as part of the push
     * @return array|null
     */
    public static function send($data){

        if(!Push::$_restClient){
            Push::$_restClient = new \Sparse\Rest();
        }

        // Rest API is a little easier
        if(!empty($data['channels']) && !empty($data['data'])){

            $params = $data;
            $data = $params['data'];
            unset($params['data']);
            $channels = $params['channels'];
            unset($params['channels']);

            return Push::$_restClient->push($channels,$data,$params);
        }

        return null;
    }
}
