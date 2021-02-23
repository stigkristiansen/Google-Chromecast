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
	}

	class Actions {
		const UPDATE = 'Update';
	}

	class ChromecastDevice extends IPSModule {

		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString('Ip', '');
			$this->RegisterPropertyInteger('Port', 0);
			$this->RegisterPropertyString('Name', '');
			$this->RegisterPropertyString('DisplayName', '');
			$this->RegisterPropertyString('Id', '');
			
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
			IPS_LogMessage('Chromecast Device', 'Inside SetTimer()');
			$this->SetTimerInterval(Timers::UPDATE  . (string) $this->InstanceID, 5000);
		}
	
		public function RequestAction($Ident, $Value) {
			IPS_LogMessage('Chromecast Device', 'RequestAction');
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
		}


	}