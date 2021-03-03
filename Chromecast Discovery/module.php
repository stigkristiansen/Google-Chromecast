<?php
	declare(strict_types=1);

	require_once(__DIR__ . "/../libs/autoload.php");

	class ChromecastDiscovery extends IPSModule {
		use ServiceDiscovery;

		private $dnsSdId;

		public function __construct($InstanceID) {
			parent::__construct($InstanceID);
	
			$this->dnsSdId = $this->GetDnsSdId(); // Defined in traits.php
		}

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyInteger(Properties::DISCOVERYTIMEOUT, 500);
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function GetConfigurationForm() {
			$ccDevices = $this->DiscoverCCDevices();
			$ccInstances = $this->GetCCInstances();
	
			$values = [];
	
			// Add devices that are discovered
			foreach ($ccDevices as $id => $device) {
				$value = [
					Properties::DISPLAYNAME	=> $device['DisplayName'],
					'instanceID' 			=> 0,
				];
				
				// Check if discovered device has an instance that is created earlier. If found, set InstanceID and DisplayName
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					unset($ccInstances[$instanceId]); // Remove from list to avoid duplicates
					$value[Properties::DISPLAYNAME] = IPS_GetName($instanceId);
					$value['instanceID'] = $instanceId;
				} 
				
				$value['create'] = [
					'moduleID'      => Modules::CHROMECAST,
					'name'			=> $device[Properties::DISPLAYNAME],
					'configuration' => [
						Properties::NAME => $device[Properties::NAME],
						Properties::ID 	 => $id
					]
				];
			
				$values[] = $value;
			}

			// Add devices that are not discovered, but created earlier
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					Properties::DISPLAYNAME => IPS_GetName($instanceId), 
					'instanceID' 			=> $instanceId
				];
			}

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $values;
	
			return json_encode($form);
		}
	
		private function DiscoverCCDevices() : array {
			$this->LogMessage(Messages::DISCOVER, KL_MESSAGE);

			$this->SendDebug('Chromecast Discovery', Debug::STARTINGDISCOVERY, 0);
			
			$devices = [];

			$services = @ZC_QueryServiceTypeEx($this->dnsSdId, "_googlecast._tcp", "", $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT));

			if($services!==false) {
				$this->SendDebug(Debug::FOUNDDEVICES, 0);
				
				if(count($services)>0) {
					foreach($services as $service) {
						$this->SendDebug('Chromecast Discovery', sprintf(Debug::QUERYDETAILS, $service[Properties::NAME]), 0);
						
						$device = @ZC_QueryServiceEx ($this->dnsSdId , $service[Properties::NAME], $service[Properties::TYPE] ,  $service[Properties::DOMAIN], $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT)); 
						if($device===false || count($device)==0) {
							$this->SendDebug('Chromecast Discovery', sprintf(Debug::NORESPONSE, $service[Properties::NAME]), 0);
							continue;
						}
						
						$displayName = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'fn');
						$id = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'id');
						if($displayName!==false && $id!==false) {
							$this->SendDebug('Chromecast Discovery', sprintf(Debug::DEVICEFOUND, $service[Properties::NAME]), 0);
						
							$devices[$id] = [	// Id is used as index
								Properties::NAME => $service[Properties::NAME],
								Properties::DISPLAYNAME => $displayName
							];	
						} else {
							$this->SendDebug('Chromecast Discovery', sprintf(Debug::INVALIDRESPONSE, $service[Properties::NAME], json_encode($device[0])), 0);
							$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
						}
					}
				} else
					$this->SendDebug('Chromecast Discovery', Debug::NODEVICESDISCOVERED, 0);	
			} else {
				$this->SendDebug('Chromecast Discovery', Debug::DISCOVERYFAILED, 0);
				$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
			}

			return $devices;
		}

		private function GetCCInstances () : array {
			$devices = [];

			$this->SendDebug('Chromecast Discovery', sprintf(Debug::GETTINGINSTANCES, Modules::CHROMECAST), 0);

			$instanceIds = IPS_GetInstanceListByModuleID(Modules::CHROMECAST);
        	
        	foreach ($instanceIds as $instanceId) {
				$devices[$instanceId] = IPS_GetProperty($instanceId, 'Id');
			}

			$this->SendDebug('Chromecast Discovery', sprintf(Debug::NUMBERFOUND, count($devices)), 0);

			return $devices;
		}

	}