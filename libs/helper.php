<?php

    class Timers {
        const UPDATE = 'CCDEUpdate';
        const INTERVAL = 5000;
    }

    class Variables {
        const SOURCE_IDENT = 'Source';
        const SOURCE_TEXT = 'Source';
    }

    class Errors {
        const UNEXPECTED  = 'An unexpected error occured. The error was : %s';
        const MISSINGDNSSD = 'Did not find any instances of DNS-SD';
        const INVALIDRESPONSE = 'Returned TXT-records are invalid';
    }

    class Messages {
        const DISCOVER = 'Discovering Chromecast devices...';
    }

    class Actions {
        const UPDATE = 'CCDEUpdate';
    }

    class Properties {
        const NAME = 'Name';
        const TYPE = 'Type';
        const DOMAIN = 'Domain';
        const DISPLAYNAME = 'DisplayName';
        const ID = 'Id';
        const DISCOVERYTIMEOUT = 'DiscoveryTimeout';
    }

    class Modules {
        const CHROMECAST = '{935F2596-C56A-88DB-A2B8-1A4A06605206}';
        const DNSSD = '{780B2D48-916C-4D59-AD35-5A429B2355A5}';
    }

    class Debug {
        const SEARCHING = 'Update: Searching for device with name "%s"';
        const DEVICEFOUND = 'Update: Found device "%s". Querying for more information';
        const QUERYOK = 'Update: The query returned information for "%s"';
        const UPDATESTATUS = 'Update: Updating statusvariable "Source" for "%s"';
        const NEWVALUE = 'Update: New value for "Source" for "%s" is "%s"';
        const MISISNGSTREAMINGINFO = 'Update: Did not find streaming information for "%s"';
        const QUERYNOINFO = 'Update: The query did not return any information for "%s"';
        const DEVICENOTFOUND = 'Update: The device "%s" was not found';
        const NUMBERFOUND = 'GetCCInstances(): Added %d instances of Chromecast device(s) to the list';
        const GETTINGINSTANCES = 'GetCCInstances(): Getting list of all created Chromecast devices (module id: %s)';
        const DISCOVERYFAILED = 'DiscoverCCDevices(): The discovery of Chromecast devices failed';
        const NODEVICESDISCOVERED = 'DiscoverCCDevices(): Did not find any Chromecast devices on the network';
        const INVALIDRESPONSE = 'DiscoverCCDevices(): Invalid query response from "%s. The response was: %s"';
        const DEVICEFOUND = 'DiscoverCCDevices(): "%s" reponded to the query. Adding it to the list';
        const NORESPONSE = 'DiscoverCCDevices(): No Query response from "%s"';
        const QUERYDETAILS = 'DiscoverCCDevices(): Querying "%s" for more information';
        const FOUNDDEVICES = 'DiscoverCCDevices(): Found Chromecast devices';
        const STARTINGDISCOVERY = 'DiscoverCCDevices(): Starting discovery of Chromecast devices';


    }