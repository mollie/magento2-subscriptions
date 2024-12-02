<p align="center">
  <img src="https://github-production-user-asset-6210df.s3.amazonaws.com/24823946/391594367-66de170d-2a3f-4176-b6f4-42299262428a.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20241202%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20241202T150108Z&X-Amz-Expires=300&X-Amz-Signature=4a5367f56a0ad9dae6fe06f62da919d2e3326da14ac47368afcb31a61a7d6c94&X-Amz-SignedHeaders=host" />
  
</p>
<h1 align="left">Mollie Subscriptions Addon for Magento 2.3.x and higher</h1>

This plugin is an **addon** on the [Mollie Magento 2 payment module](https://github.com/mollie/magento2/) and can't be installed seperatly without the Mollie Payment plugin installed.

## Installation
We recommend that you make a backup of your webshop files, as well as the database.

Step-by-step to install the Magento® 2 extension through Composer:

1.	Make sure the [Mollie Magento 2 payment module](https://github.com/mollie/magento2/) is installed.
2.	Connect to your server running Magento® 2 using SSH or other method (make sure you have access to the command line).
3.	Locate your Magento® 2 project root.
4.	Install the Magento® 2 extension through composer and wait till it's completed:
```
composer require mollie/magento2-subscriptions
``` 
4.	Once completed run the Magento® module enable command:
```
bin/magento module:enable Mollie_Subscriptions
``` 
5.	After that run the Magento® upgrade and clean the caches:
```
php bin/magento setup:upgrade
php bin/magento cache:flush
```
6.  If Magento® is running in production mode you also need to redeploy the static content:
```
php bin/magento setup:static-content:deploy
```
7.  Go to your Magento® admin portal and open; ‘Stores’ > ‘Configuration’ > ‘Mollie’ > ‘Subscriptions’ to activate the Subscriptions.
8.  Configure the Subscription products using the Mollie attribute settings within the Mollie attribute tab. 
   

## License ##
[BSD (Berkeley Software Distribution) License](http://www.opensource.org/licenses/bsd-license.php).
Copyright (c) 2011-2021, Mollie B.V.
