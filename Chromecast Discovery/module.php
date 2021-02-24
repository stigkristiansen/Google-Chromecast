<?php
	declare(strict_types=1);

	require_once(__DIR__ . "/../libs/autoload.php");

	class ChromecastDiscovery extends IPSModule {
		use ServiceDiscovery;

		public function Create()
		{
			//Never delete this line!
			parent::Create();
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
					Properties::DISPLAYNAME 	=> $device['DisplayName'],
					'instanceID' 	=> 0,
				];
				
				// Check if discoverd device have a instance that are created earlier. If found, set InstanceID
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					unset($ccInstances[$instanceId]);
					$value['instanceID'] = $instanceId;
				}
				
				$value['create'] = [
					'moduleID'      => Modules::CHROMECAST,
					'configuration' => [
						Properties::DISPLAYNAME	=> $device[Properties::DISPLAYNAME],
						Properties::NAME 		=> $device[Properties::NAME],
						Properties::TYPE		=> $device[Properties::TYPE],
						Properties::DOMAIN 		=> $device[Properties::DOMAIN],
						Properties::ID 			=> $id
					]
				];
				
				$values[] = $value;
			}

			// Add devices that are not discovered, but created earlier
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					'DisplayName'   => IPS_GetProperty($instanceId, Properties::DISPLAYNAME), 
					'instanceID' 	=> $instanceId
				];
			}

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $values;
	
			return json_encode($form);
		}
	
		private function DiscoverCCDevices() : array {
			$this->LogMessage(Messages::DISCOVER, KL_MESSAGE);
			
			$devices = [];

			// Find DNS SD Instance
			$instanceIds = IPS_GetInstanceListByModuleID(Modules::DNSSD);
			if(count($instanceIds)==0)
				return $devices;

			$dnssdId = $instanceIds[0];
			
			$services = @ZC_QueryServiceTypeEx($dnssdId, "_googlecast._tcp", "", 500);

			foreach($services as $service) {
				$device = @ZC_QueryServiceEx ($dnssdId , $service[Properties::NAME], $service[Properties::TYPE] ,  $service[Properties::DOMAIN], 500); 
				if($device===false)
					continue;
				
				$displayName = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'fn');
				$id = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'id');
				if($displayName!==false && $id!==false) {
						$devices[$id] = [	// Id is used as index
							Properties::NAME => $service[Properties::NAME],
							Properties::TYPE => $service[Properties::TYPE],
							Properties::DOMAIN =>$service[Properties::DOMAIN],
							Properties::DISPLAYNAME => $displayName
						];	
				} else
					$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
			}

			return $devices;
		}

		private function GetCCInstances () : array {
			$devices = [];

			$instanceIds = IPS_GetInstanceListByModuleID(Modules::CHROMECAST);
        	
        	foreach ($instanceIds as $instanceId) {
				$devices[$instanceId] = IPS_GetProperty($instanceId, 'Id');
			}

			return $devices;
		}

	}