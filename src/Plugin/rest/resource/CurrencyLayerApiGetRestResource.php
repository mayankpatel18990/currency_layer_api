<?php

namespace Drupal\currency_layer_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Routing;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a resource to get currency layer api.
 * @RestResource(
 *   id = "api_currency",
 *   label = @Translation("API Currency"),
 *   uri_paths = {
 *     "canonical" = "/api/currency"
 *   }
 * )
 */
class CurrencyLayerApiGetRestResource extends ResourceBase {
    
    /**
     * Responds to custom GET requests.
     * @return \Drupal\rest\ResourceResponse
     */
    public function get(Request $request) {
        $response_data = [];
        // get the environ variables
        $api_url = Settings::get('api_url', '');
        $api_key = Settings::get('api_key', '');
        $client = \Drupal::httpClient();
        $options = [
            'headers' => [
                'apikey' => $api_key,
            ],
        ];
        try {
            if (!empty($request->query->all())) {
                
                //get all query parameters from API
                $query_params = $request->query->all();                
                
                //create api url with query string
                $query_str = UrlHelper::buildQuery($query_params);
                $url = $api_url . '?' . $query_str;
                
                //creates a get request
                $response = $client->get($url, $options);
                $response_data = Json::decode($response->getBody()->getContents());
                
                //get the cacheId and cached data
                $cacheId = $this->getCacheId($query_params);
                if ($cache = \Drupal::cache()->get($cacheId)) {
                    return new JsonResponse($cache->data);
                }
                else {     
                    if (!empty($response_data['success']) && $response_data['success'] === TRUE) {
                        \Drupal::cache()->set($cacheId, $response_data, \Drupal::time()->getRequestTime() + (86400));
                    }
                } 
            }
            else {
                $response = $client->get($api_url, $options);
                $response_data = Json::decode($response->getBody()->getContents());
            }              
        }
        catch (\Exception $e) {
            // If api is not working returns error
            return new JsonResponse(['error' => ['message' => 'Currency Api is not available', 'code' => '502'] ], 502, ['Content-Type'=> 'application/json']);
        }
        return new JsonResponse($response_data, 200, ['Content-Type'=> 'application/json']);
    }
      
    /**
     * This function helps us cache results regardless of the order of 
     * query parameters that are passed in. 
     *
     * @param array $query_params
     *   The query parameters.
     *
     * @return string
     *   Returns cacheId.
     */
    public function getCacheId(array $query_params) {
        ksort($query_params);
        $cacheId = 'currency_layer_api:api_currency';
        foreach ($query_params as $key => $value) {
            $cacheId .= $this->getCacheIdValue($key, $value);
        }
        return $cacheId;
    }
    
    /**
     *
     * This function helps work with both: ?currencies=EUR,GBP and ?currencies=GBP,EUR and
     * same for other parameters.
     *
     * @param string $key
     *   The query parameter name.
     * @param string $value
     *   The value of the query parameter.
     *
     * @return string
     *   cacheId fragment generated.
     */
    public function getCacheIdValue($key, $value): string {
        $value = explode(',', $value);
        sort($value);
        $sorted_value = implode(',', $value);
        return "$key={$sorted_value}:";
    }
}