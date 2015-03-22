<?php

namespace MKraemer\GarminConnect\ActivityUploader;

use Exception;
use SplFileInfo;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;

/**
 * MKraemer\GarminConnect\ActivityUploader\ActivityUploader
 */
class ActivityUploader
{
    const RESULT_UPLOAD_SUCCESSFUL = 1;

    const RESULT_UPLOAD_DUPLICATE = 2;

    public function __construct(HttpClient $httpClient, CookieJar $cookieJar)
    {
        $this->httpClient = $httpClient;

        $this->cookieJar = $cookieJar;
    }

    public function __invoke(SplFileInfo $fileInfo)
    {
        $response = $this->httpClient->post(
            'https://connect.garmin.com/proxy/upload-service-1.1/json/upload/.fit',
            [
                'cookies' => $this->cookieJar,
                'body' => [
                    'data' => fopen($fileInfo->getRealPath(), 'r'),
                    'responseContentType' => 'text/html'
                ]
            ]
        );

        return $this->parseResponse($response);
    }

    public function parseResponse($response)
    {
        /*
         * response for a successful upload:
         * {"detailedImportResult":{"uploadId":xxxxxxxxxx,"owner":xxxxxxx,"fileSize":1260,"processingTime":14157,"creationDate":"2015-03-21 17:46:45.658 GMT","ipAddress":"xxxxxxxxxxxxxx","fileName":"2015-03-16-11-14-34.fit","report":{"@class":"uploadReport","entries":[],"createdOn":"2015-03-21 17:46:33.336 GMT","children":[],"userProfileId":2769627},"successes":[{"internalId":725414413,"externalId":"795435274","messages":null}],"failures":[]}}
         *
         * response for a file which was already uploaded previously:
         * {"detailedImportResult":{"uploadId":"","owner":xxxxxxx,"fileSize":1260,"processingTime":1839,"creationDate":"2015-03-21 18:03:55.252 GMT","ipAddress":"xxxxxxxxxxxxxx","fileName":"2015-03-16-11-14-34.fit","report":null,"successes":[],"failures":[{"internalId":725414413,"externalId":"795435274","messages":[{"code":202,"content":"Duplicate Activity."}]}]}}
         */

        $responseData = json_decode((string) $response->getBody(), true);

        switch (true) {
            case (!empty($responseData['detailedImportResult']['successes'])):
                return self::RESULT_UPLOAD_SUCCESSFUL;

            case ($responseData['detailedImportResult']['failures'][0]['messages'][0]['code'] == 202):
                return self::RESULT_UPLOAD_DUPLICATE;

            default:
                throw new Exception('Could not determine upload result');
        }
    }
}
