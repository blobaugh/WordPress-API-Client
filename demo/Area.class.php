<?php


/**
 * Connector for the /area endpoint 
 */
class Area extends ApiRequest {
    
    
    public function __construct($Config) {
        parent::__construct($Config);
    }
    
    /**
     * 
     * @param String $Region
     * @param Array $AdditionalParams - Pass in additional optional query parameters
     * @return String (Array|null) 
     */
    public function getByRegion( $Region, $AdditionalParams = null) {
        $params = array( 'region' => $Region );
        if(is_array( $AdditionalParams ) ) $params = array_merge ( $params, $AdditionalParams );
        
        //die( var_dump($params ));
        $url = $this->buildUrl( AGENT_API_AREA, $params );

        $response = $this->get( $url )->getResponse();
        
        // Make sure there are some results to display
        if( count( $response['results'] ) == 0 ) $response = null;
        
        return $response;
    }
    
    
    /**
     * Returns a list of all available regions
     * @return Mixed - Array or NULL 
     */
    public function getRegions() {
        $url = $this->buildUrl( AGENT_API_AREA );

        $response = $this->get( $url )->getResponse();
        
        // Make sure there are some results to display
        if( !isset( $response['meta'] )  ) return null;
        
        $response = $response['meta']['areas'];
        
        return $response;
    }
} // end class