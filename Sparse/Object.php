<?php
/**
 * PHP Parse Object Implementation
 * @version 0.1
 */
namespace Sparse;

class Object {

    // Class ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    const USER_OBJECT_CLASS = 'User';

    /**
     * @var Rest
     */
    protected static $_restClient;

    /**
     * Saves the given list of Sparse\Objects
     *
     * @param $list
     */
    public static function saveAll($list){
        foreach($list as $obj){
            $obj->save();
        }
    }

    // Instance ////////////////////////////////////////////////////////////////////////////////////////////////////////

    public $objectClass;

    protected $parseSuppliedAttributes = array('objectId','createdAt','updatedAt');

    protected $_attributes = array();
    protected $_dirtyKeys = array();
    protected $_rest;

    /**
     * Constructor
     * Creates a new model with defined attributes.
     *
     * @param $objectClass
     * @param array $attributes
     */
    public function __construct($objectClass,$attributes=array()){

        $this->objectClass = $objectClass;

        $this->attributes($attributes);

        // TODO: Fix the use of many rest clients
        $this->_rest = new \Sparse\Rest();

        if(!Object::$_restClient){
            Object::$_restClient = new \Sparse\Rest();
        }

        $this->initialize();
    }

    // Overloads ///////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __get($key){
        return $this->get($key);
    }

    public function __set($key,$value){
        $this->set($key,$value);
    }

    public function __isset($name){
        return ($this->get($name) != null);
    }

    // API /////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Atomically add an object to the end of the array associated with a given key.
     *
     * @param $attr
     * @param $item
     */
    public function add($attr,$item){
        $value = $this->get($attr);
        if(!$value){
            $value = array();
        }
        if(is_array($value)){
            $value[] = $item;
            $this->set($attr,$value);
        }
    }

    /**
     * Atomically add an object to the end of the array associated with a given key,
     * only if it is not already present in the array.
     *
     * @param $attr
     * @param $item
     */
    public function addUnique($attr,$item){
        $value = $this->get($attr);
        if(!$value){
            $value = array();
        }
        if(is_array($value) && !in_array($item,$value)){
            $value[] = $item;
            $this->set($attr,$value);
        }
    }

    /**
     * Attributes getter/setter
     * Note: Addition from SDK
     * @param array $attributes
     * @return array
     */
    public function attributes($attributes=array()){
        $attributes = (array)$attributes;
        if(!empty($attributes)){
            // IMPORTANT: setting all the attributes directly does not set dirty
            $this->clearDirtyKeys();
            $processed = array();
            foreach($attributes as $key=>$value){
                $processed[$key] = $this->processIncomingAttribute($value);
            }
            $this->_attributes = $processed;
        }
        return $this->_attributes;
    }

    /**
     * @param $diff
     */
    public function changedAttributes($diff){
        // Not yet implemented
    }

    /**
     * @return string
     */
    public function className(){
        if($this->objectClass == Object::USER_OBJECT_CLASS){
            return '_'.$this->objectClass;
        }
        return $this->objectClass;
    }

    /**
     * Clear all attributes on the model
     */
    public function clear(){
        $this->clearDirtyKeys();
        $this->_attributes = array();
    }

    /**
     * Creates a new model with identical attributes to this one.
     * Note: Changed from clone()
     * @return Object
     */
    public function cloneObject(){
        return new Object($this->objectClass,$this->resetAttributes($this->attributes()));
    }

    /**
     * Destroy this model on the server if it was already persisted.
     */
    public function destroy(){
        $id = $this->id();
        if($id){
            $this->_rest->deleteObject($this->objectClass,$id);
            $this->clearDirtyKeys();
            $this->attributes($this->resetAttributes($this->attributes()));
        }
    }

    /**
     * Returns true if this object has been modified since its last save/refresh.
     * @return bool
     */
    public function dirty(){
        return ($this->_dirtyKeys && count($this->_dirtyKeys) > 0);
    }

    /**
     * @param $attr
     */
    public function escape($attr){
        // Not yet implemented
    }

    /**
     * TODO: This will be probably be changed or removed
     * @return string
     */
    public function error(){
        return $this->_rest->error();
    }

    public function existed(){
        // Not yet implemented
    }

    /**
     * Fetch the model from the server.
     * If the server's representation of the model differs from its current attributes, they will be overriden.
     */
    public function fetch(){
        $id = $this->id();
        if($id){
            if($this->objectClass == Object::USER_OBJECT_CLASS){
                $fetched = $this->_rest->getuser($id);
            }else{
                $fetched = $this->_rest->getObject($this->objectClass,$id);
            }
            if($this->_rest->statusCode() == 200){
                $this->clearDirtyKeys();
                $this->attributes($this->mergeAttributes($fetched));
            }
        }
    }

    /**
     * Gets the value of an attribute.
     * @param $key
     * @return mixed
     */
    public function get($key){
        if($key=='id'){
            $key = 'objectId';
        }
        return $this->has($key) ? $this->_attributes[$key] : null;
    }

    public function getACL(){
        // Not yet implemented
    }

    /**
     * Returns true if the attribute contains a value that is not null or undefined.
     * @param $key
     * @return bool
     */
    public function has($key){
        return !empty($this->_attributes[$key]);
    }

    /**
     * Determine if that attribute has changed.
     * @param $key
     * @return bool
     */
    public function hasChanged($key){
        return in_array($key,$this->_dirtyKeys);
    }

    /**
     * Increments the value of the given attribute
     * If no amount is specified, 1 is used by default.
     * @param $attr
     * @param int $amount
     */
    public function increment($attr, $amount=1){
        $value = $this->get($attr);
        if(!$value){
            $value = 0;
        }
        $this->set($attr,$value+$amount);
    }

    /**
     * Initialize is an empty function by default. Override it with your own initialization logic.
     */
    public function initialize(){
        // empty
    }

    /**
     * Returns true if this object has never been saved to Parse.
     * @return bool
     */
    public function isNew(){
        return ($this->id() == null);
    }

    /**
     * @return bool
     */
    public function isValid(){
        // Not yet implemented
        return true;
    }

    /**
     * Convenience method to get/set Parse objectId
     * Note: Addition from SDK
     * @param string $id
     * @return string
     */
    public function id($id=null){
        if($id){
            $this->set('objectId',$id);
        }
        return $this->get('objectId');
    }

    public function op(){
        // Not yet implemented
    }

    /**
     * Convenience method to get a pointer to this object
     * Note: Addition from SDK
     * @return array
     */
    public function pointer(){
        $pointer = array();
        // objects should be saved before used as a pointer
        if(!$this->isNew()){
            $pointer = array(
                '__type'=>'Pointer',
                'className'=>$this->className(),
                'objectId'=>$this->id(),
            );
        }
        return $pointer;
    }

    /**
     * @param $attr
     */
    public function previous($attr){
        // Not yet implemented
    }

    public function previousAttributes(){
        // Not yet implemented
    }

    public function relation(){
        // Not yet implemented
    }
    /**
     * Atomically remove all instances of an object from the array associated with a given key.
     *
     * @param $attr
     * @param $item
     */
    public function remove($attr,$item){
        $value = $this->get($attr);
        if(is_array($value)){
            $new = array();
            foreach($value as $v){
                if($item != $v){
                    $new[] = $v;
                }
            }
            $this->set($attr,$new);
        }
    }

    /**
     * Set a hash of model attributes, and save the model to the server.
     * updatedAt will be updated when the request returns.
     * @param null $keyOrAttributes
     * @param mixed|null $valueForKey
     * @return bool
     */
    public function save($keyOrAttributes=null, $valueForKey=null){

        $this->set($keyOrAttributes,$valueForKey);

        if($this->isNew()){

            $attributes = $this->formatAttributesForTransfer($this->attributes());
            $created = $this->_rest->createObject($this->objectClass,$attributes);
            if($this->_rest->statusCode() == 201){
                $this->clearDirtyKeys();
                $this->updateAttributes((array)$created);
                return true;
            }

        }else{

            $id = $this->id();
            $attributes = $this->formatAttributesForTransfer($this->dirtyAttributes());
            if(!empty($attributes)){
                $updated = $this->_rest->updateObject($this->objectClass,$id,$attributes);
                if($this->_rest->statusCode() == 200){
                    $this->clearDirtyKeys();
                    $this->updateAttributes((array)$updated);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets a hash of model attributes on the object
     * You can call it with an array containing keys and values, or with one key and value.
     * @param string|array $keyOrAttributes
     * @param mixed|null $valueForKey
     */
    public function set($keyOrAttributes,$valueForKey=null){
        // no-op with no key
        if(!$keyOrAttributes){
            return;
        }
        // allow associative arrays to be passed in
        if(is_array($keyOrAttributes)){
            foreach($keyOrAttributes as $k=>$v){
                $this->set($k,$v);
            }
            return;
        }
        if($keyOrAttributes=='id'){
            $keyOrAttributes = 'objectId';
        }
        // set attribute
        $this->addDirtyKey($keyOrAttributes);
        $this->_attributes[$keyOrAttributes] = $this->processIncomingAttribute($valueForKey);
    }

    /**
     * @param $acl
     */
    public function setACL($acl){
        // Not yet implemented
    }

    /**
     * Returns a JSON version of the object suitable for saving to Parse.
     * @return string
     */
    public function toJSON(){
        return json_encode($this->_attributes);
    }

    /**
     * Remove an attribute from the model
     * Note: Changed from unset()
     * @param $key
     */
    public function unsetAttr($key){
        if($this->has($key)){
            unset($this->_attributes[$key]);
        }
    }

    /**
     * @param $attrs
     */
    public function validate($attrs){
        // Not yet implemented
    }

    // Internal Methods ////////////////////////////////////////////////////////////////////////////////////////////////

    protected function addDirtyKey($key){
        if(!in_array($key,$this->_dirtyKeys)){
            $this->_dirtyKeys[] = $key;
        }
    }

    protected function clearDirtyKeys(){
        $this->_dirtyKeys = array();
    }

    protected function dirtyAttributes(){
        $attributes = array();
        foreach($this->_attributes as $k=>$v){
            if(in_array($k,$this->_dirtyKeys)){
                $attributes[$k] = $v;
            }
        }
        return $attributes;
    }

    /**
     * "Resets" attributes by removing parse specific keys
     * Used when destroying or cloning (non-mutating)
     * @param $attributes
     * @return array
     */
    protected function resetAttributes($attributes){
        foreach($this->parseSuppliedAttributes as $k){
            if(isset($attributes[$k])){
                unset($attributes[$k]);
            }
        }
        return $attributes;
    }

    /**
     * @param array $attributes
     */
    protected function updateAttributes($attributes=array()){
        $attributes = (array)$attributes;
        $this->attributes($this->mergeAttributes($attributes));
    }

    /**
     * Merges new attributes to the existing and returns the result (non-mutating)
     * @param array $attributes
     * @return array
     */
    protected function mergeAttributes($attributes=array()){
        $existing = $this->_attributes;
        $attributes = (array)$attributes;
        foreach($attributes as $k=>$v){
            $existing[$k] = $v;
        }
        return $existing;
    }

    protected function processIncomingAttribute($value){
        if(is_object($value)){
            if(get_class($value) != 'Sparse\Object'){

                if(isset($value->className) && isset($value->__type) && ($value->__type == 'Object' || $value->__type == 'Pointer')){
                    $objectClass = $value->className;
                    unset($value->__type);
                    unset($value->className);
                    if($objectClass == '_'.Object::USER_OBJECT_CLASS){
                        $value = new User((array)$value);
                    }else{
                        $value = new Object($objectClass,(array)$value);
                    }
                }
            }
        }
        return $value;
    }

    protected function formatAttributesForTransfer($attributes){
        $formatted = array();
        foreach($attributes as $key=>$value){
            if(!in_array($key,$this->parseSuppliedAttributes)){
                if(is_object($value)){
                    if(get_class($value) == 'Sparse\Object'){
                        $value = $value->pointer();
                        $formatted[$key] = $value;
                    }elseif(get_class($value) == 'Sparse\User'){
                        $value = $value->pointer();
                        $formatted[$key] = $value;
                    }
                }else{
                    $formatted[$key] = $value;
                }
            }
        }
        return $formatted;
    }
}