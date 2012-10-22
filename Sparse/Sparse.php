<?php
namespace Sparse;

class Sparse {

    /**
     * @var Rest
     */
    protected static $_restClient;
}

include("Cloud.php");
include("Rest.php");
include("Object.php");
include("User.php");
include("Query.php");
include("Push.php");

// Your credentials:
//Rest::$applicationId = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
//Rest::$restAPIKey = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";