<?php
	declare(strict_types=1);

	require_once(__DIR__ . "/../libs/autoload.php");

	class ChromecastDevice extends IPSModule {
		use ServiceDiscovery;

		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString(Properties::NAME, '');
			$this->RegisterPropertyString(Properties::TYPE, '');
			$this->RegisterPropertyString(Properties::DOMAIN, '');
			$this->RegisterPropertyString(Properties::DISPLAYNAME, '');
			$this->RegisterPropertyString(Properties::ID, '');
			
			$source = $this->RegisterVariableString(Variables::SOURCE_IDENT, Variables::SOURCE_TEXT, '', 1);
			$this->EnableAction(Variables::SOURCE_IDENT);
			$this->RegisterTimer(Timers::UPDATE . (string) $this->InstanceID, 0, "if(IPS_VariableExists(".$source.")) RequestAction(".$source.", '" .Actions::UPDATE."');"); 
			
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

		private function SetTimer() {
			//IPS_LogMessage('Chromecast Device', 'Inside SetTimer()');
			$this->SetTimerInterval(Timers::UPDATE  . (string) $this->InstanceID, 5000);
		}
	
		public function RequestAction($Ident, $Value) {
			//IPS_LogMessage('Chromecast Device', 'Inside RequestAction()');
			try {
				switch ($Ident) {
					case Variables::SOURCE_IDENT:
						switch($Value) {
							case Actions::UPDATE: 
								$this->Update();
								break;
						}
						break;
				}
		 
			} catch(Exception $e) {
				$this->LogMessage(sprintf(Errors::UNEXPECTED,  $e->getMessage()), KL_ERROR);
			}
		}

		private function Update() {
			// Try to find a DNS SD instance
			$instanceIds = IPS_GetInstanceListByModuleID(Modules::DNSSD);
			if(count($instanceIds)==0) {
				$this->LogMessage(Errors::MISSINGDNSSD, KL_ERROR);
				return;
			}
			
			$dnssdId = $instanceIds[0];

			$found = false;
			$name = $this->ReadPropertyString(Properties::NAME);
			$services = @ZC_QueryServiceTypeEx($dnssdId, "_googlecast._tcp", "", 500);
			if($services!==false) {
				foreach($services as $service) {
					if(strcasecmp($service[Properties::NAME], $name)==0) {
						$found = true;
						break;
					}
				}
			}

			if($found) {
				$type = $this->ReadPropertyString(Properties::TYPE);
				$domain = $this->ReadPropertyString(Properties::DOMAIN); 
				
				$device = @ZC_QueryServiceEx($dnssdId , $name, $type ,  $domain, 500); 

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
		}

		private function SetValueEx(string $Ident, $Value) {
			$oldValue = $this->GetValue($Ident);
			if($oldValue!=$Value)
				$this->SetValue($Ident, $Value);
		}
	}