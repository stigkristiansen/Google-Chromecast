<?php
	declare(strict_types=1);

	require_once(__DIR__ . "/../libs/autoload.php");

	class ChromecastDevice extends IPSModule {
		use ServiceDiscovery;

		private $dnsSdId;

		public function __construct($InstanceID) {
			parent::__construct($InstanceID);
	
			$this->dnsSdId = $this->GetDnsSdId(); // Defined in traits.php
		}

		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString(Properties::NAME, '');
			//$this->RegisterPropertyString(Properties::TYPE, '');
			//$this->RegisterPropertyString(Properties::DOMAIN, '');
			//$this->RegisterPropertyString(Properties::DISPLAYNAME, '');
			$this->RegisterPropertyString(Properties::ID, '');

			$this->RegisterVariableString(Variables::SOURCE_IDENT, Variables::SOURCE_TEXT, '', 1);
			
			$this->RegisterTimer(Timers::UPDATE . (string) $this->InstanceID, 0, "CCDE_Update(".$this->InstanceID.");"); 
			
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

		private function SetTimer($Interval = 5000) {
			$this->SetTimerInterval(Timers::UPDATE  . (string) $this->InstanceID, $Interval);
		}
	
		public function Update() {
			try {
				$this->SetTimer(0);				
				
				$type = '';
				$domain = '';
				$found = false;
				$name = $this->ReadPropertyString(Properties::NAME);
				$services = @ZC_QueryServiceTypeEx($this->dnsSdId, "_googlecast._tcp", "", 500);
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
					$device = @ZC_QueryServiceEx($this->dnsSdId , $name, $type ,  $domain, 500); 

					if(count($device)>0) {
						$source = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'rs');
						if($source!==false)
							$this->SetValueEx(Variables::SOURCE_IDENT, $source);
						else
							$this->SetValueEx(Variables::SOURCE_IDENT, '');	
					} else
						$this->SetValueEx(Variables::SOURCE_IDENT, '');
				} else
					$this->SetValueEx(Variables::SOURCE_IDENT, '');
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