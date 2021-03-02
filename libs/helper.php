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
        const DISCOVERTIMEOUT = 'DiscoverTimeout';
    }

    class Modules {
        const CHROMECAST = '{935F2596-C56A-88DB-A2B8-1A4A06605206}';
        const DNSSD = '{780B2D48-916C-4D59-AD35-5A429B2355A5}';
    }