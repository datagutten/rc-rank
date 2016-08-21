<?php
session_start();
class rc_rank
{
	public $db=false;
	public $federation;
	private $config=false; //The config contains passwords which should only be available inside the class
	public $debug=false;
	public $error;
	public $EFRA_GP2=array(0,75,71,67,63,61,59,57,55,53,51,49,48,47,46,45,44,43,42,41,40,39,38,37,36,35,34,33,32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1);
	public $championship_names=false;
	public $counted_championships=false;
	public $words_not_counting=false;
	public $count_all_rounds=false; //Championships and classes where all rounds should be counted
	public $outdoor_season; //Outdoor season start and end week
	public $federations=false;
	public $lang='en';

	function init($federation=false)
	{
		if(empty($federation))
		{
			if(!empty($_GET['federation']))
				$federation=$_GET['federation'];
			elseif(empty($_SESSION['federation']))
			{
				header('Location: select_federation.php?uri='.urlencode($_SERVER['REQUEST_URI']));
				die();
			}
			else
				$federation=$_SESSION['federation'];
		}
		if($this->debug)
			ini_set('display_errors',true);
		if($this->federations===false)
			$this->federations=$this->get_federations();
		if(array_search($federation,$this->federations)===false)
		{
			$this->error='Invalid federation: '.$federation;
			return false;
		}
		elseif(file_exists($configfile='config_'.$federation.'.php'))
			require $configfile;
		else
		{
			$this->error=sprintf(_('%s is not a valid federation'),$federation);
			return false;
		}
		$this->config=$config;
		$this->federation=$federation;
		$this->championship_names=$config['championship_names'];
		$this->counted_championships=$config['counted_championships'];
		$this->words_not_counting=$config['words_not_counting'];
		if(isset($config['count_all_rounds']))
			$this->count_all_rounds=$config['count_all_rounds'];
		if(isset($config['outdoor_start_week']) && isset($config['outdoor_end_week']))
			$this->outdoor_season=array('start'=>$config['outdoor_start_week'],'end'=>$config['outdoor_end_week']);
		
		$this->db=new PDO("mysql:host={$this->config['db_host']};dbname={$this->config['db_name']};charset=utf8",$this->config['db_user'],$this->config['db_password'],array(PDO::ATTR_PERSISTENT => true));
	}
	function get_federations() //Get valid federations
	{
		$confs=glob('config_*.php');
		foreach($confs as $config)
		{
			if($config!='config_sample.php' && preg_match($config_pattern='/config_([A-Za-z0-9_\-]+).php/',$config) )
			{
				$federation=preg_replace($config_pattern,'$1',$config);
				$federations[]=$federation;
			}
		}
		return $federations;
	}
	function query($q,$fetch='all')
	{
		if($this->db==false)
			throw new Exception('Not initialized');
		$st=$this->db->query($q);

		if($st===false)
		{
			$errorinfo=$this->db->errorInfo();
			//trigger_error("SQL error: {$errorinfo[2]}",E_USER_WARNING);
			throw new Exception("SQL error: {$errorinfo[2]}");
			//return false;
		}
		elseif($fetch===false)
			return $st;
		elseif($fetch=='column')
			return $st->fetch(PDO::FETCH_COLUMN);
		elseif($fetch=='all')
			return $st->fetchAll(PDO::FETCH_ASSOC);
		elseif($fetch=='all_column')
			return $st->fetchAll(PDO::FETCH_COLUMN);
		elseif($fetch=='key_pair')
			return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	function execute($st,$parameters,$fetch=false)
	{
		if($st->execute($parameters)===false)
		{
			$errorinfo=$st->errorInfo();
			throw new Exception("SQL error: {$errorinfo[2]}");
			return false;
		}
		elseif($fetch=='single')
			return $st->fetch(PDO::FETCH_COLUMN);
		elseif($fetch=='all')
			return $st->fetchAll(PDO::FETCH_ASSOC);
		elseif($fetch=='key_pair')
			return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	function getEventList($eventType=false,$year=false)
	{
		if($eventType===false)
			return $this->query(sprintf('SELECT * FROM events_%s',$this->federation,$eventKey));
		if($year===false)
			$year=date('Y');
		elseif(!is_numeric($year))
		{
			$this->error=sprintf(_('Invalid year: %s'),$year);
			return false;
		}
		if(!preg_match('/[A-Za-z0-9_\-]+/',$eventType))
		{
			$this->error=_('Invalid eventType');
			return false;
		}
		else
			return $this->query(sprintf('SELECT * FROM events_%s WHERE eventType=%s AND startDate LIKE %s',$this->federation,$this->db->quote($eventType),$this->db->quote($year.'%')));
	}
	function getSectionList($eventKey)
	{
		if(!is_numeric($eventKey))
		{
			$this->error='Invalid eventKey';
			return false;
		}
		return $this->query(sprintf('SELECT * FROM sections_%s WHERE eventKey=%d',$this->federation,$eventKey));
	}
	function championships($year=false)
	{
		if($year===false)
			$year=date('Y');
		elseif(!is_numeric($year))
		{
			$this->error=sprintf(_('Invalid year: %s'),$year);
			return false;
		}
		return $this->query(sprintf('SELECT distinct championship FROM championships_%s WHERE year=%s',$this->federation,$this->db->quote($year)),'all_column');
	}

	function championship_events($championship,$year,$class) //Get events in a championship
	{
		if($class==='all')
		{
			$st_championship_events=$this->db->prepare(sprintf('SELECT * FROM championships_%s WHERE championship=? AND year=? AND class!=\'nocount\' ORDER BY round',$this->federation));
			return $this->execute($st_championship_events,array($championship,$year),'all');
		}
		else
		{
			$st_championship_events=$this->db->prepare(sprintf('SELECT * FROM championships_%s WHERE championship=? AND year=? AND class=? ORDER BY round',$this->federation));
			return $this->execute($st_championship_events,array($championship,$year,$class),'all');
		}
	}

	function championship_classes($championship,$year=false) //Get classes run in a championship a given year
	{
		if(!preg_match('/[A-Za-z0-9_\-]+/',$championship))
		{
			$this->error=_('Invalid championship');
			return false;
		}
		if($year===false)
		{
			$st=$this->db->prepare($q=sprintf('SELECT classes_%1$s.id,classes_%1$s.name FROM championships_%1$s,classes_%1$s
		WHERE championships_%1$s.class=classes_%1$s.id 
		AND championships_%1$s.championship=? 
		GROUP BY championships_%1$s.class 
		ORDER BY classes_%1$s.name',$this->federation));
			return $this->execute($st,array($championship),'key_pair');
		}
		if(!is_numeric($year))
		{
			$this->error=sprintf(_('Invalid year: %s'),$year);
			return false;
		}
		$q=sprintf('SELECT classes_%1$s.id,classes_%1$s.name FROM championships_%1$s,classes_%1$s
		WHERE championships_%1$s.class=classes_%1$s.id 
		AND championships_%1$s.championship=%2$s 
		AND year=%3$d 
		GROUP BY championships_%1$s.class 
		ORDER BY classes_%1$s.name',$this->federation,$this->db->quote($championship),$year);
		return $this->query($q,'key_pair');
	}
	//Number of rounds in a championship
	function number_of_rounds($championship,$year,$class)
	{
		$st=$this->db->prepare($q=sprintf('SELECT max(round) FROM championships_%s WHERE class=? AND championship=? AND year=?',$this->federation));
		return $this->execute($st,array($class,$championship,$year),'single');
	}
}