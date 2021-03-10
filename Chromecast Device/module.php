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
			
			$this->RegisterTimer(Timers::UPDATE . (string) $this->InstanceID, 0, "if(IPS_InstanceExists(" . (string) $this->InstanceID . ")) CCDE_Update(" . (string) $this->InstanceID . ");"); 
			
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

			$this->SendToCC();
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
			parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
	
			if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) 
				$this->SetTimer();
		}

		private function SetTimer($Interval = Timers::INTERVAL) {
			$this->SetTimerInterval(Timers::UPDATE . (string) $this->InstanceID, $Interval);
		}
	
		public function Update() {
			try {
				$this->SetTimer(0);	

				$type = '';
				$domain = '';
				$found = false;
				$name = $this->ReadPropertyString(Properties::NAME);

				$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::SEARCHING, $name), 0);

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
					$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::DEVICEFOUND, $name), 0);

					$device = @ZC_QueryServiceEx($this->dnsSdId , $name, $type , $domain, $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT)); 

					if($device!==false && count($device)>0) {
						$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::QUERYOK, $name), 0);
						
						$this->SendDebug(IPS_GetName($this->InstanceID), sprintf('Data returned is: %s',json_encode($device[0])), 0);
						
						$source = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'rs');  // Defined in trait ServiceDiscovery
						if($source!==false) {
							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::UPDATESTATUS, $name), 0);

							if(strpos($source, 'Casting: ')===0)  // Remove "Casting:" 
								$source = substr($source, 9);

							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::NEWVALUE, $name, $source), 0);

							$this->SetValueEx(Variables::SOURCE_IDENT, $source);
						} else {
							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::MISISNGSTREAMINGINFO, $name), 0);
							$this->SetValueEx(Variables::SOURCE_IDENT, '');	
						}
					} else {
						$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::QUERYNOINFO, $name), 0);
						$this->SetValueEx(Variables::SOURCE_IDENT, '');
					}
				} else {
					$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::DEVICENOTFOUND, $name), 0);
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

		private $socket;

		private function SendToCC() {
			$message = new Cast_channel\CastMessage;
			$message->setProtocolVersion(0);
			$message->setSourceId('sender-0');
			$message->setDestinationId('receiver-0');
			$message->setNamespace('urn:x-cast:com.google.cast.tp.connection');
			$message->setPayloadType(0);
			$message->setPayloadUtf8('{"type":"CONNECT"}');
			$this->SendDebug(IPS_GetName($this->InstanceID), $message->serializeToString(), 0);
			$ip = '192.168.50.94';
			$port = '8009';
			$errno = 0;
			$errstr = '';
			$contextOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, ]];
			$context = stream_context_create($contextOptions);
			if ($this->socket = stream_socket_client('ssl://' . $ip . ":" . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context)) {
			}
			else {
				throw new Exception("Failed to connect to remote Chromecast");
			}

			fwrite($this->socket, $message->serializeToString());
			fflush($this->socket);
			
			$statusMessage = new Cast_channel\CastMessage;
			$statusMessage->setProtocolVersion(0);
			$statusMessage->setSourceId('sender-0');
			$statusMessage->setDestinationId('receiver-0');
			$statusMessage->setNamespace('urn:x-cast:com.google.cast.receiver');
			$statusMessage->setPayloadType(0);
			$message->setPayloadUtf8('{"type":"GET_STATUS","requestId":1}');
			fwrite($this->socket, $statusMessage->serializeToString());
			fflush($this->socket);

			$r ='';
			while ($this->transportid == '') {
				$r = $this->getCastMessage();
			}
			return $r;

			$this->SendDebug(IPS_GetName($this->InstanceID), sprintf('Chromecast status returned: %s', $r), 0);

			
		}

		private $transportid='';
		private $sessionid='';

		private function getCastMessage(){
		// Get the Chromecast Message/Response
		// Later on we could update CCprotoBuf to decode this
		// but for now all we need is the transport id  and session id if it is
		// in the packet and we can read that directly.
		//$this->testLive();
		$response = fread($this->socket, 2000);
		while (preg_match("/urn:x-cast:com.google.cast.tp.heartbeat/", $response) && preg_match("/\"PING\"/", $response)) {
			//$this->pong();
			sleep(3);
			$response = fread($this->socket, 2000);
			// Wait infinitely for a packet.
			set_time_limit(30);
		}
		if (preg_match("/transportId/s", $response)) {
			preg_match("/transportId\"\:\"([^\"]*)/", $response, $matches);
			$matches = $matches[1];
			$this->transportid = $matches;
		}
		if (preg_match("/sessionId/s", $response)) {
			preg_match("/\"sessionId\"\:\"([^\"]*)/", $response, $r);
			$this->sessionid = $r[1];
		}
		return $response;
	}
	}