<?php
/**
 *
 */

namespace Sparse;

class Cloud extends Sparse {

    const PATH_CLOUD = 'functions';

    /**
     * @param $name
     * @param $data
     * @return array|null
     */
    public static function run($name,$data=array()){

        if(!Sparse::$_restClient){
            Sparse::$_restClient = new Rest();
        }

        $result = Sparse::$_restClient->post(Cloud::PATH_CLOUD.'/'.$name,$data);

        if(is_object($result) && isset($result->result)){
            return $result->result;
        }

        return null;
    }
}
