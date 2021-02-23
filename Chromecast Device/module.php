<?php
	class Timers {
		public static UPDATE = 'Update';
	}

	class Variables {
		public static SOURCE_IDENT = 'Source';
		public static SOURCE_TEXT = 'Source';
	}

	class Errors {
		public static UNEXPECTED  = 'An unexpected error occured. The error was : %s';
	}

	class Actions {
		public static UPDATE = 'Update';
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

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
			parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
	
			if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 
				$this->SetTimer();
		}

		private function SetTimer() {
			$this->SetTimerInterval(Timers::UPDATE  . (string) $this->InstanceID, 5000);
		}
	
		public function RequestAction($Ident, $Value) {
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
			IPS_LogMessage('Chromecast Device', 'Inside function Update...');
		}

		public function Destroy() {
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
		}

	}