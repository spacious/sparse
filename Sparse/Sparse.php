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
Rest::$applicationId = "odzci8lJYxcEDHhAMF8GmZF28P6kK6x9mzfVqQvZ";
Rest::$restAPIKey = "xLL2UkGaHKkAEC0zYWE3B8J1Vf6FtuhBGkc2zTAN";