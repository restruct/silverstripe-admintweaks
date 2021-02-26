<?php

class BetterNavDynCacheExtension extends DynamicCacheExtension {

	public function updateEnabled(&$enabled) {
		
		// disable caching if user is logged in (so we don't cache betternavigator)
        // if the user still has an alc_enc cookie set, Member::currentUserID() may call Member::autoLogin(), 
        // which may require a (yet unset) database connection by calling DB::get_conn()->addslashes() 
        // via Convert::raw2sql(). DB will be unset because we're using the DynamicCache controller.
        
        // Prevent calling addslashes() on a non-object by checking for loggedInAs in the session first
		if( Session::get("loggedInAs") && Member::currentUserID() ) {
			$enabled = false;
		}
		
	}

}