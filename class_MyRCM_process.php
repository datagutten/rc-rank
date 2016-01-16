<?Php
//Process data from MyRCM
class MyRCM_process
{
	public $error;
	function sort_events($events,$cups=false,$year=false)
	{
		if(!is_numeric($year))
			trigger_error("Invalid year",E_USER_ERROR);
		if(!is_array($cups))
			trigger_error("Invalid value for cups",E_USER_ERROR);
		if(!is_array($events))
		{
			$this->error='Events must be array';
			return false;
		}
		foreach($events as $event)
		{
			$event=(array)$event;
			if(substr($event['startDate'],0,4)!=$year)
				continue;
			//echo $event['eventName']."\n";
			foreach($cups as $key=>$names)
			{
				if(is_numeric($key))
				{
					$key=$names;
					$names=array($names);
				}
				foreach($names as $name)
				{
					if(strlen($name)<=2)
						$check=substr($event['eventName'],0,strlen($name))==$name;
					else
						$check=stripos($event['eventName'],$name);
					if($check!==false)
					{
						$events_sorted[$key][$event['eventType']][$event['primaryKey']]=$event;
						continue 3;
					}
				}
				//echo sprintf('%s==%s',substr($event['eventName'],0,strlen($name)),$name)."\n";
			}
			
			$events_sorted['unknown'][$event['eventType']][$event['primaryKey']]=$event;
			//echo $event['eventName']."\n";
		
			//break;
		}
		return $events_sorted;
	}
}