<?php
	class ChromecastDevice extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString('Ip', '');
			$this->RegisterPropertyString('Name', '');
			$this->RegisterPropertyString('DisplayName', '');
			$this->RegisterPropertyString('Id', '');
			$this->RegisterPropertyInteger('Port', 0);
	
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

	}