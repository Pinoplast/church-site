<?php
defined('_JEXEC') or die;

class SermonspeakerController extends JControllerLegacy
{
	protected $default_view = 'main';

	public function display($cachable = false, $urlparams = false)
	{
		$view   = $this->input->get('view', 'main');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');
		$views  = array('sermon', 'serie', 'speaker');

		// Check for edit form.
		if (in_array($view, $views) && $layout == 'edit' && !$this->checkEditId('com_sermonspeaker.edit.' . $view, $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_sermonspeaker&view=main', false));

			return false;
		}

		$params = JComponentHelper::getParams('com_sermonspeaker');

		if ($params->get('css_icomoon') == '')
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SERMONSPEAKER_NOTSAVED'), 'warning');
		}

		if ($params->get('alt_player'))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_SERMONSPEAKER_PLAYER_DEPRECATED'), 'notice');
		}
		else
		{
			if (!JPluginHelper::isEnabled('sermonspeaker'))
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_SERMONSPEAKER_NO_PLAYER_ENABLED'), 'warning');
			}
		}

		return parent::display();
	}
	public function download()
	{
		require_once JPATH_COMPONENT . '/helpers/sermonspeaker.php'; 
		
		$this->input	= JFactory::getApplication()->input;
		$id		= $this->input->get('id', 0, 'int');
		if (!$id)
		{
			die("<html><body OnLoad=\"javascript: alert('I have no clue what you want to download...');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}
		
		$db = JFactory::getDBO();
		
		if ($this->input->get('type', 'audio', 'word') == 'video')
		{
			$query = "SELECT videofile FROM #__sermon_sermons WHERE id = ".$id;
		}
		else
		{
		  $query = "
		   SELECT  
			   sermon.id,
			   sermon.speaker_id, 
			   speaker.title as speaker_title,
			   sermon.audiofile, 
			   sermon.title as sermon_title,
			   sermon.sermon_date 
			FROM 
			   #__sermon_sermons as sermon,  
			   #__sermon_speakers as speaker

			WHERE (sermon.id = " . $id . ") AND (sermon.speaker_id = speaker.id)
		";
			//$query = "SELECT sermon.speaker_id, audiofile,title,sermon_date FROM #__sermon_sermons WHERE id = ".$id;
		}
		
		$db->setQuery($query);
		$result = $db->loadObject() or die ("<html><body OnLoad=\"javascript: alert('Encountered an error while accessing the database');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		$audiofile = rtrim($result->audiofile);
		
		if (parse_url($audiofile, PHP_URL_SCHEME))
		{ // redirect if link goes to an external source
			$audiofile = str_replace('http://player.vimeo.com/video/', 'http://vimeo.com/', $audiofile);
			$this->setRedirect($audiofile);
			return;
		}
		$audiofile = str_replace('\\', '/', $audiofile); // replace \ with /
		if (substr($audiofile, 0, 1) != '/')
		{ // add a leading slash to the sermonpath if not present.
			$audiofile = '/'.$audiofile;
		}
		// Loading Joomla Filefunctions
		jimport('joomla.filesystem.file');
		$file = JPATH_ROOT.$audiofile;
		
		
		$filename = substr($result->sermon_date, 0, 10) . " " . $result->speaker_title . " - " . $result->sermon_title . "." . JFile::getExt($file);
		
		$mime = SermonspeakerHelper::getMime(JFile::getExt($file));
		
		if(ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}
		
		if (JFile::exists($file))
		{
			// if present overriding the memory_limit for php so big files can be downloaded.
			if(ini_get('memory_limit'))
			{
				ini_set('memory_limit','-1'); 
			}
			
			header('Pragma: public');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Type: ' . $mime);
			//header('Content-Disposition: attachment; filename="'.JFile::getName($file).'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . @filesize($file));
			set_time_limit(0);
			$fSize = @filesize($file);
			
			// how many bytes per chunk
			$chunksize = 3 * (1024 * 1024); 
			if ($fSize > $chunksize)
			{
				$handle = fopen($file, 'rb');
				if (!$handle)
				{
					die("Can't open the file!");
				}
				$buffer = '';
				while (!feof($handle))
				{
					$buffer = fread($handle, $chunksize);
					echo $buffer;
					ob_flush();
					flush();
				}
				fclose($handle);
			}
			else
			{
				@readfile($file) OR die('Unable to read file!');
			}
			exit;
		}
		else
		{
			die("<html><body OnLoad=\"javascript: alert('File not found!');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}
	}
}