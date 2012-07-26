<?php

// Make sure our response object is available
require_once( 'ApiResponse.class.php' );
require_once( 'ApiExceptions.class.php' );

/*
 * This line is more for testing/use outside of WordPress. It can be safely 
 * removed if you do not need it
 */
//require_once(ABSPATH.'wp-load.php');


class ApiRequest {
    
    private $mConfig = array();

    /**
     * Default constructor
     * 
     * @throws InvalidArgumentException
     * @throws InvalidParametersException
     */
    public function __construct( $Config ) {
        
        // Ensure the passed in config parameter is an array
        if( !is_array( $Config ) )
            throw new InvalidArgumentException( 'ApiRequest object requires an array of configuration data as a parameter' );
        
        
        $required_config = array( 'api_entry' );
        $default_config = array(
            'response_type' => 'json',
            'cache_enabled' => false,
            'cache_lifetime' => 60, // In seconds. Default is 1 minute
            'cache_prefix' => 'api_request_',
            'check_for_update' => true, // Checks HTTP HEAD to see if there are updates since last cache
            'force_caching' => false // If true the cache parameters in HEAD will be overridden by these config settings
        );
        $Config = wp_parse_args( $Config, $default_config );
        
        if( !$this->verifyParameters( $required_config, $Config ) ) 
                throw new InvalidParametersException( $required_config );
        
        $this->setConfig( $Config );
    }

    /**
     * Performs the GET query against the specified endpoint
     *
     * @todo Add conditional code to fetch header data first
     * @throws UnkownErrorException
     * @throws BadRequestException
     * @throws UnauthorizedRequestException
     * @throws InternalServerErrorException
     * @param String $Url - Endpoint with URL paramters (for now)
     * @return ApiResponse
     */
    public function get( $Url ) { 
        /*
         * Several things to check before we go fetch the data
         * 
         * - Is caching enabled?
         * --- Yes? Check to see if the transient is still valid
         * ------ Yes? Set it as the response
         * --------- Is check_for_update? (HTTP HEAD)
         * ------------ Boolean: fetch_url = false
         * ------------ Yes? Check HTTP HEAD
         * --------------- If timestamp newer than transient
         * ------------------ fetch_url = true
         * --------- If fetch_url
         * ------------ Go fetch!, Set transient
         * ------ No? Go fetch!, Set transient
         * --- No? Go fetch!
         * - Return response
         *
         * To make this easier to read some of the conditionals may be broken 
         * apart in a not as efficient manner as possible
         */
        
        if( $this->getConfig( 'cache_enabled' ) ) { 
            $transient_name = $this->transientName( $Url );
            
            // Grab the cached data
            $transient = get_transient( $transient_name );
            
            if( !$transient ) {
                // Cached data is no longer valid refresh it
                $response = $this->performGet( $Url );
                
                if( $response->isCachable() || $this->getConfig( 'force_caching' ))
                    set_transient( $transient_name, $response, $this->getConfig( 'cache_lifetime' ));
            } else {
                // Cached data is valid do we need to check the HTTP HEAD data?
                if( $this->getConfig( 'check_for_update' )) {
                    // Need to check HTTP HEAD to see if there are any updates
                    // or if the expires heading has been exceeded
                    $head = $this->performHead( $Url );
                    
                    if( $head->isExpired() ) {
                        // Cache is expired. Grab the data again.
                        $response = $this->performGet( $Url );
                        
                        if( $response->isCachable() || $this->getConfig( 'force_caching' )) {
                            // Response is cachable (HTTP HEAD) or force_caching option is true
                            set_transient( $transient_name, $response, $this->getConfig( 'cache_lifetime' ));
                        }
                    }
                } else {
                    $response = $transient;
                }
            }
        } else {
            // Caching is not enabled. Always perform the GET
            $response = $this->performGet( $Url );
        }
        
        return $response;
    }
    
    private function transientName( $Name ) {
        return $this->getConfig( 'cache_prefix' ) . md5($Name);
    }
    
    // Helper function
    private function performGet( $Url ) {
        $res = wp_remote_get( $Url );
        //echo '<pre>';var_dump( wp_remote_retrieve_headers($res));
        if( is_wp_error( $res ) ) {
          //  ( var_dump( ($res->errors) ));
            
            if( count( $res->errors ) == 1 ) {
                // If there was only 1 error let's see if I can deal with it
                $error = array_keys( $res->errors );
                $error = $error[0];
                
                switch( $error ) {
                    case 'http_request_failed':
                        throw new HttpRequestFailedException( $Url );
                    default:
                        // WordPress had some error that I do not know about
                        throw new UnknownErrorException( $res );
                }
                echo '1 error';
            } else {
                // WordPress had some error that I do not know about
                throw new UnknownErrorException( $res );
            }
            die();
        } else {            
            $response = new ApiResponse();
            $response->setHttpCode( wp_remote_retrieve_response_code( $res ) );
            $response->setHeaders( wp_remote_retrieve_headers($res) );
            $response->setResponse( wp_remote_retrieve_body( $res ) );
            if( $response->getHttpCode() == '400' ) {
                // 400 Bad request when there was a problem with the request
                throw new BadRequestException($Url, $response);
            } else if ( $response->getHttpCode() == '401' ) {
                // 401 Unauthorized when you don't provide a valid key
                throw new UnauthorizedRequestException();
            } else if ( $response->getHttpCode() == '500' ) {
                // 500 Internal Server Error
                throw new InternalServerErrorException();
            }
        
            return $response;
        }
    }
    
    private function performHead( $Url ) {
        $res = wp_remote_head( $Url );
        //echo '<pre>';var_dump( wp_remote_retrieve_headers($res));
        if( is_wp_error( $res ) ) {
            // WordPress had some error that I do not know about
            throw new UnknownErrorException( $res );
        } else {            
            $response = new ApiResponse();
            $response->setHttpCode( wp_remote_retrieve_response_code( $res ) );
            $response->setHeaders( wp_remote_retrieve_headers($res) );
           // $response->setResponse( wp_remote_retrieve_body( $res ) );
            if( $response->getHttpCode() == '400' ) {
                // 400 Bad request when there was a problem with the request
                throw new BadRequestException($Url, $response);
            } else if ( $response->getHttpCode() == '401' ) {
                // 401 Unauthorized when you don't provide a valid key
                throw new UnauthorizedRequestException();
            } else if ( $response->getHttpCode() == '500' ) {
                // 500 Internal Server Error
                throw new InternalServerErrorException();
            }
        
            return $response;
        }
    }

    /**
     *
     * @throws InvalidParametersException
     * @param <type> $Endpoint
     * @param <type> $Parameters
     * @param <type> $RequiredParameters - Optional - Some endpoints do not have parameters
     * @return <type>
     */
    public function buildUrl( $Endpoint, $Parameters = null, $RequiredParameters = null ) {
        if(is_array($RequiredParameters) && !$this->verifyParameters( $RequiredParameters, $Parameters )) {
            throw new InvalidParametersException( $RequiredParameters );
        }
        
        $url = trailingslashit( $this->getConfig( 'api_entry' ) ) . $Endpoint;
        
        if( !is_null( $Parameters ) )
            $url = add_query_arg( $Parameters, $url );
        
        return $url;
    }

    /**
     * Checks the input parameters against a list of required parameters to
     * ensure at least one of the required parameters exists.
     *
     * NOTE: The Meetup API contains a list of parameters that are required for
     * each endpoint with a default condition of "any of"
     * 
     * @param Array $RequiredList - Names of required parameters
     * @param Array $Parameters - List of provided paramters
     * @return Boolean
     */
    public function verifyParameters($RequiredList, $Parameters) {
        if( !is_array( $Parameters ) ) return false;
        $Parameters = array_keys($Parameters);

        /*
         * Check to see if any of the required list is in the parameters array
         * Since the Meetup API requires "any of" if a required key is found in
         * parameters the verification will pass
         */
        foreach($RequiredList AS $r) {
            if(in_array($r, $Parameters)) {
                return true;
            }
        }
        return false;
    }
    
    public function getConfig( $Var ) {
        return ( isset( $this->mConfig[$Var] ) )? $this->mConfig[$Var] : null;
    }
    
    /**
     *Recursive function
     * @param type $Var
     * @param type $Value 
     */
    public function setConfig( $Var, $Value = null ) {
        if(is_array( $Var ) ) {
            foreach( $Var AS $k => $v ) {
                $this->setConfig( $k, $v );
            }
        } else {
            $this->mConfig[$Var] = $Value;
        }
    }

} // end class
