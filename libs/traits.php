<?php

	declare(strict_types=1);

	trait ServiceDiscovery {
		private function GetServiceTXTRecord($Records, $Key) {
			foreach($Records as $record) {
				if(stristr($record, $Key.'=')!==false)
					return substr($record, 3);
			}

			return false;
		}
	}

