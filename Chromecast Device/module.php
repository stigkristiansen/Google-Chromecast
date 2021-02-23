<?php
	class Timers {
		const UPDATE = 'CCDEUpdate';
	}

	class Variables {
		const SOURCE_IDENT = 'Source';
		const SOURCE_TEXT = 'Source';
	}

	class Errors {
		const UNEXPECTED  = 'An unexpected error occured. The error was : %s';
		const MISSINGDNSSD = 'Did not find any instances of DNS-SD';
	}

	class Actions {
		const UPDATE = 'CCDEUpdate';
	}

	class Properties {
		const IP = 'Ip';
		const PORT = 'Port';
		const NAME = 'Name';
		const TYPE = 'Type';
		const DOMAIN = 'Domain';
		const DISPLAYNAME = 'DisplayName';
		const ID = 'Id';
	}

	class ChromecastDevice extends IPSModule {

		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString(Properties::IP, '');
			$this->RegisterPropertyInteger(Properties::PORT, 0);
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
			IPS_LogMessage('Chromecast Device', 'Inside Update()');
			
			$instanceIds = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
			if(count($instanceIds)==0) {
				return;
				$this->LogMessage(Errors::MISSINGDNSSD, KL_ERROR);
			}
			
			$dnssdId = $instanceIds[0];

			$name = $this->ReadPropertyString(Properties::NAME);
			$type = $this->ReadPropertyString(Properties::TYPE);
			$domain = $this->ReadPropertyString(Properties::DOMAIN); 

			//IPS_LogMessage('Chromecast Device', $name . ':' . $type . ':' . $domain);

			$device = ZC_QueryService ($dnssdId , $name, $type ,  $domain); 

			$this->SetValueEx(Variables::SOURCE_IDENT, substr($device[0]['TXTRecords'][11], 3));
		}

		private function SetValueEx(string $Ident, $Value) {
			$oldValue = $this->GetValue($Ident);
			if($oldValue!=$Value)
				$this->SetValue($Ident, $Value);
		}
	}