<?php
declare(strict_types=1);

require_once(__DIR__ . "/../libs/autoload.php");

class ChromecastDevice extends IPSModule {
	use ServiceDiscovery;

	private $dnsSdId;

	public function __construct($InstanceID) {
		parent::__construct($InstanceID);

		$this->dnsSdId = $this->GetDnsSdId(); // Defined in trait ServiceDiscovery
	}

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->RegisterPropertyInteger(Properties::DISCOVERYTIMEOUT, 500);

		$this->RegisterPropertyString(Properties::NAME, '');
		$this->RegisterPropertyString(Properties::ID, '');

		$this->RegisterVariableString(Variables::SOURCE_IDENT, Variables::SOURCE_TEXT, '', 1);
		
		//$this->RegisterTimer(Timers::UPDATE . (string) $this->InstanceID, 0, "if(IPS_InstanceExists(" . (string) $this->InstanceID . ")) CCDE_Update(" . (string) $this->InstanceID . ");"); 
		$this->RegisterTimer(Timers::UPDATE . (string) $this->InstanceID, 0, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "Update", 0);'); 
		
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() == KR_READY)
			$this->SetTimer();
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 
			$this->SetTimer();
	}

	public function RequestAction($Ident, $Value) {
		$this->SendDebug( __FUNCTION__ , sprintf(Debug::REQUESTACTION, $Ident, (string)$Value), 0);

		switch (strtolower($Ident)) {
			case 'update':
				$this->SendDebug(__FUNCTION__, Debug::CALLUPDATE, 0);
				$this->Update();
				break;
		}
	}

	private function SetTimer($Interval = Timers::INTERVAL) {
		$this->SendDebug( __FUNCTION__ , sprintf(Debug::UPDATETIMER, $Interval), 0);
		$this->SetTimerInterval(Timers::UPDATE . (string) $this->InstanceID, $Interval);
	}

	private function Update() {
		try {
			$this->SetTimer(0);	

			$type = '';
			$domain = '';
			$found = false;
			$name = $this->ReadPropertyString(Properties::NAME);

			$this->SendDebug(__FUNCTION__, sprintf(Debug::SEARCHING, $name), 0);

			$services = @ZC_QueryServiceTypeEx($this->dnsSdId, "_googlecast._tcp", "", $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT));
			if($services!==false) {
				foreach($services as $service) {
					if(strcasecmp($service[Properties::NAME], $name)==0) {
						$found = true;
						$domain = $service[Properties::DOMAIN];
						$type = $service[Properties::TYPE];
						break;
					}
				}
			}

			if($found) {
				$this->SendDebug(__FUNCTION__, sprintf(Debug::DEVICEFOUND, $name), 0);

				$device = @ZC_QueryServiceEx($this->dnsSdId , $name, $type , $domain, $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT)); 

				if($device!==false && count($device)>0) {
					$this->SendDebug(__FUNCTION__, sprintf(Debug::QUERYOK, $name), 0);
					
					$this->SendDebug(__FUNCTION__, sprintf('Data returned is: %s',json_encode($device[0])), 0);
					
					$source = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'rs');  // Defined in trait ServiceDiscovery
					if($source!==false) {
						$this->SendDebug(__FUNCTION__, sprintf(Debug::UPDATESTATUS, $name), 0);

						if(strpos($source, 'Casting: ')===0)  // Remove "Casting:" 
							$source = substr($source, 9);

						$this->SendDebug(__FUNCTION__, sprintf(Debug::NEWVALUE, $name, $source), 0);

						$this->SetValueEx(Variables::SOURCE_IDENT, $source);
					} else {
						$this->SendDebug(__FUNCTION__, sprintf(Debug::MISISNGSTREAMINGINFO, $name), 0);
						$this->SetValueEx(Variables::SOURCE_IDENT, '');	
					}
				} else {
					$this->SendDebug(__FUNCTION__, sprintf(Debug::QUERYNOINFO, $name), 0);
					$this->SetValueEx(Variables::SOURCE_IDENT, '');
				}
			} else {
				$this->SendDebug(__FUNCTION__, sprintf(Debug::DEVICENOTFOUND, $name), 0);
				$this->SetValueEx(Variables::SOURCE_IDENT, '');
			}
		} catch(Exception $e) {
				$this->LogMessage(sprintf(Errors::UNEXPECTED,  $e->getMessage()), KL_ERROR);
		} finally {
			$this->SetTimer();
		}
	}

	private function SetValueEx(string $Ident, $Value) {
		$oldValue = $this->GetValue($Ident);
		if($oldValue!=$Value)
			$this->SetValue($Ident, $Value);
	}

}