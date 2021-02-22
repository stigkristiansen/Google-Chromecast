<?php
	class ChromecastDiscovery extends IPSModule {

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
			$devices = $this->DiscoverCCDevices();
			$ccInstances = $this->GetCCInstances();
	
			$Values = [];
	
			foreach ($devices as $id => $device) {
				$value = [
					'Ip'			=> $device['Ip'],
					'Port'       	=> $device['Port'],
					'DisplayName' 	=> $device['DisplayName'],
					'instanceID' 	=> 0,
				];
				
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					unset($ccInstances[$instanceId]);
					$value['DisplayName'] = IPS_GetName($instanceId);
					$value['instanceID'] = $instanceId;
				}
				
				$value['create'] = [
					'moduleID'      => '{935F2596-C56A-88DB-A2B8-1A4A06605206}',
					'configuration' => [
						'Ip' 			=> $device['Ip'],
						'Port' 			=> $device['Port'],
						'DisplayName' 	=> $device['DisplayName'],
						'Name' 			=> $device['Name'],
						'Id' 			=> $id
					]
				];
				
				$values[] = $value;
			}
	
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					'Ip'  			=> IPS_GetProperty($instanceId, 'Ip'),
					'Port'       	=> IPS_GetProperty($instanceId, 'Port'),
					'DisplayName'   => IPS_GetName($instanceId),
					'instanceID' 	=> $instanceId
				];
			}

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $values;
	
			return json_encode($form);
		}
	
		private function DiscoverCCDevices() : array {
			$devices = [];

			// Find DNS SD Instance
			$instanceIds = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
			if(count($instanceIds)==0)
				return $devices;

			$dnssdId = $instanceIds[0];

			$services = ZC_QueryServiceTypeEx(dnssdId, "_googlecast._tcp", "", 500);

			foreach($services as $service) {
				$device = ZC_QueryService (dnssdId , $service['Name'], $service['Type'] ,  $service['Domain']); 
				if(count($device)==0)
					continue;
					$devices[substr($device[0]['TXTRecords'][0], 3)] = [	// Id is used as index
						'Name' => $device[0]['Name'], 
						'DisplayName' => substr($device[0]['TXTRecords'][6], 3),
						'Port' => $device[0]['Port'],
						'Ip' => $device[0]['IPv4'][0]
					];	
			}

			return $devices;
		}

		private function GetCCInstances () : array {
			$devices = [];

			$instanceIds = IPS_GetInstanceListByModuleID('{935F2596-C56A-88DB-A2B8-1A4A06605206}');
        	
        	foreach ($instanceIds as $instanceId) {
				$devices[$instanceId] = IPS_GetProperty($instanceId, 'Id');
			}

			return $devices;
		}

	}