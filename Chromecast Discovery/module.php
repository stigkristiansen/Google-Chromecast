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

			$this->SendDebug(IPS_GetName($this->InstanceID), 'GetConfigurationForm(): Building Configuration form', 0);
	
			// Add devices that are discovered
			if(count($ccDevices)>0)
				$this->SendDebug(IPS_GetName($this->InstanceID), 'GetConfigurationForm(): Adding discovered devices', 0);
			else
				$this->SendDebug(IPS_GetName($this->InstanceID), 'GetConfigurationForm(): No discovered devices to add', 0);

			foreach ($ccDevices as $id => $device) {
				$value = [
					Properties::DISPLAYNAME	=> $device[Properties::DISPLAYNAME],
					'instanceID' 			=> 0,
				];

				$this->SendDebug(IPS_GetName($this->InstanceID), sprintf('GetConfigurationForm(): Added discovered device "%s"', $device[Properties::DISPLAYNAME]), 0);
				
				// Check if discovered device has an instance that is created earlier. If found, set InstanceID and DisplayName
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					$this->SendDebug(IPS_GetName($this->InstanceID), sprintf('GetConfigurationForm(): The discovered device "%s" exists as an instance. Setting InstanceId to %d', $device['DisplayName'], $instanceId), 0);
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
			if(count($ccInstances)>0)
				$this->SendDebug(IPS_GetName($this->InstanceID), 'GetConfigurationForm(): Adding existing instances that are not discovered', 0);
			
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					Properties::DISPLAYNAME => IPS_GetName($instanceId), 
					'instanceID' 			=> $instanceId
				];

				$this->SendDebug(IPS_GetName($this->InstanceID), sprintf('GetConfigurationForm(): Added existing instance "%s" with InstanceId %d', IPS_GetName($instanceId), $instanceId), 0);
			}

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $values;

			$this->SendDebug(IPS_GetName($this->InstanceID), 'GetConfigurationForm(): The Configuration form build is complete', 0);
	
			return json_encode($form);
		}
	
		private function DiscoverCCDevices() : array {
			$this->LogMessage(Messages::DISCOVER, KL_MESSAGE);

			$this->SendDebug(IPS_GetName($this->InstanceID), Debug::STARTINGDISCOVERY, 0);
			
			$devices = [];

			$services = @ZC_QueryServiceTypeEx($this->dnsSdId, "_googlecast._tcp", "", $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT));

			if($services!==false) {
				$this->SendDebug(IPS_GetName($this->InstanceID), Debug::FOUNDDEVICES, 0);
				
				if(count($services)>0) {
					foreach($services as $service) {
						$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::QUERYDETAILS, $service[Properties::NAME]), 0);
						
						$device = @ZC_QueryServiceEx ($this->dnsSdId , $service[Properties::NAME], $service[Properties::TYPE] ,  $service[Properties::DOMAIN], $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT)); 
						if($device===false || count($device)==0) {
							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::NORESPONSE, $service[Properties::NAME]), 0);
							continue;
						}
						
						$displayName = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'fn');
						$id = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'id');
						if($displayName!==false && $id!==false) {
							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::FOUNDDEVICE, $service[Properties::NAME]), 0);
						
							$devices[$id] = [	// Id is used as index
								Properties::NAME => $service[Properties::NAME],
								Properties::DISPLAYNAME => $displayName
							];	
						} else {
							$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::INVALIDRESPONSE, $service[Properties::NAME], json_encode($device[0])), 0);
							$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
						}
					}
				} else
					$this->SendDebug(IPS_GetName($this->InstanceID), Debug::NODEVICESDISCOVERED, 0);	
			} else {
				$this->SendDebug(IPS_GetName($this->InstanceID), Debug::DISCOVERYFAILED, 0);
				$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
			}

			$this->SendDebug(IPS_GetName($this->InstanceID), Debug::DISCOVERYCOMPLETED, 0);	
			
			return $devices;
		}

		private function GetCCInstances () : array {
			$devices = [];

			$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::GETTINGINSTANCES, Modules::CHROMECAST), 0);

			$instanceIds = IPS_GetInstanceListByModuleID(Modules::CHROMECAST);
        	
        	foreach ($instanceIds as $instanceId) {
				$devices[$instanceId] = IPS_GetProperty($instanceId, 'Id');
			}

			$this->SendDebug(IPS_GetName($this->InstanceID), sprintf(Debug::NUMBERFOUND, count($devices)), 0);
			$this->SendDebug(IPS_GetName($this->InstanceID), Debug::INSTANCESCOMPLETED, 0);	

			return $devices;
		}

	}