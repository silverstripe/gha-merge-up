<?php

use SilverStripe\SupportedModules\MetaData;

// working directory will be root
include 'funcs.php';

// Read repositories.json from the installed package instead of fetching it over the network
MetaData::$isRunningUnitTests = true;
