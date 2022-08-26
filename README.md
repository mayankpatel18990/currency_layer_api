# currency_layer_api

This module was tested with Currency Data Api.

Documentation: https://apilayer.com/marketplace/currency_data-api#documentation-tab

-----------------------

This module assumes, api url and key will be provided via settings.php as of now.

-----------------------

Other contrib modules can be used like dotenv to directly access env
vars in our module, but I prefer having a layer of settings.php in between.

-----------------------

@Example

$settings['api_url'] = 'https://api.apilayer.com/currency_data/live';
$settings['api_key'] = '{apiKey}';


By default the api url will be accessible to admin users, please provide the needed
permissions to anonymous/ other roles as needed.# currency_layer_api
Wild Style Network Tasks
