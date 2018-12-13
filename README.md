# Peach Commerce – PrestaShop  Gateway Module for Lightning

## 1. Description

We are providing you with merchant module, which can be used with PrestaShop e-commerce platform. With the help of this module you can accept Bitcoin Lightning payments with small fees. Selling goods or services now becomes easier. You don't need to deploy your own LND or perform complicated setup. Withdrawing process is quick and easy. You can withdraw your income with a single aggregated bitcoin transaction through Peach Public Node.

## 2. Installation

Requires PHP >= 5.4.

Requires the `php-curl` and extensions.

1. Download peach-commerce.zip.

2. Upload Peach Commerce module in the Modules tab => Modules & Services.

3. Configure Peach Commerce in the Modules tab => Modules & Services => Configure Peach Commerce

To set up your module, you should request a merchant account on Peach Public Node. You can do it on Peach website [https://lightningpeach.com/peach-commerce]

4. To view your balance and get information on how to set up Cron job, Open Payment => Peach Commerce.

That's it! The "Bitcoin Lightning" payment option should now be available on your checkout page, you are ready to accept Bitcoin.

## 3. How to connect to Peach Public Node

Request account creation on Peach Public Node - [https://lightningpeach.com/peach-commerce]

The following parameters will be requested by Peach team to create account on Peach Public Node:
- Name of your business.
- Notification URL – a URL for webhook to inform you about a new successful payment. You can find it in PrestaShop admin page in the Modules tab => Modules & Services => Configure Peach Commerce.
- BTC address – a Bitcoin address for onchain withdrawal of your funds. For security reasons it is better not to change it.


## 4 Configure Peach Commerce:
To configure Peach Commerce please set up the following fields:
•	Merchant ID is a secret key to your account for managing it on Peach Public Node and will be provided by lightning team.
NOTE: Merchant ID should not be shared with anyone to keep your account secure.
•	HUB Host – Specify a url for Peach Public Node API (you will get it with your merchant id).
•	Notification URL – will be set up as default when module is uploaded.

Cron job is needed to update the order status right away for both sides Order confirmation and Admin pages.
Set up cron job:
1.	Open Payment => Peach Commerce to get the link to add to cron job set up.
2.	Set up cron job and add the link from Step 1 to the job.


## 5. Contact

If you have any questions, please contact us:
https://github.com/LightningPeach
contact@lightningpeach.com


