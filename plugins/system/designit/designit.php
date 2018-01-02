<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.sef
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! SEF Plugin.
 *
 * @since  1.5
 */
class PlgSystemDesignit extends JPlugin
{

	/**
	 * Convert the site URL to fit to the HTTP request.
	 *
	 * @return  void
	 */
	public function onAfterRender()
	{
		$app = JFactory::getApplication();

		if (!$app->isSite())
		{
			return;
		}

		// check access level
		$groups = JFactory::getUser()->getAuthorisedViewLevels();
		$accessLevel = $this->params->get('access', 3);	
		if (!in_array($accessLevel, $groups)) return;

		// append script to before close body
		$buffer = $app->getBody();
		$appID = $this->params->get('app_id');
		$height = $this->params->get('height', 100);
		$width = $this->params->get('width', 100);
		$upload = $this->params->get('upload', false) ? 'true' : 'false';

		if (!$appID) return;

		$script = <<<EOT
<script type="text/javascript">
	(function(){
	    function addEvent(element, eventName, fn) {
	        if (element.addEventListener)
	            element.addEventListener(eventName, fn, false);
	        else if (element.attachEvent)
	            element.attachEvent('on' + eventName, fn);
	    }

	    addEvent(window, 'load', function(){ 
	        // find all images which have good size for insert designit button
	        var imgs = document.getElementsByTagName('img');
	        for (var i=0; i<imgs.length; i++) {
	            if (imgs[i].naturalWidth > $width && imgs[i].naturalHeight > $height) {
	                if (!imgs[i].className.match(/(^|\s)db-img-design-me(\s|$)/)) imgs[i].className += ' db-img-design-me';
	            }
	        }
			window.DBSDK_Cfg = {
			   image_edit : $upload
			};
	        // init designit script
	        (function(d, s, id) {
	            var js, fjs = d.getElementsByTagName(s)[0];
	            if (d.getElementById(id)) return;
	            js = d.createElement(s); js.id = id;
	            js.src = "https://sdk.designbold.com/button/v1.0/js/sdk.js#app_id=$appID";
	            fjs.parentNode.insertBefore(js, fjs);
	        }(document, 'script', 'db-js-sdk'));            
	    });
	})();
</script>

EOT;
		$bdcPos = strripos ($buffer, '</body>');
		if ($bdcPos) {
			$buffer = substr_replace ($buffer, $script, $bdcPos, 0);
		}

		// Use the replaced HTML body.
		$app->setBody($buffer);
	}

}
