<?Php
class MyRCM
{
	public $soap=false;
	private $auth=false;
	public $error;
	public $lang='en';

	function soap_connect($federation)
	{
		if(file_exists($configfile='config_'.$federation.'.php'))
			require $configfile;
		else
			throw new Exception("Could not find config file for $federation");

		$this->soap=new soapClient('RemoteServicePort.xml',array('trace'=>true));
		$return=$this->soap->authenticate(array('user'=>$config['SOAP_user'],'password'=>$config['SOAP_password']));
		$this->auth=$return->authorizationDto->authorization;
		return $this->auth;
	}
	function soapCall($function_name,$arguments)
	{
		$return=$this->soap->__soapCall($function_name,array(array_merge(array('authorization'=>$this->auth),$arguments)));
		$return=reset($return);

		if($return->code=='ERROR' || $return->code=='FAILED')
		{
			$this->error=$return->message;
			return false;
		}
		else
			return $return;
	}
	function getEventList($eventType='ALL')
	{
		return $this->soapCall('getEventList',array('eventType'=>$eventType));
	}
	function getSectionList($eventKey)
	{
		$return=$this->soapCall('getSectionList',array('eventKey'=>$eventKey));
		if($return===false)
			return false;
		else
		{
			$return=$return->sectionListDto->SectionDto;
			if(isset($return->primaryKey))
				return array($return);
			else
				return $return;
		}
		//return $this->soap->getSectionList(array('authorization'=>$this->auth,'eventKey'=>$eventKey));
	}
	/*function reporturl($event_primaryKey,$section_primaryKey) //XML Report URL
	{
		$data=file_get_contents('http://www.myrcm.ch/myrcm/report/en/22358/156077?treeView=Final&cType=XML');
		return sprintf('http://www.myrcm.ch/myrcm/report/en/%s/%s?reportKey=%s&cType=XML',$event_primaryKey,$section_primaryKey,$reportKey);
	}*/	
	function ReportUrl($event_primaryKey,$section_primaryKey)
	{
		return sprintf('http://www.myrcm.ch/myrcm/report/en/%s/%s',$event_primaryKey,$section_primaryKey);
	}
	function FinalRankingList($event_primaryKey,$section_primaryKey)
	{
		$ReportUrl=$this->ReportUrl($event_primaryKey,$section_primaryKey);
		$xml=simplexml_load_file($ReportUrl.'?treeView=RankingList&cType=XML');
		foreach($xml->item as $item) //Loop through the items to find the id for the final
		{
			if((string)$item->attributes()->text=='Final')
			{
				$reportKey=((int)$item->attributes()->id);
				break;
			}
		}
		$url=sprintf('%s?reportKey=%s&cType=XML',$ReportUrl,$reportKey);
		$xml_string=file_get_contents($url);
		if(empty($xml_string))
		{
			$this->error=sprintf('<a href="%s">%s</a>',$this->eventlink($event_primaryKey),sprintf(_('No data on MyRCM for event %d section %d'),$event_primaryKey,$section_primaryKey));
			return false;
		}
		$xml_string=str_replace('UTF-16','UTF-8',$xml_string); //XML header says UTF-16, but content is UTF-8
		$xml=simplexml_load_string($xml_string);
		
		if($xml===false)
			$this->error='Error loading XML';
		return $xml;
	}
	function eventlink($eventKey)
	{
		return sprintf('http://www.myrcm.ch/myrcm/main?pLa=%s&hId[1]=com&dId[E]=%d',$this->lang,$eventKey);
	}

}