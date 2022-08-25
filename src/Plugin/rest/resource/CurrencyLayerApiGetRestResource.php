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
    public function get() {
        $response_data = '';

        // get the environ variables
        $api_url = Settings::get('api_url', '');
        $api_token = Settings::get('api_token', '');
        
        try { 
            if (!empty(\Drupal::request()->query->has('source')) && !empty(\Drupal::request()->query->has('currencies'))) {
                $source = \Drupal::request()->query->get('source');
                $currencies = \Drupal::request()->query->get('currencies');
                $options = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'apikey' => $api_token,
                    ],
                ];
                
                //Build query parameters to pass in method with api url
                $query = [
                    'source' => $source,
                    'currencies' => $currencies,
                ];
                $query_str = UrlHelper::buildQuery($query);
                $url = $api_url . '?' . $query_str;
                
                //Get Request
                $client = \Drupal::httpClient();
                $response = $client->get($url, $options);
                
                $status_code = $response->getStatusCode();
                if ($status_code == 200) {
                    $response_data_json = Json::decode($response->getBody()->getContents());
                    
                    //Cache API code
                    $cacheId = 'currency_layer_api:api_currency';    
                    if ($cache = \Drupal::cache()->get($cacheId, true)) {
                        $cached_data = $cache->data;
                        return new JsonResponse($cached_data, 200, ['Content-Type'=> 'application/json']);
                    }
                    else {
                        $response_data = \Drupal::cache()->set($cacheId, $response_data_json, \Drupal::time()->getRequestTime() + (86400));
                        return new JsonResponse($response_data, 200, ['Content-Type'=> 'application/json']);
                    }
                }
                else {
                    return new JsonResponse(['error' => ['message' => 'Currency Api is not available', 'code' => '502'] ], 400, ['Content-Type'=> 'application/json']);
                }
            }
            else {
                $options = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'apikey' => $api_token,
                    ],
                ];
                $client = \Drupal::httpClient();
                $response = $client->get($api_url, $options);
                $response_data = Json::decode($response->getBody()->getContents());
                return new JsonResponse($response_data, 200, ['Content-Type'=> 'application/json']);
            }
        }
        catch (RequestException $e) {
            return new JsonResponse(['error' => ['message' => 'Currency Api is not available', 'code' => '502'] ], 400, ['Content-Type'=> 'application/json']);
        } 
        catch (\Exception $e) {
            return new JsonResponse(['error' => ['message' => 'Currency Api is not available', 'code' => '502'] ], 400, ['Content-Type'=> 'application/json']);
        }
    }
}