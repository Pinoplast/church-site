<?php
/**
 * @package     SermonSpeaker
 * @subpackage  Component.Site
 * @author      Thomas Hunziker <admin@sermonspeaker.net>
 * @copyright   © 2016 - Thomas Hunziker
 * @license     http://www.gnu.org/licenses/gpl.html
 **/

defined('_JEXEC') or die();

/**
 * SermonSpeaker Component Controller
 * @since  1.0
 */
class SermonspeakerController extends JControllerLegacy
{
	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $default_view = 'sermons';

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *                        Recognized key values include 'name', 'default_task', 'model_path', and
	 *                        'view_path' (this list is not meant to be comprehensive).
	 *
	 * @since   4.0
	 */
	public function __construct($config = array())
	{
		$this->input = JFactory::getApplication()->input;

		// Frontpage Editor sermons proxying:
		if ($this->input->get('view') === 'sermons' && $this->input->get('layout') === 'modal')
		{
			$config['base_path'] = JPATH_COMPONENT_ADMINISTRATOR;
		}

		parent::__construct($config);
	}

	/**
	 * View method.
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   array   $urlparams An array of safe url parameters and their variable types, for valid values see
	 *                             {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
	 *
	 * @since   4.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$cachable = JFactory::getUser()->get('id') ? false : true;
		$viewName = $this->input->get('view', $this->default_view);
		$id       = $this->input->getInt('id');
		$views    = array('frontendupload', 'serieform', 'speakerform');

		// Check for edit form.
		if (in_array($viewName, $views) && !$this->checkEditId('com_sermonspeaker.edit.' . $viewName, $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_sermonspeaker&view=main', false));

			return false;
		}

		/** @var $app JApplicationSite */
		$app    = JFactory::getApplication();
		$params = $app->getParams();

		if ($params->get('css_icomoon'))
		{
			JHtml::_('stylesheet', 'jui/icomoon.css', array('relative' => true));
		}

		// Make sure the format is raw for feed and sitemap view
		// Bug: Doesn't take into account additional filters (type, cat)
		if (($viewName == 'feed' || $viewName == 'sitemap') && $this->input->get('format') != 'raw')
		{
			// JFactory::$document = JDocument::getInstance('raw');
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . JUri::root() . 'index.php?option=com_sermonspeaker&view=' . $viewName . '&format=raw');

			return $this;
		}

		$safeurlparams = array(
			'id'               => 'INT',
			'catid'            => 'INT',
			'limit'            => 'INT',
			'limitstart'       => 'INT',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'lang'             => 'CMD',
			'year'             => 'INT',
			'month'            => 'INT',
			'filter-search'    => 'STRING',
			'return'           => 'BASE64',
			'book'             => 'INT',
			'Itemid'           => 'INT',
		);

		switch ($viewName)
		{
			case 'speaker':
				$viewLayout   = $this->input->get('layout', 'default');
				$view         = $this->getView($viewName, 'html', '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
				$series_model = $this->getModel('series');
				$view->setModel($series_model);
				$sermons_model = $this->getModel('sermons');
				$view->setModel($sermons_model);
				break;
			case 'serie':
				$viewLayout    = $this->input->get('layout', 'default');
				$view          = $this->getView($viewName, 'html', '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
				$sermons_model = $this->getModel('sermons');
				$view->setModel($sermons_model);
				break;
			case 'seriessermon':
				$viewLayout   = $this->input->get('layout', 'default');
				$view         = $this->getView($viewName, 'html', '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
				$series_model = $this->getModel('series');
				$view->setModel($series_model);
				$sermons_model = $this->getModel('sermons');
				$view->setModel($sermons_model);
				break;
		}

		return parent::display($cachable, $safeurlparams);
	}
	public function started() 
	{
		$this->input = JFactory::getApplication()->input;
		$id          = $this->input->get('id', 0, 'int');

	
		if (!$id)
		{
			die("<html><body onload=\"alert('I have no clue what you want to download...');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}
		
		 try
		{
		  	 
		  $hits = $this->getModel('sermon')->getItem($id)->hits;
		  $custom1 = $this->getModel('sermon')->getItem($id)->custom1;
		  
		   
		  $sermon = JTable::getInstance('Sermon', 'SermonspeakerTable');
		  $sermon->id = $id;
		  $sermon->custom1 = $custom1 + 1;
		  $sermon->store();
	 
		  echo new JResponseJson(array("hits"=>$hits, "custom1"=>$custom1) );
		}
		catch(Exception $e)
		{
		  echo new JResponseJson($e);
		}
		
		//$db = JFactory::getDBO();
		
	}
	
	public function complited() 
	{
		$this->input = JFactory::getApplication()->input;
		$id          = $this->input->get('id', 0, 'int');

	
		if (!$id)
		{
			die("<html><body onload=\"alert('I have no clue what you want to download...');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}
		
		 try
		{
		  	 
		  
		  $custom2 = $this->getModel('sermon')->getItem($id)->custom2;
		  
		   
		  $sermon = &JTable::getInstance('Sermon', 'SermonspeakerTable');
		  $sermon->id = $id;
		  $sermon->custom2 = $custom2 + 1;
		  $sermon->store();
	 
		  echo new JResponseJson(array("custom2"=>$custom2) );
		  
		}
		catch(Exception $e)
		{
		  echo new JResponseJson($e);
		}
		
		//$db = JFactory::getDBO();
		
	}
	public function download()
	{
		$this->input = JFactory::getApplication()->input;
		$id          = $this->input->get('id', 0, 'int');

		if (!$id)
		{
			die("<html><body onload=\"alert('I have no clue what you want to download...');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}

		$db = JFactory::getDBO();

		if ($this->input->get('type', 'audio', 'word') == 'video')
		{
			$query = "SELECT videofile FROM #__sermon_sermons WHERE id = " . $id;
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
			//$query = "SELECT audiofile FROM #__sermon_sermons WHERE id = " . $id;
		}

		$db->setQuery($query);
		$result = $db->loadObject() or die ("<html><body onload=\"alert('Encountered an error while accessing the database'); history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		$audiofile = rtrim($result->audiofile);

		// Redirect if link goes to an external source
		if (parse_url($audiofile, PHP_URL_SCHEME))
		{
			$audiofile = str_replace('http://player.vimeo.com/video/', 'http://vimeo.com/', $audiofile);
			$this->setRedirect($audiofile);

			return;
		}

		// Replace \ with /
		$audiofile = str_replace('\\', '/', $audiofile); // replace \ with /

		// Add a leading slash to the sermonpath if not present
		if (substr($audiofile, 0, 1) != '/')
		{
			$audiofile = '/' . $audiofile;
		}

		$file = JPATH_ROOT.$audiofile;
		$filename = substr($result->sermon_date, 0, 10) . " " . $result->speaker_title . " - " . $result->sermon_title . "." . JFile::getExt($file);
		$mime = SermonspeakerHelperSermonspeaker::getMime(JFile::getExt($file));
		// echo "<br />".$filename;
		// echo "<br />".$mime;
		// exit();

		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}

		if (JFile::exists($file))
		{
			// If present overriding the memory_limit for php so big files can be downloaded
			if (ini_get('memory_limit'))
			{
				ini_set('memory_limit', '-1');
			}

			header('Pragma: public');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', false);
			header('Content-Type: ' . $mime);
			//header('Content-Disposition: attachment; filename="' . JFile::getName($file) . '"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . @filesize($file));
			set_time_limit(0);
			$fSize = @filesize($file);

			// How many bytes per chunk
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
				// Update Statistic //onivan
				  $hits = $this->getModel('sermon')->getItem($id)->hits;
				  $sermon = &JTable::getInstance('Sermon', 'SermonspeakerTable');
				  $sermon->id = $id;
				  $sermon->hits = $hits + 1;
				  $sermon->store();
	
			}
			else
			{
				@readfile($file) or die('Unable to read file!');
				// Update Statistic //onivan
				  $hits = $this->getModel('sermon')->getItem($id)->hits;
				  $sermon = &JTable::getInstance('Sermon', 'SermonspeakerTable');
				  $sermon->id = $id;
				  $sermon->hits = $hits + 1;
				  $sermon->store();
			}

			exit;
		}
		else
		{
			die("<html><body OnLoad=\"alert('File not found!');history.back();\" bgcolor=\"#F0F0F0\"></body></html>");
		}
	}
}
