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
			$devices = $this->DiscoverCCDevices();
			$ccInstances = $this->GetCCInstances();
	
			$values = [];
	
			foreach ($devices as $id => $device) {
				$value = [
					'DisplayName' 	=> $device['DisplayName'],
					'instanceID' 	=> 0,
				];
				
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					unset($ccInstances[$instanceId]);
					//$value['DisplayName'] = IPS_GetName($instanceId);
					$value['instanceID'] = $instanceId;
				}
				
				$value['create'] = [
					'moduleID'      => '{935F2596-C56A-88DB-A2B8-1A4A06605206}',
					'configuration' => [
						'DisplayName' 	=> $device['DisplayName'],
						'Name' 			=> $device['Name'],
						'Type' 			=> $device['Type'],
						'Domain' 		=> $device['Domain'],
						'Id' 			=> $id
					]
				];
				
				$values[] = $value;
			}
	
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					'DisplayName'   => IPS_GetProperty($instanceId, 'DisplayName'), //IPS_GetName($instanceId),
					'instanceID' 	=> $instanceId
				];
			}

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $values;
	
			return json_encode($form);
		}
	
		private function DiscoverCCDevices() : array {
			$devices = [];

			//IPS_LogMessage('Chromecast Discovery','Inside DiscoverCCDevices');

			// Find DNS SD Instance
			$instanceIds = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
			if(count($instanceIds)==0)
				return $devices;

			$dnssdId = $instanceIds[0];
			//IPS_LogMessage('Chromecast Discovery','Found DNS SD: '. (string)$dnssdId );

			$services = @ZC_QueryServiceTypeEx($dnssdId, "_googlecast._tcp", "", 500);

			foreach($services as $service) {
				$device = @ZC_QueryServiceEx ($dnssdId , $service['Name'], $service['Type'] ,  $service['Domain'], 500); 
				if($device===false)
					continue;
				
				$displayName = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'fn');
				$id = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'id');
				if($displayName!==false && $id!==false) {
					//$devices[substr($device[0]['TXTRecords'][0], 3)] = [	// Id is used as index
						$devices[$id] = [	// Id is used as index
							'Name' => $service['Name'],
							'Type' => $service['Type'],
							'Domain' =>$service['Domain'],
							//'DisplayName' => substr($device[0]['TXTRecords'][6], 3),
							'DisplayName' => $displayName
						];	
				} else
					$this->LogMessage('Returned TXT-records are invalid', KL_ERROR);
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