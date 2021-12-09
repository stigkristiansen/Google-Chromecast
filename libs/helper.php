<?php

    class Timers {
        const UPDATE = 'CCDEUpdate';
        const INTERVAL = 5000;
        const LOADDEVICE = 'LoadDevicesTimer';
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
        const CHROMECAST = '{26810601-2C6A-4663-BDB3-053FBEEA39EA}';
        const DNSSD = '{780B2D48-916C-4D59-AD35-5A429B2355A5}';
    }

    class Buffer {
        const SEARCHINPROGRESS = 'SearchInProgress';
        const DEVICES = 'Devices';
    }

    class Debug {
        const SEARCHING = 'Searching for device with name "%s"';
        const DEVICEFOUND = 'Found device "%s". Querying for more information';
        const QUERYOK = 'The query returned information for "%s"';
        const UPDATESTATUS = 'Updating statusvariable "Source" for "%s"';
        const NEWVALUE = 'New value for "Source" for "%s" is "%s"';
        const MISISNGSTREAMINGINFO = 'Did not find streaming information for "%s"';
        const QUERYNOINFO = 'The query did not return any information for "%s"';
        const DEVICENOTFOUND = 'The device "%s" was not found';
        const INSTANCESCOMPLETED = 'Building list of instances completed';
        const NUMBERFOUND = 'Added %d instance(s) of Chromecast device(s) to the list';
        const GETTINGINSTANCES = 'Getting list of all created Chromecast devices (module id: %s)';
        const DISCOVERYFAILED = 'The discovery of Chromecast devices failed';
        const NODEVICESDISCOVERED = 'Did not find any Chromecast devices on the network';
        const INVALIDRESPONSE = 'Invalid query response from "%s. The response was: %s"';
        const FOUNDDEVICE = '"%s" reponded to the query. Adding it to the list';
        const NORESPONSE = 'No Query response from "%s"';
        const QUERYDETAILS = 'Querying "%s" for more information';
        const FOUNDDEVICES = 'Found Chromecast device(s)';
        const STARTINGDISCOVERY = 'Starting discovery of Chromecast devices';
        const DISCOVERYCOMPLETED = 'The discovery is completed';
        const FORMCOMPLETED = 'The Configuration form build is complete';
        const ADDINGINSTANCE = 'Added existing instance "%s" with InstanceId %d';
        const ADDINGEXISTINGINSTANCE = 'Adding existing instances that are not discovered';
        const ADDINSTANCETODEVICE = 'The discovered device "%s" exists as an instance. Setting InstanceId to %d';
        const ADDEDDISCOVEREDDEVICE = 'Added discovered device "%s"';
        const NODEVICEDISCOVERED = 'No discovered devices to add';
        const ADDINGDISCOVEREDDEVICE = 'Adding discovered device(s)';
        const BUILDINGFORM = 'Building Discovery form';
        const SEARCHFALSE = 'Setting SearchInProgress to FALSE';
        const SEARCHTRUE = 'Setting SearchInProgress to TRUE';
        const SEARCHIS = 'SearchInProgress is "%s"';
        const DISCOVERYFORMCOMPLETED = 'Updating Discovery Form completed';
        const CALLLOADDEVICES = 'Calling LoadDevices()...';
        const REQUESTACTION = 'ReqestAction called for Ident "%s" with Value %s';
        const GENERATINGFORMDONE = 'Finished generating the Discovery Form';
        const ADDCACHEDDEVICES = 'Adding cached devices to the form';
        const STARTTIMER = 'Starting a timer to process the search in a new thread...';


    }