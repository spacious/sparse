<?php
/**
 * PHP Parse Query Implementation
 * @version 0.1
 */
namespace Sparse;

class Query {

    // Class ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    const WHERE_OPT_IN = '$in';
    const WHERE_OPT_NOT_IN = '$nin';
    const WHERE_OPT_REGEX = '$regex';
    const WHERE_OPT_PCRE_OPTIONS = '$options';

    public static function queryWithOrQueries($queries=array()){
        if($queries && is_array($queries) && count($queries)){
            $query = new Query($queries[0]->className);
            $query->matchesOrQueries($queries);
            return $query;
        }
        return null;
    }

    // Instance ////////////////////////////////////////////////////////////////////////////////////////////////////////

    public $objectClass;

    /**
     * Unique!! key to use as the index for find() results
     * Defaults to objectId, set to null to disable
     * @var string
     */
    public $indexKey = 'objectId';

    protected $_rest;
    protected $_where = array();
    protected $_order = array();
    protected $_include = array();
    protected $_limit = 0;
    protected $_skip = 0;
    protected $_count = 0;

    /**
     * Creates a new parse Parse.Query for the given Parse.Object subclass.
     * @param $objectClass
     */
    public function __construct($objectClass){

        $this->objectClass = $objectClass;
        $this->_rest = new \Sparse\Rest();
    }

    // API /////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the seldom used 'arrayKey'
     * Note: Addition to SDK
     * @param $n
     */
    public function arrayKey($n){
        $this->_where['arrayKey'] = $n;
    }

    /**
     * Sorts the results in ascending order by the given key.
     * @param $key
     */
    public function ascending($key){

        $this->_order[] = $key;
    }

    public function className(){
        if($this->objectClass == \Sparse\Object::USER_OBJECT_CLASS){
            return '_'.$this->objectClass;
        }
        return $this->objectClass;
    }

    /**
     * Returns a new instance of Parse.Collection backed by this query.
     *
     * @param $items
     * @param $options
     */
    public function collection($items, $options){
        // no-op
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be contained in the provided list of values.
     *
     * @param $key
     * @param $values
     */
    public function containedIn($key, $values){

        $this->setWhereKeyHashValue($key,'$in',$values);
    }

    /**
     * Add a constraint for finding string values that contain a provided string.
     * @param $key
     * @param $substring
     */
    public function contains($key, $substring){

        $this->setWhereKeyHashValue($key,Query::WHERE_OPT_REGEX,$substring);
    }

    /**
     * Counts the number of objects that match this query.
     *
     * @return int
     */
    public function count(){

        if($this->_count > 0){
            return $this->_count;
        }

        $this->_count = 0;
        $params = $this->preparedQueryParameters();
        // Add count=1 and limit=0 to only return count
        $params['count'] = 1;
        $params['limit'] = 0;

        $this->_find($params);
        if($this->_rest->statusCode() == 200){
            $this->_count = $this->_rest->count();
        }
        return $this->_count;
    }

    /**
     * Sorts the results in descending order by the given key.
     * @param $key
     */
    public function descending($key){

        $this->_order[] = '-'.$key;
    }

    /**
     * Add a constraint for finding objects that do not contain a given key.
     *
     * @param $key
     * @return void
     */
    public function doesNotExist($key){

        $this->setWhereKeyHashValue($key,'$exists',false);
    }

    /**
     * Add a constraint that requires that a key's value not matches a Parse.Query constraint.
     * @param $key
     * @param $query
     */
    public function doesNotMatchQuery($key, $query){

        //'$notInQuery'
    }

    /**
     * Add a constraint for finding string values that end with a provided string.
     * @param $key
     * @param $suffix
     */
    public function endsWith($key, $suffix){

        $this->setWhereKeyHashValue($key,Query::WHERE_OPT_REGEX,$suffix.'$');
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be equal to the provided value.
     * @param $key
     * @param $value
     */
    public function equalTo($key, $value){

        $this->_where[$key] = $value;
    }

    /**
     * Add a constraint for finding objects that contain the given key.
     * @param $key
     */
    public function exists($key){

        $this->setWhereKeyHashValue($key,'$exists',true);
    }

    /**
     * Retrieves a list of ParseObjects that satisfy this query.
     *
     * @return array
     */
    public function find(){

        $params = $this->preparedQueryParameters();
        $objects = array();

        // return total count
        $params['count'] = 1;
        $this->_count = 0;

        $found = $this->_find($params);

        if($this->_rest->statusCode() == 200){
            $this->_count = $this->_rest->count();
            $indexKey = $this->indexKey;
            foreach($found as $attributes){
                if($indexKey){
                    $index = isset($attributes->$indexKey) ? $attributes->$indexKey : count($objects);
                }else{
                    $index = count($objects);
                }

                if($this->objectClass == Object::USER_OBJECT_CLASS){
                    $objects[$index] = new \Sparse\User($attributes);
                }else{
                    $objects[$index] = new \Sparse\Object($this->objectClass,$attributes);
                }
            }
        }

        return $objects;
    }

    /**
     * Retrieves at most one Parse.Object that satisfies this query.
     */
    public function first(){

        $this->limit(1);

        $objects = $this->find();

        if($objects && count($objects)>0){
            return array_pop($objects);
        }

        return null;
    }

    /**
     * Constructs a Parse.Object whose id is already known by fetching data from the server.
     * @param $objectId
     * @return array
     */
    public function get($objectId){

        return $this->_rest->getObject($this->objectClass,$objectId);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be greater than the provided value.
     * @param $key
     * @param $value
     */
    public function greaterThan($key, $value){

        $this->setWhereKeyHashValue($key,'$gt',$value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be greater than or equal to the provided value.
     * @param $key
     * @param $value
     */
    public function greaterThanOrEqualTo($key, $value){

        $this->setWhereKeyHashValue($key,'$gte',$value);
    }

    /**
     * mutates where
     */
    protected function setWhereKeyHashValue($whereKey,$key,$value){
        // If equals value was defined, this will override it
        if(!isset($this->_where[$whereKey]) || !is_array($this->_where[$whereKey])){
            $this->_where[$whereKey] = array();
        }
        $this->_where[$whereKey][$key] = $value;
    }

    /**
     * Include nested Parse.Objects for the provided key.
     *
     * Note: Changed from include to includeObject
     */
    public function includeObject($key){
        $this->_include[] = $key;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be less than the provided value.
     * @param $key
     * @param $value
     */
    public function lessThan($key, $value){

        $this->setWhereKeyHashValue($key,'$lt',$value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be less than or equal to the provided value.
     * @param $key
     * @param $value
     */
    public function lessThanOrEqualTo($key, $value){

        $this->setWhereKeyHashValue($key,'$lte',$value);
    }

    /**
     * Sets the limit of the number of results to return.
     * @param $n
     */
    public function limit($n){
        $this->_limit = $n;
    }

    /**
     * Add a regular expression constraint for finding string values that match the provided regular expression.
     * @param $key
     * @param $regex
     * @param $modifiers
     */
    public function matches($key, $regex, $modifiers=null){

        $this->setWhereKeyHashValue($key,'$regex',$regex);
        if(!empty($modifiers)){
            $this->setWhereKeyHashValue($key,'$options',$modifiers);
        }
    }

    /**
     * Add a constraint that requires that a key's value matches a value in an object returned by a different Parse.Query.
     * @param $key
     * @param $queryKey
     * @param $query
     */
    public function matchesKeyInQuery($key, $queryKey, $query){

        //'$select'
        // Not yet implemented
    }

    /**
     * @param array $queries
     */
    public function matchesOrQueries($queries=array()){

        $this->_where['$or'] = $queries;
    }

    /**
     * Add a constraint that requires that a key's value matches a Parse.Query constraint.
     *
     * @param $key
     * @param $query
     */
    public function matchesQuery($key, $query){
        //'$inQuery'
        $this->_where[$key] = array('$inQuery'=>$query);
    }

    /**
     * Add a proximity based constraint for finding objects with key point values near the point given.
     * @param $key
     * @param $point
     */
    public function near($key, $point){
        // Not yet implemented
    }

    /**
     * Add a constraint to the query that requires a particular key's value to not be contained in the provided list of values.
     * @param $key
     * @param $values
     */
    public function notContainedIn($key, $values){

        $this->setWhereKeyHashValue($key,'$nin',$values);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to be not equal to the provided value.
     * @param $key
     * @param $value
     */
    public function notEqualTo($key, $value){

        $this->setWhereKeyHashValue($key,'$ne',$value);
    }

    /**
     * TODO: this may change or used as toJSON
     * @return array
     */
    public function preparedQueryParameters(){
        $params = array();
        if(!empty($this->_include)){
            $params['include'] = implode(',',$this->_include);
        }

        if(!empty($this->_order)){
            $params['order'] = implode(',',$this->_order);
        }

        if(!empty($this->_where)){
            $params['where'] = $this->formatAttributesForTransfer($this->_where);
        }

        if(!empty($this->_limit)){
            $params['limit'] = $this->_limit;
        }

        if(!empty($this->_skip)){
            $params['skip'] = $this->_skip;
        }
        return $params;
    }

    /**
     *
     * Note: Addition to SDK
     * @param $key
     * @param $object
     */
    public function relatedTo($key,$object){
        //'$relatedTo'
    }

    /**
     * Sets the number of results to skip before returning any results.
     * @param $n
     */
    public function skip($n){
        $this->_skip = $n;
    }

    /**
     * Add a constraint for finding string values that start with a provided string.
     *
     * @param $key
     * @param $prefix
     */
    public function startsWith($key, $prefix){

        $this->setWhereKeyHashValue($key,Query::WHERE_OPT_REGEX,'^'.$prefix);
    }


    /**
     * Returns a JSON representation of this query.
     */
    public function toJSON(){

    }

    /**
     * @return array
     */
    public function where(){
        return $this->_where;
    }


    /**
     * Add a constraint to the query that requires a particular key's coordinates be contained within a given rectangular geographic bounding box.
     * @param $key
     * @param $southwest
     * @param $northeast
     */
    public function withinGeoBox($key, $southwest, $northeast){

    }

    /**
     * Add a proximity based constraint for finding objects with key point values near the point given and within the maximum distance given.
     *
     * @param $key
     * @param $point
     * @param $maxDistance
     */
    public function withinKilometers($key, $point, $maxDistance){

    }

    /**
     * Add a proximity based constraint for finding objects with key point values near the point given and within the maximum distance given.
     * @param $key
     * @param $point
     * @param $maxDistance
     */
    public function withinMiles($key, $point, $maxDistance){

    }

    /**
     * Add a proximity based constraint for finding objects with key point values near the point given and within the maximum distance given.
     * @param $key
     * @param $point
     * @param $maxDistance
     */
    public function withinRadians($key, $point, $maxDistance){

    }

    protected function _find($params){

        if($this->objectClass == \Sparse\Object::USER_OBJECT_CLASS){
            $found = $this->_rest->getUsers($params);
        }else{
            $found = $this->_rest->getObjects($this->objectClass,$params);
        }

        return $found;
    }

    protected function formatValue($value){

        if(is_array($value)){


        }elseif(is_object($value)){
            // Duck type Sparse\Object, subclasses or anything with valid interface
            // exposing 'pointer'
            if(method_exists($value,'pointer')){
                $value = $value->pointer();
            }
        }

        return $value;
    }

    protected function formatAttributesForTransfer($attributes){
        $formatted = array();
        foreach($attributes as $key=>$value){

            if(is_array($value)){

                if($key=='$or'){

                    $prepared = array();
                    foreach($value as $query){
                        // Duck type Sparse\Query, subclasses or anything with valid interface
                        // exposing 'preparedQueryParameters'
                        if(method_exists($query,'preparedQueryParameters')){
                            $params = $query->preparedQueryParameters();
                            if(!empty($params['where'])){
                                $prepared[] = $params['where'];
                            }
                        }
                    }
                    $formatted[$key] = $prepared;

                    // Duck type Sparse\Query, subclasses or anything with valid interface
                    // exposing 'preparedQueryParameters' && 'className'
                }elseif(isset($value['$inQuery']) && (method_exists($value['$inQuery'],'preparedQueryParameters') && method_exists($value['$inQuery'],'className'))){

                    $query = $value['$inQuery'];
                    $value['$inQuery'] = $query->preparedQueryParameters();
                    $value['$inQuery']['className'] = $query->className();
                    $formatted[$key] = $value;

                }elseif(isset($value[Query::WHERE_OPT_IN]) || isset($value[Query::WHERE_OPT_NOT_IN])){

                    $inKey = isset($value[Query::WHERE_OPT_IN]) ? Query::WHERE_OPT_IN : Query::WHERE_OPT_NOT_IN;
                    $inValues = array();

                    foreach($value[$inKey] as $v){
                        $inValues[] = $this->formatValue($v);
                    }

                    $formatted[$key] = array($inKey=>$inValues);
                }else{
                    $formatted[$key] = $value;
                }

            }elseif(is_object($value)){
                // Duck type Sparse\Object, subclasses or anything with valid interface
                // exposing 'pointer'
                if(method_exists($value,'pointer')){
                    $value = $value->pointer();
                    $formatted[$key] = $value;
                }
            }else{
                $formatted[$key] = $value;
            }


        }



        return $formatted;
    }
}