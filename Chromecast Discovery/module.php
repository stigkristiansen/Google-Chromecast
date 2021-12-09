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

			$this->SetBuffer(Buffer::DEVICES, json_encode([]));
            $this->SetBuffer(Buffer::SEARCHINPROGRESS, json_encode(false));
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
			$this->SendDebug(__FUNCTION__, , 0);
            $this->SendDebug(__FUNCTION__, sprintf(Debug::SEARCHIS, json_decode($this->GetBuffer(Buffer::SEARCHINPROGRESS))?'TRUE':'FALSE'), 0);
            			
			$devices = json_decode($this->GetBuffer(Buffer::DEVICES));
           
			if (!json_decode($this->GetBuffer(Buffer::SEARCHINPROGRESS))) {
                $this->SendDebug(__FUNCTION__, Debug::SEARCHTRUE, 0);
				$this->SetBuffer(Buffer::SEARCHINPROGRESS, json_encode(true));
				
				$this->SendDebug(__FUNCTION__, Debug::STARTTIMER, 0);
				$this->RegisterOnceTimer(Timers::LOADDEVICE, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "Discover", 0);');
            }

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['visible'] = count($devices)==0;
			
			$this->SendDebug(__FUNCTION__, Debug::ADDCACHEDDEVICES, 0);
			$form['actions'][1]['values'] = $devices;

			$this->SendDebug(__FUNCTION__, Debug::GENERATINGFORMDONE, 0);

            return json_encode($form);
		}

		public function RequestAction($Ident, $Value) {
			$this->SendDebug( __FUNCTION__ , sprintf(Debug::REQUESTACTION, $Ident, (string)$Value), 0);

			switch (strtolower($Ident)) {
				case 'discover':
					$this->SendDebug(__FUNCTION__, Debug::CALLLOADDEVICES, 0);
					$this->LoadDevices();
					break;
			}
		}

		private function LoadDevices() {
			$this->SendDebug(__FUNCTION__, Debug::BUILDINGFORM, 0);

			$ccDevices = $this->DiscoverCCDevices();
			$ccInstances = $this->GetCCInstances();

			$this->SendDebug(__FUNCTION__, Debug::SEARCHFALSE, 0);
			$this->SetBuffer(Buffer::SEARCHINPROGRESS, json_encode(false));
	
			$values = [];

			// Add devices that are discovered
			if(count($ccDevices)>0)
				$this->SendDebug(__FUNCTION__, Debug::ADDINGDISCOVEREDDEVICE, 0);
			else
				$this->SendDebug(__FUNCTION__, Debug::NODEVICEDISCOVERED, 0);

			
			foreach ($ccDevices as $id => $device) {
				$value = [
					Properties::DISPLAYNAME	=> $device[Properties::DISPLAYNAME],
					'instanceID' 			=> 0,
				];

				$this->SendDebug(__FUNCTION__, sprintf(Debug::ADDEDDISCOVEREDDEVICE, $device[Properties::DISPLAYNAME]), 0);
				
				// Check if discovered device has an instance that is created earlier. If found, set InstanceID and DisplayName
				$instanceId = array_search($id, $ccInstances);
				if ($instanceId !== false) {
					$this->SendDebug(__FUNCTION__, sprintf(Debug::ADDINSTANCETODEVICE, $device[Properties::DISPLAYNAME], $instanceId), 0);
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
				$this->SendDebug(__FUNCTION__, Debug::ADDINGEXISTINGINSTANCE, 0);
			
			foreach ($ccInstances as $instanceId => $id) {
				$values[] = [
					Properties::DISPLAYNAME => IPS_GetName($instanceId), 
					'instanceID' 			=> $instanceId
				];

				$this->SendDebug(__FUNCTION__, sprintf(Debug::ADDINGINSTANCE, IPS_GetName($instanceId), $instanceId), 0);
			}

			$newDevices = json_encode($values);
			$this->SetBuffer(Buffer::DEVICES, $newDevices);

			$this->UpdateFormField('Discovery', 'values', $newDevices);
            $this->UpdateFormField('SearchingInfo', 'visible', false);

			$this->SendDebug(__FUNCTION__, Debug::DISCOVERYFORMCOMPLETED, 0);
		}
	
		private function DiscoverCCDevices() : array {
			$this->LogMessage(Messages::DISCOVER, KL_NOTIFY);

			$this->SendDebug(__FUNCTION__, Debug::STARTINGDISCOVERY, 0);
			
			$devices = [];

			$services = @ZC_QueryServiceTypeEx($this->dnsSdId, "_googlecast._tcp", "", $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT));

			if($services!==false) {
				$this->SendDebug(__FUNCTION__, Debug::FOUNDDEVICES, 0);
				
				if(count($services)>0) {
					foreach($services as $service) {
						$this->SendDebug(__FUNCTION__, sprintf(Debug::QUERYDETAILS, $service[Properties::NAME]), 0);
						
						$device = @ZC_QueryServiceEx ($this->dnsSdId , $service[Properties::NAME], $service[Properties::TYPE] ,  $service[Properties::DOMAIN], $this->ReadPropertyInteger(Properties::DISCOVERYTIMEOUT)); 
						if($device===false || count($device)==0) {
							$this->SendDebug(__FUNCTION__, sprintf(Debug::NORESPONSE, $service[Properties::NAME]), 0);
							continue;
						}
						
						$displayName = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'fn');
						$id = $this->GetServiceTXTRecord($device[0]['TXTRecords'], 'id');
						if($displayName!==false && $id!==false) {
							$this->SendDebug(__FUNCTION__, sprintf(Debug::FOUNDDEVICE, $service[Properties::NAME]), 0);
						
							$devices[$id] = [	// Id is used as index
								Properties::NAME => $service[Properties::NAME],
								Properties::DISPLAYNAME => $displayName
							];	
						} else {
							$this->SendDebug(__FUNCTION__, sprintf(Debug::INVALIDRESPONSE, $service[Properties::NAME], json_encode($device[0])), 0);
							$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
						}
					}
				} else
					$this->SendDebug(__FUNCTION__, Debug::NODEVICESDISCOVERED, 0);	
			} else {
				$this->SendDebug(__FUNCTION__, Debug::DISCOVERYFAILED, 0);
				$this->LogMessage(Errors::INVALIDRESPONSE, KL_ERROR);
			}

			$this->SendDebug(__FUNCTION__, Debug::DISCOVERYCOMPLETED, 0);	
			
			return $devices;
		}

		private function GetCCInstances () : array {
			$devices = [];

			$this->SendDebug(__FUNCTION__, sprintf(Debug::GETTINGINSTANCES, Modules::CHROMECAST), 0);

			$instanceIds = IPS_GetInstanceListByModuleID(Modules::CHROMECAST);
        	
        	foreach ($instanceIds as $instanceId) {
				$devices[$instanceId] = IPS_GetProperty($instanceId, 'Id');
			}

			$this->SendDebug(__FUNCTION__, sprintf(Debug::NUMBERFOUND, count($devices)), 0);
			$this->SendDebug(__FUNCTION__, Debug::INSTANCESCOMPLETED, 0);	

			return $devices;
		}

	}