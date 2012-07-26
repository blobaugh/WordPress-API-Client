<?php
/**
 * Contains all of the exceptions for the API Client
 */



/**
 * Used when invalid parameters are passed to the API 
 */
class InvalidParametersException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct( $RequiredParameters ) {
        // some code
        $message = "A required parameter was not found. Please review the list of required parameters: " . implode(", ", $RequiredParameters);
        // make sure everything is assigned properly
        parent::__construct( $message, E_USER_ERROR, null );
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}

// 400 Bad request when there was a problem with the request
class BadRequestException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($Url, $Response) {
        // some code
        $message = "<p><b>400 HTTP Error:</b> Error bad request to $Url<br/>Details: {$Response['details']}<br/>Problem: {$Response['problem']}</p>";

        // make sure everything is assigned properly
        parent::__construct($message, E_USER_ERROR, null);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}


class UnauthorizedRequestException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct() {
        // some code
        $message = "<p><b>401 HTTP Error:</b> Error not authorized. Please check your API credentials</p>";

        // make sure everything is assigned properly
        parent::__construct($message, E_USER_ERROR, null);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}

class InternalServerErrorException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct() {
        // some code
        $message = "<p><b>500 HTTP Error:</b> Internal server error. The servers are currently experiencing difficulty</p>";

        // make sure everything is assigned properly
        parent::__construct($message, E_USER_ERROR, null);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}

class UnknownErrorException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct( $Error = '') {
        
        if(is_wp_error( $Error ) ) {
            // Get all the errors WordPress returned
            $errors = $Error->errors;
            $Error = ''; // Reset the error message
            foreach( $errors AS $k => $v ) {
                $Error .= "Error $k: (" . implode(',', $v) . ")";
            }
        }
        // some code
        $message = "An unknown error has occured while talking to the API: $Error";

        // make sure everything is assigned properly
        parent::__construct($message, E_USER_ERROR, null);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}

class HttpRequestFailedException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct( $Url ) {
        // some code
        $message = "The provided URL failed to respond: $Url";

        if( '' == $Url ) $message = "No URL provided!";
        
        // make sure everything is assigned properly
        parent::__construct($message, E_USER_ERROR, null);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}