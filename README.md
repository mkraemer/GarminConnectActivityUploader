# GarminConnect Activity Uploader

This Activity Uploader provides a way to upload activity files into a GarminConnect account.

## Usage

The following examples makes use of mkraemer/GarminConnectSSO to obtain the cookies to make authenticated calls agains the GarminConnect API:

```php

use GuzzleHttp\Client as HttpClient;
use MKraemer\GarminConnect\SSO\SSO as GarminConnectSSO;
use MKraemer\GarminConnect\ActivityUploader\ActivityUploader as GarminConnectActivityUploader;

$client = new HttpClient();

$sso = new GarminConnectSSO(
        $client,
        'username',
        'password'
        );

$cookieJar = $sso();

$activityUploader = new GarminConnectActivityUploader($client, $cookieJar);

$fileInfo = new SplFileInfo('/path/to/garmin_device/Activities/2015-04-02.fit');

$result = $activityUploader($fileInfo);

switch ($result) {
    case GarminConnectActivityUploader::RESULT_UPLOAD_SUCCESSFUL:
        echo sprintf('%s successfully uploaded', $fileInfo->getFilename());
        break;

    case GarminConnectActivityUploader::RESULT_UPLOAD_DUPLICATE:
        echo sprintf('%s was already uploaded', $fileInfo->getFilename());
        break;
}

```
